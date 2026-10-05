<?php

namespace App\Http\Controllers\Admin;

use Mpdf\Mpdf;
use App\Models\Grade;
use App\Models\Answer;
use App\Models\AnswerEssay;
use App\Models\Essay;
use App\Models\ExamSession;
use App\Models\Question;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use App\Exports\GradesExport;
use App\Exports\GradesEssayExport;
use App\Exports\GradesEssayMigasExport;
use App\Exports\GradesSessionExport;
use App\Http\Controllers\Controller;
use App\Support\AnswerFile;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    // Menu "Laporan Nilai" sudah digabung ke Hasil Penilaian (admin.results.*): export Excel/PDF
    // dan detail jawaban dibuka dari Rekap Hasil per sesi. URL lama tetap hidup untuk bookmark.
    public function index()
    {
        return redirect()->route('admin.results.index');
    }

    public function filter(Request $request)
    {
        $session = ExamSession::find($request->integer('exam_session_id'));

        return $session
            ? redirect()->route('admin.results.show', $session)
            : redirect()->route('admin.results.index');
    }

    /**
     * Detail jawaban satu peserta untuk satu ujian (dibuka dari Rekap Hasil). Semua soal
     * dimuat sekaligus; jawaban dicocokkan lewat question_id / essay_id dan dibatasi ke
     * sesi grade ini — bukan lewat urutan baris.
     */
    public function show($id)
    {
        $grade = Grade::with('student', 'exam.classroom', 'exam_session')->findOrFail($id);
        $exam  = $grade->exam;

        $mine = fn ($q) => $q->where('exam_id', $exam->id)
            ->where('exam_session_id', $grade->exam_session_id)
            ->where('student_id', $grade->student_id);

        $props = [
            'grade' => [
                'id'      => $grade->id,
                'grade'   => $grade->grade,
                'exam'    => $exam->only('id', 'title', 'type'),
                'skema'   => $exam->classroom?->title,
                'session' => $grade->exam_session?->only('id', 'title', 'kode_batch'),
                'student' => $grade->student?->only('name', 'no_participant', 'position', 'institution'),
            ],
            'questions' => null,
            'essays'    => null,
            'files'     => [],
        ];

        if ($exam->type === 'Pilihan Ganda') {
            $props['questions'] = $this->pgQuestions($exam->questions()->get(), Answer::where($mine)->get()->keyBy('question_id'));
        } else {
            [$props['essays'], $props['files']] = $this->essayItems($exam->essays()->get(), AnswerEssay::where($mine)->get()->keyBy('essay_id'));
        }

        return inertia('Admin/Reports/Show', $props);
    }

    private function pgQuestions($questions, $answers): array
    {
        return $questions->values()->map(function (Question $q, int $i) use ($answers) {
            $answer = $answers->get($q->id);
            $chosen = $answer && $answer->answer ? (int) $answer->answer : null;

            return [
                'number'   => $i + 1,
                'question' => $q->question,
                // Opsi 1–2 selalu ada, 3–5 hanya bila diisi (sama seperti saat peserta ujian)
                'options'  => collect(range(1, 5))
                    ->filter(fn ($n) => $n <= 2 || !empty($q->{"option_{$n}"}))
                    ->map(fn ($n) => ['key' => $n, 'text' => $q->{"option_{$n}"}])
                    ->values(),
                'correct'  => (int) $q->answer,
                'chosen'   => $chosen,
                // Status mengikuti is_correct yang dipakai saat menilai, bukan kunci saat ini
                'status'   => $chosen === null ? 'kosong' : ($answer->is_correct === 'Y' ? 'benar' : 'salah'),
            ];
        })->all();
    }

    /** @return array{0: array, 1: array} [soal + jawaban teks + nilai, berkas jawaban Essay Migas] */
    private function essayItems($essays, $answers): array
    {
        // Essay Migas: satu berkas untuk seluruh ujian, path-nya ditulis ke tiap baris jawaban.
        $isFile = fn ($value) => str_starts_with(ltrim(str_replace('\\', '/', (string) $value), '/'), 'essay_migas_answers/');

        // Kosong = tanpa teks/gambar, atau hanya "-" (pengisi kunci jawaban yang tidak diisi)
        $hasContent = fn ($html) => !in_array(trim(strip_tags((string) $html, '<img>')), ['', '-', '—'], true);

        $assessors = User::whereIn('id', $answers->pluck('assessed_by')->filter()->unique())->pluck('name', 'id');

        $items = $essays->values()->map(function (Essay $essay, int $i) use ($answers, $assessors, $isFile, $hasContent) {
            $answer = $answers->get($essay->id);
            $text   = $answer && !$isFile($answer->answer) && $hasContent($answer->answer) ? $answer->answer : null;

            return [
                'number'      => $i + 1,
                'question'    => $essay->question,
                'guide'       => $hasContent($essay->answer) ? $essay->answer : null,
                'guide_label' => $essay->is_essay ? 'Poin / kisi jawaban' : 'Jawaban benar',
                'answer'      => $text,
                'by_file'     => $answer && $isFile($answer->answer),
                'score'       => $answer?->score,
                'assessor'    => $answer?->assessed_by ? $assessors->get($answer->assessed_by) : null,
                'assessed_at' => $answer?->assessed_at,
            ];
        })->all();

        $files = $answers->filter(fn ($a) => $isFile($a->answer))->unique('answer')->map(fn (AnswerEssay $a) => [
            'name'         => basename(str_replace('\\', '/', $a->answer)),
            'download_url' => route('admin.essay_migas.download', $a->id, false),
            // docx butuh perender khusus; selain pdf/gambar cukup diunduh
            'preview_url'  => in_array(AnswerFile::extension($a->answer), ['pdf', 'jpg', 'jpeg', 'png'], true)
                ? route('admin.essay_migas.preview', $a->id, false)
                : null,
        ])->values()->all();

        return [$items, $files];
    }

    /** Unduh file jawaban Essay Migas (disk private). */
    public function downloadEssayMigas(int $answer_essay_id)
    {
        return AnswerFile::download(AnswerEssay::findOrFail($answer_essay_id)->answer);
    }

    /** Pratinjau file jawaban Essay Migas (pdf / gambar) di tab baru. */
    public function previewEssayMigas(int $answer_essay_id)
    {
        return AnswerFile::preview(AnswerEssay::findOrFail($answer_essay_id)->answer);
    }

    public function export(Request $request)
    {
        $request->validate([
            'exam_session_id' => 'required',
        ]);

        $exam_session = ExamSession::with('examPg.classroom', 'examEsai.classroom')
            ->findOrFail($request->exam_session_id);

        $grades = Grade::with(['answers', 'answersEssay', 'exam.classroom', 'exam_session', 'student'])
            ->where('exam_session_id', $exam_session->id)
            ->get()
            ->sortBy(fn($g) => $g->student->no_participant)
            ->values();

        // Sesi bisa berisi PG + Esai sekaligus → ekspor multi-sheet (satu sheet per jenis ujian)
        $filename = 'nilai_' . Str::slug($exam_session->title) . '_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new GradesSessionExport($exam_session, $grades), $filename);
    }

    /**
     * Export PDF laporan nilai.
     *
     * Query param `layout`:
     *   - "ringkas" → view EssayReportPDF, A4 Portrait, download file
     *   - "lebar"   → view ReportPDF, A0 Landscape, inline (default)
     */
    public function exportPdf(Request $request)
    {
        $request->validate([
            'exam_session_id' => 'required',
        ]);

        $layouts = [
            'ringkas' => ['view' => 'EssayReportPDF', 'format' => 'A4', 'orientation' => 'P', 'inline' => false],
            'lebar'   => ['view' => 'ReportPDF',      'format' => 'A0', 'orientation' => 'L', 'inline' => true],
        ];
        $config = $layouts[$request->input('layout', 'lebar')];

        $exam_session = ExamSession::with('examPg.classroom', 'examEsai.classroom')
            ->findOrFail($request->exam_session_id);

        // PDF laporan menampilkan jawaban esai → gunakan ujian esai bila ada.
        // (referenceExam mengutamakan PG sehingga membuat laporan esai kosong.)
        $exam = $exam_session->examEsai ?? $exam_session->referenceExam;

        // Hanya ambil grade milik ujian yang dilaporkan → tidak ada baris ganda per peserta.
        $grades = Grade::with(['student', 'exam.classroom', 'exam_session'])
            ->where('exam_session_id', $request->exam_session_id)
            ->where('exam_id', $exam->id)
            ->get()
            ->sortBy(fn($g) => $g->student->no_participant)
            ->values();

        // Muat relasi soal & jawaban sekali, bukan per-baris (hindari N+1)
        $examQuestions   = $exam->questions()->get();
        $examEssays      = $exam->essays()->get();
        $allAnswers      = Answer::where('exam_id', $exam->id)
            ->where('exam_session_id', $request->exam_session_id)
            ->get()->groupBy('student_id');
        $allEssayAnswers = AnswerEssay::with('essay')
            ->where('exam_id', $exam->id)
            ->where('exam_session_id', $request->exam_session_id)
            ->get()->groupBy('student_id');

        foreach ($grades as $grade) {
            $grade->setRelation('questions',    $examQuestions);
            $grade->setRelation('answers',      $allAnswers->get($grade->student_id, collect()));
            $grade->setRelation('essays',       $examEssays);
            $grade->setRelation('essaysanswers', $allEssayAnswers->get($grade->student_id, collect()));
        }

        $html = View::make($config['view'], compact('grades', 'exam', 'exam_session'))->render();

        $mpdf = new Mpdf([
            'mode'        => 'utf-8',
            'format'      => $config['format'],
            'orientation' => $config['orientation'],
        ]);
        $mpdf->WriteHTML($html);

        $filename = 'grades_' . Str::slug($exam->title) . '_' . now()->format('Ymd_His') . '.pdf';

        if ($config['inline']) {
            return response($mpdf->Output($filename, \Mpdf\Output\Destination::INLINE))
                ->header('Content-Type', 'application/pdf');
        }

        $tempPath = storage_path('app/public/' . $filename);
        $mpdf->Output($tempPath, \Mpdf\Output\Destination::FILE);

        return response()->download($tempPath)->deleteFileAfterSend();
    }
}
