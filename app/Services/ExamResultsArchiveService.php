<?php

namespace App\Services;

use App\Exports\ExamResultsSummaryExport;
use App\Models\Answer;
use App\Models\AnswerEssay;
use App\Models\Essay;
use App\Models\InterviewAssessment;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\PraktikScore;
use App\Models\Question;
use App\Models\Student;
use App\Models\StudentPraktikAttempt;
use App\Models\StudentTask;
use App\Models\User;
use App\Support\AnswerFile;
use App\Support\RichText;
use App\Support\RubrikWawancara;
use App\Support\UjianPraktik;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Mpdf\Mpdf;

/**
 * ZIP hasil seluruh ujian satu sesi (Rekap Hasil → "Unduh Hasil Ujian"):
 *   Rekap Hasil Ujian.xlsx                         ← nilai per komponen semua peserta
 *   {No. Peserta} - {Nama}/Hasil Ujian - {No}.pdf  ← lembar jawaban PG + Esai + Wawancara + ringkasan Praktik
 *   {No. Peserta} - {Nama}/Esai - Berkas Jawaban/  ← berkas Essay Migas
 *   {No. Peserta} - {Nama}/Ujian Praktik …/        ← unggahan Ujian Praktik/Keterampilan (per bagian) / Upload Tugas
 *
 * Hanya membaca: nilai dihitung tanpa disimpan (calculateForSession). Jawaban dicocokkan lewat
 * question_id / essay_id + sesi, sama dengan detail jawaban admin (ReportController::show).
 */
class ExamResultsArchiveService
{
    public function __construct(private ResultCalculatorService $calculator)
    {
    }

    /** Path ZIP sementara — hapus setelah dikirim. $studentIds: batasi ke peserta ini (bawaan semua peserta sesi). */
    public function build(ExamSession $session, ?iterable $studentIds = null): string
    {
        $session->loadMissing(['examPg.classroom', 'examEsai.classroom']);

        $ids = ExamSession::activeStudentIds($session->id);
        if ($studentIds !== null) {
            $ids = $ids->intersect(collect($studentIds)->map(fn ($id) => (int) $id))->values();
        }
        $students = Student::whereIn('id', $ids)->orderBy('no_participant')->get();

        $pg    = $session->examPg;
        $esai  = $session->examEsai;
        $parts = UjianPraktik::parts($session->id);

        $questions = $pg ? $pg->questions()->get() : collect();
        $essays    = $esai ? $esai->essays()->get() : collect();

        $mine = fn ($query, ?Exam $exam) => $query->where('exam_session_id', $session->id)
            ->where('exam_id', $exam?->id)
            ->whereIn('student_id', $ids);

        $pgAnswers    = $pg ? $mine(Answer::query(), $pg)->get()->groupBy('student_id') : collect();
        $essayAnswers = $esai ? $mine(AnswerEssay::query(), $esai)->get()->groupBy('student_id') : collect();

        $bySession = fn ($model) => $model::where('exam_session_id', $session->id)->whereIn('student_id', $ids);
        $tasks     = $bySession(StudentTask::class)->orderBy('bagian')->get()->groupBy('student_id');
        $attempts  = $bySession(StudentPraktikAttempt::class)->get()->groupBy('student_id');
        $scores    = $bySession(PraktikScore::class)->get()->groupBy('student_id');
        $interviews = $bySession(InterviewAssessment::class)->get()->keyBy('student_id');
        // Sesi dengan Ujian Praktik/Keterampilan: wawancara tanpa kriteria (D) Worksheet
        $tanpaWorksheet = UjianPraktik::wawancaraTanpaWorksheet($session->id);
        $hasWawancara   = (bool) $session->has_wawancara || $interviews->isNotEmpty();

        $results = collect($this->calculator->calculateForSession($session, $ids))->keyBy('student_id');

        $users = User::whereIn('id', $essayAnswers->flatten()->pluck('assessed_by')
            ->merge($scores->flatten()->pluck('asesor_id'))
            ->merge($interviews->pluck('asesor_id'))->filter()->unique())->pluck('name', 'id');

        $tmpDir = storage_path('app/tmp');
        if (! is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }
        $token   = Str::random(12);
        $zipPath = $tmpDir . "/hasil_ujian_{$token}.zip";
        // PDF per peserta ditulis ke file dulu (addFile dibaca saat close) — addFromString menahan
        // semua PDF di memori sampai ZIP ditutup.
        $pdfDir = $tmpDir . "/hasil_ujian_{$token}";
        mkdir($pdfDir, 0755, true);

        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        $summary = [];

        foreach ($students as $student) {
            $folder  = self::safeName("{$student->no_participant} - {$student->name}");
            $result  = $results->get($student->id);
            $myTasks = $tasks->get($student->id, collect())->keyBy('bagian');
            $myScore = $scores->get($student->id, collect())->keyBy('bagian');
            $myTry   = $attempts->get($student->id, collect())->keyBy('bagian');

            $pgItems   = $pg ? $this->pgItems($questions, $pgAnswers->get($student->id, collect())->keyBy('question_id')) : null;
            $essayRows = $essayAnswers->get($student->id, collect());
            $esaiItems = $esai ? $this->essayItems($essays, $essayRows->keyBy('essay_id'), $users) : null;

            // Berkas Essay Migas: satu berkas untuk seluruh soal (ditulis ke tiap baris jawaban)
            $esaiFiles = [];
            foreach ($essayRows->filter(fn ($a) => AnswerFile::isEssayMigasPath($a->answer))->unique('answer')->values() as $i => $a) {
                $name = self::safeName(basename(str_replace('\\', '/', $a->answer)));
                if ($this->addStoredFile($zip, $a->answer, "{$folder}/Esai - Berkas Jawaban/" . ($i ? ($i + 1) . '. ' : '') . $name)) {
                    $esaiFiles[] = $name;
                }
            }

            $praktik = $this->praktikItems($parts, $myTasks, $myScore, $myTry, $users);
            foreach ($praktik as $k => $item) {
                if ($item['task'] && ! $this->addStoredFile($zip, $item['task']->file_path, "{$folder}/{$item['folder']}/" . self::safeName($item['file_name']))) {
                    $praktik[$k]['file_missing'] = true;
                }
            }

            $pdfFile = "{$pdfDir}/{$student->id}.pdf";
            $this->pdf($pdfFile, [
                'session'    => $session,
                'skema'      => $pg?->classroom?->title ?? $esai?->classroom?->title ?? $student->classroom?->title,
                'student'    => $student,
                'result'     => $result,
                'pg'         => $pg,
                'pgItems'    => $pgItems,
                'esai'       => $esai,
                'esaiItems'  => $esaiItems,
                'esaiFiles'  => $esaiFiles,
                'praktik'    => $praktik,
                'hasWawancara' => $hasWawancara,
                'wawancara'  => $hasWawancara ? $this->wawancaraItem($interviews->get($student->id), $tanpaWorksheet, $users) : null,
            ]);
            $zip->addFile($pdfFile, "{$folder}/Hasil Ujian - " . self::safeName($student->no_participant ?: $student->name) . '.pdf');

            $summary[] = [
                'no_peserta' => $student->no_participant,
                'nama'       => $student->name,
                'pg'         => $result?->nilai_pg,
                'esai'       => $result?->nilai_esai,
                'wawancara'  => $result?->nilai_wawancara,
                'praktik'    => $result?->nilai_praktik,
                'akhir'      => $result?->nilai_akhir,
                'keputusan'  => $result?->keputusan,
                'pg_terjawab' => $pgItems ? collect($pgItems)->where('status', '!=', 'kosong')->count() . '/' . count($pgItems) : null,
                'pg_benar'   => $pgItems ? collect($pgItems)->where('status', 'benar')->count() : null,
                'esai_dinilai' => $esaiItems ? collect($esaiItems)->whereNotNull('score')->count() . '/' . count($esaiItems) : null,
                'wawancara_catatan' => $interviews->get($student->id)?->catatan,
                'unggahan'   => collect($praktik)->filter(fn ($p) => $p['task'])->map(fn ($p) => $p['label'] . ': ' . $p['file_name'])->implode('; '),
            ];
        }

        $zip->addFromString('Rekap Hasil Ujian.xlsx', Excel::raw(
            new ExamResultsSummaryExport($summary, [
                'pg'        => (bool) $pg,
                'esai'      => (bool) $esai,
                'wawancara' => $hasWawancara,
                'praktik'   => (bool) $parts,
            ]),
            \Maatwebsite\Excel\Excel::XLSX
        ));

        $zip->close();

        foreach (glob("{$pdfDir}/*.pdf") as $file) {
            unlink($file);
        }
        rmdir($pdfDir);

        return $zipPath;
    }

    /** Nama file ZIP untuk diunduh. */
    public static function fileName(ExamSession $session): string
    {
        return 'Hasil Ujian - ' . self::safeName($session->title) . '.zip';
    }

    private function pgItems(Collection $questions, Collection $answers): array
    {
        return $questions->values()->map(function (Question $q, int $i) use ($answers) {
            $answer = $answers->get($q->id);
            $chosen = $answer && $answer->answer ? (int) $answer->answer : null;
            $option = fn (?int $n) => $n ? ['key' => chr(64 + $n), 'text' => $this->localImages((string) $q->{"option_{$n}"})] : null;

            return [
                'number'   => $i + 1,
                'question' => $this->localImages((string) $q->question),
                'chosen'   => $option($chosen),
                'correct'  => $option((int) $q->answer ?: null),
                // Sama dengan detail jawaban admin: is_correct saat dinilai, bukan kunci saat ini
                'status'   => $chosen === null ? 'kosong' : ($answer->is_correct === 'Y' ? 'benar' : 'salah'),
            ];
        })->all();
    }

    private function essayItems(Collection $essays, Collection $answers, Collection $users): array
    {
        return $essays->values()->map(function (Essay $essay, int $i) use ($answers, $users) {
            $answer = $answers->get($essay->id);
            $isFile = $answer && AnswerFile::isEssayMigasPath($answer->answer);

            return [
                'number'   => $i + 1,
                'question' => $this->localImages((string) $essay->question),
                'answer'   => $answer && ! $isFile && RichText::hasContent($answer->answer) ? $this->localImages($answer->answer) : null,
                'by_file'  => $isFile,
                'score'    => $answer?->score,
                'assessor' => $answer?->assessed_by ? $users->get($answer->assessed_by) : null,
            ];
        })->all();
    }

    /** Rincian wawancara: kriteria A–C (+ D Worksheet bila sesi tanpa Ujian Praktik), total = rata-rata, catatan & asesor. */
    private function wawancaraItem(?InterviewAssessment $ia, bool $tanpaWorksheet, Collection $users): array
    {
        $kriteria = [
            ['A', 'Gaya Wawancara', $ia?->gaya_wawancara, 'gaya_wawancara'],
            ['B', 'Penguasaan Materi', $ia?->penguasaan_materi, 'penguasaan_materi'],
            ['C', 'Kemampuan Menghadapi Pertanyaan', $ia?->kemampuan_hadapi_pertanyaan, 'kemampuan_hadapi_pertanyaan'],
        ];
        if (! $tanpaWorksheet) {
            $kriteria[] = ['D', 'Hasil Pengerjaan Worksheet Ujian Keterampilan', $ia?->hasil_worksheet, 'hasil_worksheet'];
        }

        return [
            'dinilai'  => (bool) $ia,
            // Kategori = pita Rubrik Penilaian Wawancara (sama untuk semua skema) + deskripsi indikatornya
            'kriteria' => array_map(fn ($k) => [
                'kode'      => $k[0],
                'nama'      => $k[1],
                'nilai'     => $k[2],
                'pita'      => RubrikWawancara::label(RubrikWawancara::pita($k[2])),
                'deskripsi' => RubrikWawancara::deskripsi($k[3], $k[2]),
            ], $kriteria),
            'total'    => $ia?->total_nilai,
            'total_label' => 'Total (rata-rata A–' . ($tanpaWorksheet ? 'C' : 'D') . ')',
            'catatan'  => $ia?->catatan,
            'asesor'   => $ia?->asesor_id ? $users->get($ia->asesor_id) : null,
            'tanggal'  => self::wib($ia?->updated_at),
        ];
    }

    /**
     * Satu baris per bagian Ujian Praktik/Keterampilan sesi, atau Upload Tugas biasa (sesi tanpa ujian praktik)
     * bila peserta mengunggah tugas.
     */
    private function praktikItems(array $parts, Collection $tasks, Collection $scores, Collection $attempts, Collection $users): array
    {
        $items = [];

        if (! $parts) {
            $task = $tasks->get(1);

            return $task ? [$this->praktikItem('Upload Tugas', 'Tugas', null, $task, null, null, $users)] : [];
        }

        foreach ($parts as $bagian => $exam) {
            $multi = count($parts) > 1;
            $items[] = $this->praktikItem(
                $multi ? "Bagian {$bagian}" : 'Ujian Praktik/Keterampilan',
                $multi ? "Ujian Praktik - Bagian {$bagian}" : 'Ujian Praktik',
                $exam,
                $tasks->get($bagian),
                $scores->get($bagian),
                $attempts->get($bagian),
                $users,
            );
        }

        return $items;
    }

    private function praktikItem(string $label, string $folder, ?Exam $exam, ?StudentTask $task, ?PraktikScore $score, ?StudentPraktikAttempt $attempt, Collection $users): array
    {
        $aspek = collect($exam?->praktik_aspek ?? [])->map(fn ($a) => [
            'no'      => $a['no'] ?? null,
            'nama'    => $a['nama'] ?? '',
            'kelompok' => $a['kelompok'] ?? null,
            'bobot'   => $a['bobot'] ?? null,
            'nilai'   => $score?->aspek[$a['no'] ?? ''] ?? null,
            'gerbang' => (bool) ($score?->rincian[$a['no'] ?? '']['gerbang'] ?? false),
            'catatan' => $score?->catatan[$a['no'] ?? ''] ?? null,
        ])->all();

        return [
            'label'        => $label,
            'folder'       => $folder,
            'title'        => $exam?->title,
            'started_at'   => self::wib($attempt?->started_at),
            'durasi'       => $attempt?->durasi,
            'task'         => $task,
            'file_name'    => $task ? ($task->original_filename ?: basename($task->file_path)) : null,
            'uploaded_at'  => self::wib($task?->uploaded_at),
            'file_missing' => false,
            'score'        => $score?->score,
            'assessor'     => $score?->asesor_id ? $users->get($score->asesor_id) : null,
            'aspek'        => $aspek,
        ];
    }

    private function addStoredFile(\ZipArchive $zip, ?string $path, string $entry): bool
    {
        $disk = AnswerFile::diskFor($path);
        if (! $disk) {
            return false;
        }

        // Unggahan (pdf/zip/office/gambar) umumnya sudah terkompresi — simpan apa adanya, jauh lebih cepat untuk berkas besar
        return $zip->addFile(Storage::disk($disk)->path($path), $entry)
            && $zip->setCompressionName($entry, \ZipArchive::CM_STORE);
    }

    private function pdf(string $file, array $data): void
    {
        $mpdf = new Mpdf([
            'mode'          => 'utf-8',
            'format'        => 'A4',
            'orientation'   => 'P',
            'margin_top'    => 12,
            'margin_bottom' => 14,
            'margin_left'   => 15,
            'margin_right'  => 15,
            // DejaVu Sans sudah memuat semua karakter teks soal/jawaban; pencarian substitusi ±30% waktu render
            'useSubstitutions' => false,
        ]);
        $mpdf->SetFooter('{PAGENO} / {nbpg}');
        $mpdf->WriteHTML(View::make('documents.hasil_ujian', $data + [
            'lsp'            => config('lsp_documents.lsp'),
            'logoEdukiaPath' => base_path(config('lsp_documents.assets.logo_edukia_kecil', config('lsp_documents.assets.logo_edukia'))),
        ])->render());

        $mpdf->Output($file, \Mpdf\Output\Destination::FILE);

        // mPDF menyimpan font & gambar dalam referensi melingkar — bebaskan sebelum peserta berikutnya
        unset($mpdf);
        gc_collect_cycles();
    }

    /**
     * Gambar soal dari disk public (/storage/…, mis. gambar paket soal) dibaca langsung dari file — mPDF
     * tidak bisa membuka alamat relatif dan tidak perlu lewat HTTP. Gambar lain (data:, situs luar) dibiarkan.
     */
    private function localImages(string $html): string
    {
        return preg_replace_callback('/(<img[^>]+src\s*=\s*["\'])([^"\']+)(["\'])/i', function ($m) {
            $src  = html_entity_decode($m[2]);
            $path = (string) parse_url($src, PHP_URL_PATH);
            $host = parse_url($src, PHP_URL_HOST);

            if (str_starts_with($path, '/storage/') && (! $host || str_contains((string) config('app.url'), $host))) {
                $relative = rawurldecode(substr($path, 9));
                if (! str_contains($relative, '..') && Storage::disk('public')->exists($relative)) {
                    return $m[1] . Storage::disk('public')->path($relative) . $m[3];
                }
            }

            return $m[0];
        }, $html) ?? $html;
    }

    private static function wib(?CarbonInterface $time): ?string
    {
        return $time ? $time->copy()->setTimezone('Asia/Jakarta')->format('d-m-Y H:i') . ' WIB' : null;
    }

    /** Nama aman untuk entri ZIP (tanpa pemisah folder / karakter yang ditolak Windows). */
    public static function safeName(?string $name): string
    {
        $name = trim(preg_replace('/[\\\\\/:*?"<>|\x00-\x1F]+/', '-', (string) $name), " .-");

        return Str::limit($name, 120, '') ?: 'tanpa-nama';
    }
}
