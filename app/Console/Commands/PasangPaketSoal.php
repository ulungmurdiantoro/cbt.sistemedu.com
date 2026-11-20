<?php

namespace App\Console\Commands;

use App\Models\Classroom;
use App\Models\Essay;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\Grade;
use App\Models\GradingScheme;
use App\Models\Question;
use App\Models\StudentPraktikAttempt;
use App\Models\StudentTask;
use App\Support\AnswerFile;
use Illuminate\Console\Command;
use Illuminate\Http\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Pasang paket soal (hasil ekstraksi dokumen FR.IA.04/05/06) sebagai ujian BARU per skema — tanpa import Excel.
 *
 * Data: database/data/paket-soal/{versi}/{skema}.json (+ images/ untuk gambar soal PG).
 * Per skema dibuat: Pilihan Ganda, Esai (bila ada di data), dan Ujian Praktik (panduan + template).
 * Ujian & sesi lama tidak disentuh. Ujian dikenali lewat exams_code "exms-{versi}-{skema}-{pg|esai|praktik}",
 * jadi perintah aman dijalankan ulang: ujian yang sudah ada dilewati, template praktik yang belum ada dilengkapi.
 *
 * Template Ujian Praktik (bisa > 20 MB, jadi tidak lewat upload) dibaca dari folder --templates
 * (default storage/app/paket-soal/{versi}/templates) lalu disalin ke disk private.
 *
 * Paket juga membawa durasi PG/Esai (`durasi`) dan Komposisi Nilai skema (`komposisi`) sesuai dokumen skema.
 *
 * --perbarui (alias lama --perbarui-praktik): yang sudah terpasang disamakan dengan paket — Ujian Praktik (judul,
 * panduan, durasi, template), durasi ujian PG/Esai, dan Komposisi Nilai skema. Ujian hanya diubah selama belum
 * dipakai peserta (belum ada yang mulai / mengunggah); yang sudah dipakai tidak diubah: ujian = versi soal, jadi
 * revisi untuk sesi berikutnya dibuat sebagai versi paket baru. Komposisi Nilai berlaku per skema, termasuk hasil
 * sesi lama yang belum Final.
 */
class PasangPaketSoal extends Command
{
    private const TYPE_PG = 'Pilihan Ganda';

    private const TYPE_ESAI = 'Essay';

    /** Sesi menampung dua bagian Ujian Praktik (exam_id_praktik, exam_id_praktik_2). */
    private const MAX_PRAKTIK_PARTS = 2;

    /** Isian `komposisi` paket (persen) = form Komposisi Nilai skema. */
    private const KOMPOSISI_FIELDS = ['bobot_ujian_tulis', 'proporsi_pg', 'bobot_wawancara', 'bobot_praktik_1', 'bobot_praktik_2', 'nilai_kelulusan'];

    /** Bila belum ada ujian sejenis di kelas itu untuk dicontoh pengaturannya. */
    private const DEFAULTS = [
        self::TYPE_PG   => ['duration' => 120, 'random_question' => 'Y', 'random_answer' => 'N', 'show_answer' => 'N'],
        self::TYPE_ESAI => ['duration' => 120, 'random_question' => 'N', 'random_answer' => 'N', 'show_answer' => 'N'],
    ];

    protected $signature = 'paket-soal:pasang
        {versi=v2 : Folder paket di database/data/paket-soal}
        {--skema=* : Hanya skema tertentu (nama file tanpa .json, mis. lifting)}
        {--templates= : Folder template Ujian Praktik (default storage/app/paket-soal/{versi}/templates)}
        {--perbarui : Samakan yang sudah terpasang dengan paket: Ujian Praktik, durasi PG/Esai, Komposisi Nilai}
        {--perbarui-praktik : Alias lama --perbarui}
        {--dry-run : Periksa data dan tampilkan rencana tanpa menyimpan}
        {--force : Jalankan tanpa konfirmasi}';

    protected $description = 'Pasang paket soal (PG, esai, ujian praktik) sebagai ujian baru per skema tanpa import';

    private string $versi;

    private string $dir;

    private string $templates;

    public function handle(): int
    {
        $this->versi = Str::lower($this->argument('versi'));
        $this->dir = database_path("data/paket-soal/{$this->versi}");
        $this->templates = rtrim($this->option('templates') ?: storage_path("app/paket-soal/{$this->versi}/templates"), '/\\');

        if (!is_dir($this->dir)) {
            $this->error("Paket {$this->versi} tidak ditemukan ({$this->dir}).");

            return self::FAILURE;
        }

        $paket = $this->load();
        if ($paket === null) {
            return self::FAILURE;
        }

        $problems = [];
        $rows = [];
        foreach ($paket as $key => $data) {
            foreach ($this->validate($data) as $p) {
                $problems[] = "{$key}: {$p}";
            }
            $rows[] = $this->planRow($key, $data);
        }

        $this->info('Paket Soal ' . $this->label() . " — template praktik dari: {$this->templates}");
        $this->table(['Skema', 'Kelas', 'Pilihan Ganda', 'Esai', 'Ujian Praktik', 'Komposisi Nilai'], $rows);

        if (!$this->praktikReady()) {
            $this->warn('Fitur Ujian Praktik belum terpasang di database ini (migrasi add_ujian_praktik_exam_type) — bagian praktik dilewati.');
        }

        if ($problems) {
            $this->error('Data paket bermasalah, tidak ada yang dipasang:');
            foreach ($problems as $p) {
                $this->line("  - {$p}");
            }

            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            $this->info('Dry run — tidak ada yang disimpan. Data paket lolos pemeriksaan.');

            return self::SUCCESS;
        }

        if (!$this->option('force') && !$this->confirm('Pasang ujian di atas?', true)) {
            return self::SUCCESS;
        }

        foreach ($paket as $key => $data) {
            $this->install($key, $data);
        }

        $this->newLine();
        $this->info('Selesai. Pilih ujian ' . $this->label() . ' di Sesi Ujian (Ujian PG / Esai / Ujian Praktik); sesi lama tidak berubah.');
        $this->line('Skema tanpa ujian esai: kosongkan Ujian Esai di sesi — bobot esai otomatis dibagi ke PG & wawancara.');
        if (collect($paket)->contains(fn ($data) => count($this->praktikParts($data)) > 1)) {
            $this->line('Ujian Praktik dua bagian: pilih bagian 1 di "Ujian Praktik" dan bagian 2 di "Ujian Praktik Bagian 2" pada sesi.');
        }

        return self::SUCCESS;
    }

    /** @return array<string, array>|null */
    private function load(): ?array
    {
        $only = array_map('strtolower', $this->option('skema'));
        $paket = [];

        foreach (glob("{$this->dir}/*.json") as $file) {
            $key = pathinfo($file, PATHINFO_FILENAME);
            if ($only && !in_array($key, $only, true)) {
                continue;
            }

            try {
                $paket[$key] = json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                $this->error("{$key}.json tidak valid: {$e->getMessage()}");

                return null;
            }
        }

        if (!$paket) {
            $this->error('Tidak ada skema yang cocok' . ($only ? ' dengan --skema=' . implode(',', $only) : '') . '.');

            return null;
        }

        return $paket;
    }

    /** @return string[] */
    private function validate(array $data): array
    {
        $problems = [];

        if (empty($data['classroom_code'])) {
            $problems[] = 'classroom_code kosong';
        }

        $pg = $data['pilihan_ganda'] ?? [];
        if (!$pg) {
            $problems[] = 'tidak ada soal pilihan ganda';
        }
        $seen = [];
        foreach ($pg as $q) {
            $no = $q['no'] ?? '?';
            if (isset($seen[$no])) {
                $problems[] = "PG soal {$no} dobel";
            }
            $seen[$no] = true;

            $options = $q['options'] ?? [];
            if (count($options) < 2 || count($options) > 5 || in_array('', array_map('trim', $options), true)) {
                $problems[] = "PG soal {$no}: opsi harus 2–5 dan tidak kosong";
            }
            $answer = $q['answer'] ?? null;
            if (!is_int($answer) || $answer < 1 || $answer > count($options)) {
                $problems[] = "PG soal {$no}: kunci tidak valid";
            }
            $blocks = $q['blocks'] ?? [];
            if (!collect($blocks)->contains(fn ($b) => trim($b['text'] ?? '') !== '')) {
                $problems[] = "PG soal {$no}: teks soal kosong";
            }
            foreach ($blocks as $b) {
                if (isset($b['image']) && !is_file($this->imagePath($b['image']))) {
                    $problems[] = "PG soal {$no}: gambar {$b['image']} tidak ada";
                }
            }
        }

        foreach ($data['esai'] ?? [] as $e) {
            $no = $e['no'] ?? '?';
            if (trim(strip_tags($e['question'] ?? '')) === '') {
                $problems[] = "esai {$no}: soal kosong";
            }
            if (isset($e['rubrik'])) {
                $valid = is_array($e['rubrik']) && $e['rubrik'] !== [] && collect($e['rubrik'])->every(fn ($b) => is_array($b)
                    && is_int($b['min'] ?? null) && is_int($b['max'] ?? null) && $b['min'] >= 0 && $b['min'] <= $b['max'] && $b['max'] <= 100
                    && trim($b['deskripsi'] ?? '') !== '');
                if (!$valid) {
                    $problems[] = "esai {$no}: rubrik harus daftar {min, max, label, deskripsi} dengan 0 ≤ min ≤ max ≤ 100";
                }
            }
        }
        if (isset($data['esai_panduan']) && (!is_string($data['esai_panduan']) || mb_strlen($data['esai_panduan']) > 50000)) {
            $problems[] = 'esai_panduan: harus teks HTML maksimal 50.000 karakter';
        }

        foreach ($data['durasi'] ?? [] as $part => $menit) {
            if (!in_array($part, ['pg', 'esai'], true) || !is_int($menit) || $menit < 1 || $menit > 1440) {
                $problems[] = "durasi {$part}: harus pg/esai, 1–1440 menit";
            }
        }

        $parts = $this->praktikParts($data);
        if ($komposisi = $data['komposisi'] ?? null) {
            foreach (self::KOMPOSISI_FIELDS as $field) {
                $v = $komposisi[$field] ?? null;
                if (!is_int($v) && !is_float($v) || $v < 0 || $v > 100) {
                    $problems[] = "komposisi: {$field} harus 0–100";
                }
            }
            $total = ($komposisi['bobot_ujian_tulis'] ?? 0) + ($komposisi['bobot_wawancara'] ?? 0)
                + ($komposisi['bobot_praktik_1'] ?? 0) + ($komposisi['bobot_praktik_2'] ?? 0);
            if (abs($total - 100) > 0.01) {
                $problems[] = "komposisi: Ujian Tulis + Lisan + Praktik harus 100% (sekarang {$total}%)";
            }
            foreach ([1, 2] as $bagian) {
                if (($komposisi["bobot_praktik_{$bagian}"] ?? 0) > 0 && !isset($parts[$bagian])) {
                    $problems[] = "komposisi: bobot praktik bagian {$bagian} tanpa Ujian Praktik bagian {$bagian}";
                }
            }
        }

        if (count($parts) > self::MAX_PRAKTIK_PARTS) {
            $problems[] = 'praktik: maksimal ' . self::MAX_PRAKTIK_PARTS . ' bagian per sesi';
        }
        foreach ($parts as $bagian => $praktik) {
            $label = count($parts) > 1 ? "praktik bagian {$bagian}" : 'praktik';
            if (count($parts) > 1 && trim($praktik['nama'] ?? '') === '') {
                $problems[] = "{$label}: nama bagian kosong";
            }
            if (trim($praktik['panduan'] ?? '') === '' || mb_strlen($praktik['panduan']) > 20000) {
                $problems[] = "{$label}: panduan kosong atau lebih dari 20.000 karakter";
            }
            if (isset($praktik['aspek'])) {
                $aspek = $praktik['aspek'];
                $valid = is_array($aspek) && $aspek !== [] && collect($aspek)->every(fn ($a) => is_array($a)
                    && is_int($a['no'] ?? null) && trim($a['nama'] ?? '') !== '' && is_numeric($a['bobot'] ?? null) && $a['bobot'] > 0);
                if (!$valid || count(array_unique(array_column($aspek, 'no'))) !== count($aspek)) {
                    $problems[] = "{$label}: aspek harus daftar {no, nama, bobot} dengan no unik dan bobot > 0";
                } elseif (abs(array_sum(array_column($aspek, 'bobot')) - 100) > 0.01) {
                    $problems[] = "{$label}: total bobot aspek harus 100%";
                }
                // Aspek per butir: butir {no, nama} + pilihan {label, nilai}; nilai maksimal = 100
                foreach ($valid ? $aspek : [] as $a) {
                    if (!isset($a['item'])) {
                        continue;
                    }
                    $nilai = array_column($a['pilihan'] ?? [], 'nilai');
                    if (!$a['item'] || !$nilai || max($nilai) * count($a['item']) != 100) {
                        $problems[] = "{$label}: aspek {$a['no']} — butir × nilai pilihan tertinggi harus 100";
                    }
                }
            }
            if (isset($praktik['rubrik']) && (!is_string($praktik['rubrik']) || strlen($praktik['rubrik']) > 200000)) {
                $problems[] = "{$label}: rubrik harus teks HTML (maks. 200 KB)";
            }
            $ext = strtolower(pathinfo($praktik['template_name'] ?? '', PATHINFO_EXTENSION));
            if (!in_array($ext, AnswerFile::EXTENSIONS, true)) {
                $problems[] = "{$label}: tipe template .{$ext} tidak diizinkan";
            }
            $duration = $praktik['duration'] ?? null;
            if (!is_int($duration) || $duration < 0 || $duration > 1440) {
                $problems[] = "{$label}: durasi harus 0–1440 menit";
            }
        }

        return $problems;
    }

    private function planRow(string $key, array $data): array
    {
        $classroom = $this->classroom($data);
        $kelas = $classroom ? "{$classroom->classrooms_code} — {$classroom->title}" : "{$data['classroom_code']} — TIDAK DITEMUKAN";

        $pg = $data['pilihan_ganda'] ?? [];
        $images = collect($pg)->flatMap(fn ($q) => collect($q['blocks'])->pluck('image')->filter())->unique()->count();
        $durasi = $data['durasi'] ?? [];
        $pgCell = count($pg) . ' soal' . ($images ? ", {$images} gambar" : '') . ' → '
            . $this->status($classroom, $key, 'pg', self::TYPE_PG, $durasi['pg'] ?? null);

        $esaiCell = !empty($data['esai'])
            ? count($data['esai']) . ' soal → ' . $this->status($classroom, $key, 'esai', self::TYPE_ESAI, $durasi['esai'] ?? null)
                . $this->esaiRubrikStatus($classroom, $key, $data)
            : '— (tanpa esai)';

        $parts = $this->praktikParts($data);
        $lines = [];
        foreach ($parts as $bagian => $praktik) {
            $file = $this->templateFile($praktik);
            $tpl = $file ? 'template ' . round(filesize($file) / 1048576, 1) . ' MB' : 'TEMPLATE BELUM ADA';
            $durasi = $praktik['duration'] ? "{$praktik['duration']} mnt" : 'tanpa batas waktu';
            $prefix = count($parts) > 1 ? "{$bagian}. {$praktik['nama']}: " : '';
            $lines[] = "{$prefix}{$tpl}, {$durasi} → "
                . ($this->praktikReady() ? $this->praktikStatus($classroom, $key, $praktik, $bagian, count($parts)) : 'dilewati');
        }
        $praktikCell = $lines ? implode("\n", $lines) : '—';

        $komposisiCell = isset($data['komposisi'])
            ? $this->komposisiText($data['komposisi']) . ' → ' . $this->komposisiStatus($classroom, $data['komposisi'])
            : '—';

        return [$key, $kelas, $pgCell, $esaiCell, $praktikCell, $komposisiCell];
    }

    private function status(?Classroom $classroom, string $key, string $part, string $type, ?int $durasi): string
    {
        if (!$classroom) {
            return 'dilewati';
        }

        $exam = Exam::where('exams_code', $this->code($key, $part))->first();
        if ($exam) {
            $n = $part === 'pg' ? $exam->questions()->count() : $exam->essays()->count();
            $text = $n ? "sudah ada ({$n} soal)" : 'sudah ada (kosong), soal diisi';
            if ($durasi === null || (int) $exam->duration === $durasi) {
                return $text . ", {$exam->duration} mnt";
            }
            if (!$this->perbarui()) {
                return $text . ", {$exam->duration} mnt (paket {$durasi}) — pakai --perbarui";
            }

            return $text . ($this->examUsed($exam)
                ? ", sudah dipakai — durasi {$exam->duration} mnt tidak diubah"
                : ", durasi {$exam->duration} → {$durasi} mnt");
        }

        [$settings, $from] = $this->settings($classroom, $type);
        $menit = $durasi ?? $settings['duration'];

        return "baru ({$menit} mnt" . ($durasi ? ' dari paket' : '') . ", acak soal {$settings['random_question']}; {$from})";
    }

    /** Ujian PG/Esai sudah dikerjakan peserta (ada yang mulai) — durasinya tidak diubah lagi. */
    private function examUsed(Exam $exam): bool
    {
        return Grade::where('exam_id', $exam->id)->whereNotNull('start_time')->exists();
    }

    private function praktikStatus(?Classroom $classroom, string $key, array $praktik, int $bagian, int $count): string
    {
        if (!$classroom) {
            return 'dilewati';
        }

        $exam = Exam::where('exams_code', $this->praktikCode($key, $bagian))->first();
        if (!$exam) {
            return 'baru';
        }

        $changes = $this->praktikChanges($exam, $praktik, $this->praktikTitle($classroom, $praktik, $count));
        if (!$changes) {
            return 'sudah ada, sama dengan paket';
        }

        $list = implode(', ', $changes);
        if (!$this->perbarui()) {
            return $exam->praktik_template_path
                ? "sudah ada, beda dengan paket ({$list}) — pakai --perbarui"
                : 'sudah ada, template dilengkapi';
        }

        $used = $this->praktikUsedBy($exam);

        return $used ? "sudah dipakai {$used} peserta — tidak diperbarui" : "diperbarui: {$list}";
    }

    /** Bagian Ujian Praktik terpasang yang berbeda dengan paket. Template yang file-nya belum diunggah tidak dibandingkan. */
    private function praktikChanges(Exam $exam, array $praktik, string $title): array
    {
        $changes = [];
        if ($exam->title !== $title) {
            $changes[] = 'judul';
        }
        if (trim((string) $exam->praktik_panduan) !== trim($praktik['panduan'])) {
            $changes[] = 'panduan';
        }
        if (trim((string) $exam->praktik_rubrik) !== trim((string) $this->rubrik($praktik))) {
            $changes[] = 'rubrik';
        }
        if (($exam->praktik_aspek ?: null) != ($praktik['aspek'] ?? null)) {
            $changes[] = 'aspek';
        }
        if ((int) $exam->duration !== (int) $praktik['duration']) {
            $changes[] = 'durasi';
        }

        $file = $this->templateFile($praktik);
        $disk = Storage::disk(AnswerFile::DISK);
        if (!$exam->praktik_template_path || !$disk->exists($exam->praktik_template_path)) {
            $changes[] = 'template';
        } elseif ($file && $disk->checksum($exam->praktik_template_path) !== md5_file($file)) {
            $changes[] = 'template';
        }

        return $changes;
    }

    /** Peserta yang sudah memulai / mengunggah Ujian Praktik ini (di sesi mana pun yang memakainya). */
    private function praktikUsedBy(Exam $exam): int
    {
        $started = StudentPraktikAttempt::where('exam_id', $exam->id)->pluck('student_id');
        $uploaded = StudentTask::where(function ($q) use ($exam) {
            $q->where('bagian', 1)->whereIn('exam_session_id', ExamSession::where('exam_id_praktik', $exam->id)->select('id'));
            $q->orWhere(fn ($q) => $q->where('bagian', 2)
                ->whereIn('exam_session_id', ExamSession::where('exam_id_praktik_2', $exam->id)->select('id')));
        })->pluck('student_id');

        return $started->merge($uploaded)->unique()->count();
    }

    private function install(string $key, array $data): void
    {
        $classroom = $this->classroom($data);
        if (!$classroom) {
            $this->warn("{$key}: kelas {$data['classroom_code']} tidak ditemukan — dilewati.");

            return;
        }

        $this->copyImages($data);
        $label = $this->label();
        $durasi = $data['durasi'] ?? [];

        $this->installPart($key, 'pg', $classroom, self::TYPE_PG, $durasi['pg'] ?? null, "{$classroom->title} - Pilihan Ganda {$label}",
            'Paket Soal ' . $label . ' — FR.IA.05 Pilihan Ganda (' . count($data['pilihan_ganda']) . ' soal).',
            function (Exam $exam) use ($data) {
                foreach ($data['pilihan_ganda'] as $q) {
                    $options = array_pad(array_map(fn ($o) => e($o), $q['options']), 5, null);
                    Question::create([
                        'exam_id'  => $exam->id,
                        'question' => $this->questionHtml($q),
                        'option_1' => $options[0],
                        'option_2' => $options[1],
                        'option_3' => $options[2],
                        'option_4' => $options[3],
                        'option_5' => $options[4],
                        'answer'   => $q['answer'],
                    ]);
                }

                return count($data['pilihan_ganda']);
            });

        if (!empty($data['esai'])) {
            $isEssay = Schema::hasColumn('essays', 'is_essay');
            $rubrikReady = $this->esaiRubrikReady();
            $this->installPart($key, 'esai', $classroom, self::TYPE_ESAI, $durasi['esai'] ?? null, "{$classroom->title} - Esai {$label}",
                'Paket Soal ' . $label . ' — FR.IA.06 Esai (' . count($data['esai']) . ' soal).',
                function (Exam $exam) use ($data, $isEssay, $rubrikReady) {
                    foreach ($data['esai'] as $e) {
                        Essay::create([
                            'exam_id'     => $exam->id,
                            'essays_code' => 'essy-' . Str::ulid(),
                            'question'    => $e['question'],
                            'answer'      => $e['answer'] ?? '',
                        ] + ($isEssay ? ['is_essay' => true] : []) + ($rubrikReady ? ['rubrik' => $e['rubrik'] ?? null] : []));
                    }
                    if ($rubrikReady) {
                        $exam->update(['esai_panduan' => $this->esaiPanduan($data)]);
                    }

                    return count($data['esai']);
                });

            if ($rubrikReady && $exam = Exam::where('exams_code', $this->code($key, 'esai'))->first()) {
                $this->syncEsaiRubrik($key, $exam, $data);
            }
        }

        $parts = $this->praktikParts($data);
        if ($parts && $this->praktikReady()) {
            foreach ($parts as $bagian => $praktik) {
                $this->installPraktik($key, $classroom, $praktik, $bagian, count($parts));
            }
        }

        if (isset($data['komposisi'])) {
            $this->applyKomposisi($key, $classroom, $data['komposisi']);
        }
    }

    private function installPart(string $key, string $part, Classroom $classroom, string $type, ?int $durasi, string $title, string $description, \Closure $fill): void
    {
        $code = $this->code($key, $part);
        $exam = Exam::where('exams_code', $code)->first();
        $count = $exam ? ($part === 'pg' ? $exam->questions()->count() : $exam->essays()->count()) : 0;

        // Durasi dari dokumen skema: ujian yang sudah ada disamakan dengan --perbarui, selama belum dikerjakan peserta
        if ($exam && $durasi !== null && (int) $exam->duration !== $durasi && $this->perbarui()) {
            if ($this->examUsed($exam)) {
                $this->warn("{$key}: {$exam->title} sudah dikerjakan peserta — durasi {$exam->duration} menit tidak diubah.");
            } else {
                $this->info("{$key}: {$exam->title} — durasi {$exam->duration} → {$durasi} menit.");
                $exam->update(['duration' => $durasi]);
            }
        }

        if ($exam && $count) {
            $this->line("{$key}: {$title} sudah ada ({$count} soal) — dilewati.");

            return;
        }

        DB::transaction(function () use (&$exam, $code, $classroom, $type, $durasi, $title, $description, $fill, $key) {
            if (!$exam) {
                [$settings] = $this->settings($classroom, $type);
                if ($durasi !== null) {
                    $settings['duration'] = $durasi;
                }
                $exam = Exam::create([
                    'exams_code'   => $code,
                    'title'        => $title,
                    'type'         => $type,
                    'classroom_id' => $classroom->id,
                    'description'  => $description,
                ] + $settings);
            }

            $n = $fill($exam);
            $this->info("{$key}: {$title} — {$n} soal dipasang (ujian #{$exam->id}).");
        });
    }

    private function installPraktik(string $key, Classroom $classroom, array $praktik, int $bagian, int $count): void
    {
        $code = $this->praktikCode($key, $bagian);
        $title = $this->praktikTitle($classroom, $praktik, $count);
        $exam = Exam::where('exams_code', $code)->first();

        if (!$exam) {
            $exam = Exam::create([
                'exams_code'      => $code,
                'title'           => $title,
                'type'            => Exam::TYPE_PRAKTIK,
                'classroom_id'    => $classroom->id,
                'duration'        => $praktik['duration'],
                'description'     => 'Paket Soal ' . $this->label() . ' — FR.IA.04 Ujian Praktik'
                    . ($count > 1 ? " bagian {$bagian} ({$praktik['nama']})" : '') . '.',
                'random_question' => 'N',
                'random_answer'   => 'N',
                'show_answer'     => 'N',
                'praktik_panduan' => $praktik['panduan'],
                'praktik_rubrik'  => $this->rubrik($praktik),
                'praktik_aspek'   => $praktik['aspek'] ?? null,
            ]);
            $this->info("{$key}: {$title} dibuat (ujian #{$exam->id}).");
        } elseif ($this->perbarui()) {
            $this->updatePraktik($key, $exam, $praktik, $title);

            return;
        }

        if ($exam->praktik_template_path) {
            $this->line("{$key}: {$title} sudah punya template — dilewati.");

            return;
        }

        $this->storeTemplate($key, $exam, $praktik);
    }

    /** Samakan Ujian Praktik terpasang dengan paket — hanya bila belum dipakai peserta. */
    private function updatePraktik(string $key, Exam $exam, array $praktik, string $title): void
    {
        $changes = $this->praktikChanges($exam, $praktik, $title);
        if (!$changes) {
            $this->line("{$key}: {$exam->title} sudah sama dengan paket — dilewati.");

            return;
        }

        if ($used = $this->praktikUsedBy($exam)) {
            $this->warn("{$key}: {$exam->title} sudah dipakai {$used} peserta — tidak diperbarui. Revisi untuk sesi berikutnya dibuat sebagai versi paket baru.");

            return;
        }

        $exam->title = $title;
        $exam->praktik_panduan = $praktik['panduan'];
        $exam->praktik_rubrik = $this->rubrik($praktik);
        $exam->praktik_aspek = $praktik['aspek'] ?? null;
        $exam->duration = $praktik['duration'];
        $exam->save();
        $this->info("{$key}: {$exam->title} — judul, panduan & durasi disamakan dengan paket.");

        if (in_array('template', $changes, true)) {
            $this->storeTemplate($key, $exam, $praktik);
        }
    }

    /** Salin template paket ke disk private; template lama (bila ada) dihapus setelah yang baru tersimpan. */
    private function storeTemplate(string $key, Exam $exam, array $praktik): void
    {
        $file = $this->templateFile($praktik);
        if (!$file) {
            $this->warn("{$key}: template {$praktik['template']} tidak ada di {$this->templates} — unggah folder template lalu jalankan ulang perintah ini.");

            return;
        }

        $ext = strtolower(pathinfo($praktik['template_name'], PATHINFO_EXTENSION));
        $name = (Str::slug(pathinfo($praktik['template_name'], PATHINFO_FILENAME)) ?: 'template')
            . '-' . now()->format('YmdHis') . '-' . Str::random(6) . ".{$ext}";
        $disk = Storage::disk(AnswerFile::DISK);
        $path = $disk->putFileAs("praktik_templates/{$exam->id}", new File($file), $name);

        $old = $exam->praktik_template_path;
        $exam->praktik_template_path = $path;
        $exam->praktik_template_name = $praktik['template_name'];
        $exam->save();

        if ($old && $old !== $path) {
            $disk->delete($old);
        }
        $this->info("{$key}: template {$praktik['template_name']} dipasang.");
    }

    private function questionHtml(array $q): string
    {
        return collect($q['blocks'])->map(function (array $b) use ($q) {
            if (isset($b['image'])) {
                $src = e("/storage/paket-soal/{$this->versi}/{$b['image']}");

                return "<p><img src=\"{$src}\" alt=\"Gambar soal {$q['no']}\" style=\"max-width:100%;height:auto\"></p>";
            }

            return '<p>' . e($b['text']) . '</p>';
        })->implode('');
    }

    /** Gambar soal disajikan dari disk public (/storage/paket-soal/{versi}/...). */
    private function copyImages(array $data): void
    {
        $disk = Storage::disk('public');
        foreach ($data['pilihan_ganda'] as $q) {
            foreach ($q['blocks'] as $b) {
                if (!isset($b['image'])) {
                    continue;
                }
                $target = "paket-soal/{$this->versi}/{$b['image']}";
                if (!$disk->exists($target)) {
                    $disk->put($target, file_get_contents($this->imagePath($b['image'])));
                }
            }
        }

        if (!is_dir(public_path('storage'))) {
            $this->warn('public/storage belum ada — jalankan php artisan storage:link supaya gambar soal tampil.');
        }
    }

    /** Pengaturan ujian = ujian sejenis terakhir di kelas itu (selain paket ini), atau bawaan. */
    private function settings(Classroom $classroom, string $type): array
    {
        $prev = Exam::where('classroom_id', $classroom->id)
            ->where('type', $type)
            ->where('exams_code', 'not like', "exms-{$this->versi}-%")
            ->latest('id')
            ->first();

        if (!$prev) {
            return [self::DEFAULTS[$type], 'bawaan'];
        }

        return [[
            'duration'        => (int) $prev->duration,
            'random_question' => $prev->random_question,
            'random_answer'   => $prev->random_answer,
            'show_answer'     => $prev->show_answer,
        ], "contoh ujian #{$prev->id}"];
    }

    private function classroom(array $data): ?Classroom
    {
        return Classroom::where('classrooms_code', $data['classroom_code'])->first();
    }

    /** Rubrik penilaian asesor (HTML dari FR.IA.04) — hanya tag tabel/paragraf yang dipertahankan. */
    private function rubrik(array $praktik): ?string
    {
        $html = trim((string) ($praktik['rubrik'] ?? ''));

        return $html === '' ? null : strip_tags($html, Exam::RUBRIK_TAGS);
    }

    private function esaiRubrikReady(): bool
    {
        return Schema::hasColumn('essays', 'rubrik') && Schema::hasColumn('exams', 'esai_panduan');
    }

    /** Panduan Penilaian Umum esai (FR.IA.06) — hanya tag paragraf/daftar yang dipertahankan. */
    private function esaiPanduan(array $data): ?string
    {
        $html = trim((string) ($data['esai_panduan'] ?? ''));

        return $html === '' ? null : strip_tags($html, Exam::PANDUAN_ESAI_TAGS);
    }

    /**
     * Perbedaan matriks rubrik/panduan antara ujian esai terpasang dan paket. Soal dicocokkan lewat teks pertanyaan
     * (seperti kunci-esai:perbarui), bukan posisi. Rubrik hanya panduan tampilan asesor — nilai tersimpan tidak berubah,
     * jadi boleh diperbarui meski jawaban sudah dinilai.
     *
     * @return array{0: array<int, array{0: Essay, 1: ?array}>, 1: bool, 2: int}  [soal berubah, panduan berubah, soal tak cocok]
     */
    private function esaiRubrikChanges(Exam $exam, array $data): array
    {
        $installed = $exam->essays()->get()->keyBy(fn (Essay $e) => PerbaruiKunciEsai::normalize($e->question));
        $changes = [];
        $missing = 0;
        foreach ($data['esai'] as $e) {
            $essay = $installed->get(PerbaruiKunciEsai::normalize($e['question'] ?? ''));
            if (!$essay) {
                $missing++;

                continue;
            }
            $rubrik = $e['rubrik'] ?? null;
            if ($essay->rubrik != $rubrik) {
                $changes[] = [$essay, $rubrik];
            }
        }

        return [$changes, (string) $exam->esai_panduan !== (string) $this->esaiPanduan($data), $missing];
    }

    private function esaiRubrikStatus(?Classroom $classroom, string $key, array $data): string
    {
        $hasRubrik = isset($data['esai_panduan']) || collect($data['esai'])->contains(fn ($e) => !empty($e['rubrik']));
        if (!$classroom || !$hasRubrik) {
            return '';
        }
        if (!$this->esaiRubrikReady()) {
            return "\nmatriks rubrik: dilewati (migrasi add_esai_rubrik belum dijalankan)";
        }
        $exam = Exam::where('exams_code', $this->code($key, 'esai'))->first();
        if (!$exam || !$exam->essays()->exists()) {
            return "\nmatriks rubrik ikut dipasang";
        }

        [$changes, $panduan, $missing] = $this->esaiRubrikChanges($exam, $data);
        $miss = $missing ? "; {$missing} soal paket tidak cocok dengan soal terpasang" : '';
        if (!$changes && !$panduan) {
            return "\nmatriks rubrik sudah sama{$miss}";
        }
        $what = trim(($changes ? count($changes) . ' soal' : '') . ($changes && $panduan ? ' + ' : '') . ($panduan ? 'panduan umum' : ''));

        return "\nmatriks rubrik {$what} " . ($this->perbarui() ? 'diperbarui' : 'berbeda — pakai --perbarui') . $miss;
    }

    private function syncEsaiRubrik(string $key, Exam $exam, array $data): void
    {
        [$changes, $panduan, $missing] = $this->esaiRubrikChanges($exam, $data);
        if ($missing) {
            $this->warn("{$key}: {$missing} soal esai paket tidak cocok dengan soal di {$exam->title} — matriksnya dilewati.");
        }
        if (!$changes && !$panduan) {
            return;
        }
        if (!$this->perbarui()) {
            $this->warn("{$key}: matriks rubrik esai {$exam->title} berbeda dengan paket — jalankan dengan --perbarui untuk menyamakan.");

            return;
        }

        DB::transaction(function () use ($changes, $panduan, $exam, $data) {
            foreach ($changes as [$essay, $rubrik]) {
                $essay->update(['rubrik' => $rubrik]);
            }
            if ($panduan) {
                $exam->update(['esai_panduan' => $this->esaiPanduan($data)]);
            }
        });
        $this->info("{$key}: {$exam->title} — matriks rubrik " . count($changes) . ' soal' . ($panduan ? ' + Panduan Penilaian Umum' : '') . ' diperbarui.');
    }

    private function perbarui(): bool
    {
        return (bool) ($this->option('perbarui') || $this->option('perbarui-praktik'));
    }

    private function komposisiText(array $k): string
    {
        $parts = ["Tulis {$k['bobot_ujian_tulis']}% (PG {$k['proporsi_pg']}%)"];
        if ($k['bobot_wawancara'] > 0) {
            $parts[] = "Lisan {$k['bobot_wawancara']}%";
        }
        if ($k['bobot_praktik_1'] > 0 || $k['bobot_praktik_2'] > 0) {
            $parts[] = 'Praktik ' . implode('/', array_filter([$k['bobot_praktik_1'], $k['bobot_praktik_2']])) . '%';
        }

        return implode(' + ', $parts) . ", lulus ≥{$k['nilai_kelulusan']}";
    }

    /** Kolom grading_schemes dari komposisi paket (bobot efektif PG/Esai dihitung seperti form Komposisi Nilai). */
    private function komposisiAttributes(array $k): array
    {
        return [
            'bobot_ujian_tulis' => $k['bobot_ujian_tulis'],
            'proporsi_pg'       => $k['proporsi_pg'],
            'bobot_pg'          => round($k['bobot_ujian_tulis'] * $k['proporsi_pg'] / 100, 2),
            'bobot_esai'        => round($k['bobot_ujian_tulis'] * (100 - $k['proporsi_pg']) / 100, 2),
            'bobot_wawancara'   => $k['bobot_wawancara'],
            'bobot_praktik_1'   => $k['bobot_praktik_1'],
            'bobot_praktik_2'   => $k['bobot_praktik_2'],
            'nilai_kelulusan'   => $k['nilai_kelulusan'],
        ];
    }

    private function komposisiDiffers(GradingScheme $scheme, array $k): bool
    {
        foreach ($this->komposisiAttributes($k) as $field => $value) {
            if (abs((float) $scheme->{$field} - (float) $value) > 0.001) {
                return true;
            }
        }

        return false;
    }

    private function komposisiReady(): bool
    {
        return Schema::hasColumn('grading_schemes', 'bobot_praktik_1');
    }

    private function komposisiStatus(?Classroom $classroom, array $k): string
    {
        if (!$classroom) {
            return 'dilewati';
        }
        if (!$this->komposisiReady()) {
            return 'dilewati (migrasi Nilai Praktik belum dijalankan)';
        }

        $scheme = GradingScheme::where('classroom_id', $classroom->id)->first();
        if (!$scheme) {
            return 'baru';
        }
        if (!$this->komposisiDiffers($scheme, $k)) {
            return 'sama';
        }

        return $this->perbarui() ? 'diperbarui' : 'beda dengan yang berlaku — pakai --perbarui';
    }

    /** Komposisi Nilai skema dari dokumen skema. Yang sudah ada & berbeda hanya diubah dengan --perbarui. */
    private function applyKomposisi(string $key, Classroom $classroom, array $k): void
    {
        if (!$this->komposisiReady()) {
            $this->warn("{$key}: Komposisi Nilai dilewati — jalankan migrasi Nilai Praktik dulu.");

            return;
        }

        $scheme = GradingScheme::where('classroom_id', $classroom->id)->first();
        if ($scheme && !$this->komposisiDiffers($scheme, $k)) {
            return;
        }
        if ($scheme && !$this->perbarui()) {
            $this->warn("{$key}: Komposisi Nilai {$classroom->title} beda dengan paket — tidak diubah (pakai --perbarui).");

            return;
        }

        GradingScheme::updateOrCreate(['classroom_id' => $classroom->id], $this->komposisiAttributes($k));
        $this->info("{$key}: Komposisi Nilai {$classroom->title} = " . $this->komposisiText($k) . '.');
        if ($scheme) {
            $this->line('   Berlaku per skema: hasil sesi lama yang belum Final ikut dihitung ulang saat Rekap dibuka.');
        }
    }

    /** Bagian Ujian Praktik paket: [bagian => data]. `praktik` = satu objek, atau daftar bagian (mis. Lifting). */
    private function praktikParts(array $data): array
    {
        $praktik = $data['praktik'] ?? null;
        if (!$praktik) {
            return [];
        }
        $list = array_is_list($praktik) ? $praktik : [$praktik];

        return array_combine(range(1, count($list)), $list);
    }

    /** Bagian 1 memakai kode lama (exms-{versi}-{skema}-praktik) supaya ujian yang sudah terpasang ikut diperbarui. */
    private function praktikCode(string $key, int $bagian): string
    {
        return $this->code($key, $bagian === 1 ? 'praktik' : "praktik-{$bagian}");
    }

    private function praktikTitle(Classroom $classroom, array $praktik, int $count): string
    {
        return "{$classroom->title} - Ujian Praktik {$this->label()}" . ($count > 1 ? " - {$praktik['nama']}" : '');
    }

    private function code(string $key, string $part): string
    {
        return "exms-{$this->versi}-{$key}-{$part}";
    }

    private function label(): string
    {
        return Str::upper($this->versi);
    }

    private function imagePath(string $rel): string
    {
        return "{$this->dir}/images/{$rel}";
    }

    private function templateFile(array $praktik): ?string
    {
        $file = "{$this->templates}/{$praktik['template']}";

        return is_file($file) ? $file : null;
    }

    private function praktikReady(): bool
    {
        return defined(Exam::class . '::TYPE_PRAKTIK') && Schema::hasColumn('exams', 'praktik_template_path');
    }
}
