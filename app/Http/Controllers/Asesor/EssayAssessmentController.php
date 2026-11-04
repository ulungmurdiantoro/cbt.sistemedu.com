<?php

namespace App\Http\Controllers\Asesor;

use App\Http\Controllers\Controller;
use App\Models\AnswerEssay;
use App\Models\AsesorAssignment;
use App\Models\Essay;
use App\Models\ExamSession;
use App\Models\Grade;
use App\Models\ParticipantResult;
use App\Support\AnswerFile;
use App\Support\RichText;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EssayAssessmentController extends Controller
{
    public function show(int $exam_session_id)
    {
        $asesor = auth()->user();

        $exam_session = ExamSession::with(['examEsai.classroom', 'examPg.classroom'])->findOrFail($exam_session_id);

        $esaiExamId = $exam_session->exam_id_esai;

        // Peserta yang ditugaskan ke asesor ini di sesi ini
        $assigned_student_ids = AsesorAssignment::where('user_id', $asesor->id)
            ->where('exam_session_id', $exam_session_id)
            ->pluck('student_id');

        abort_if($assigned_student_ids->isEmpty(), 403, 'Anda tidak ditugaskan pada sesi ini.');

        // Sembunyikan akun peserta yang sudah dinonaktifkan (mis. akun lama hasil
        // re-issue) supaya nama tidak tampil dobel saat menilai.
        $assigned_student_ids = \App\Models\Student::whereIn('id', $assigned_student_ids)
            ->where('is_active', true)
            ->pluck('id');

        // Soal-soal esai (urutan resmi = id naik) + kunci/panduan jawaban untuk asesor.
        // Kunci jawaban hanya dikirim bila saklar config('lsp.asesor_show_answer_key') aktif.
        $showKey = (bool) config('lsp.asesor_show_answer_key');

        $essays = Essay::where('exam_id', $esaiExamId)
            ->orderBy('id')
            ->get()
            ->values()
            ->map(fn (Essay $e, int $i) => [
                'id'          => $e->id,
                'number'      => $i + 1,
                'question'    => $e->question,
                'guide'       => $showKey && RichText::hasContent($e->answer) ? $e->answer : null,
                'guide_label' => $e->is_essay ? 'Poin / kisi jawaban' : 'Jawaban benar',
            ]);

        $student_map = \App\Models\Student::whereIn('id', $assigned_student_ids)
            ->orderBy('no_participant')
            ->get()
            ->keyBy('id');

        $attempt_map = ParticipantResult::where('exam_session_id', $exam_session_id)
            ->whereIn('student_id', $assigned_student_ids)
            ->pluck('attempt', 'student_id');

        $students_data = [];
        foreach ($assigned_student_ids as $student_id) {
            $answers = AnswerEssay::where('exam_id', $esaiExamId)
                ->where('exam_session_id', $exam_session_id)
                ->where('student_id', $student_id)
                ->orderBy('essay_id')
                ->get();

            $grade = Grade::where('exam_id', $esaiExamId)
                ->where('exam_session_id', $exam_session_id)
                ->where('student_id', $student_id)
                ->first();

            // Essay Migas: jawabannya path berkas (satu berkas untuk semua soal) — jangan tampil sebagai teks.
            $files = $answers->filter(fn ($a) => AnswerFile::isEssayMigasPath($a->answer))->unique('answer')
                ->map(fn (AnswerEssay $a) => [
                    'name'         => basename(str_replace('\\', '/', $a->answer)),
                    'download_url' => route('asesor.esai.file', [$exam_session_id, $a->id], false),
                    'preview_url'  => AnswerFile::previewable($a->answer)
                        ? route('asesor.esai.file', [$exam_session_id, $a->id, 'pratinjau' => 1], false)
                        : null,
                ])->values();

            $students_data[] = [
                'student_id' => $student_id,
                'student'    => $student_map[$student_id] ?? null,
                'answers'    => $answers->map(fn (AnswerEssay $a) => [
                    'id'       => $a->id,
                    'essay_id' => $a->essay_id,
                    'by_file'  => AnswerFile::isEssayMigasPath($a->answer),
                    'answer'   => !AnswerFile::isEssayMigasPath($a->answer) && RichText::hasContent($a->answer) ? $a->answer : null,
                    'score'    => $a->score,
                ])->values(),
                'files'      => $files,
                'grade'      => $grade,
                'attempt'    => $attempt_map[$student_id] ?? 1,
            ];
        }

        return inertia('Asesor/Esai/Show', [
            'exam_session'  => $exam_session,
            'essays'        => $essays,
            'students_data' => array_values($students_data),
        ]);
    }

    public function store(Request $request, int $exam_session_id)
    {
        $request->validate([
            'scores'              => 'required|array',
            'scores.*.student_id' => 'required|exists:students,id',
            'scores.*.answers'    => 'required|array',
            'scores.*.answers.*.answer_essay_id' => 'required|exists:answer_essays,id',
            'scores.*.answers.*.score'           => 'nullable|numeric|min:0|max:100',
        ]);

        $asesor = auth()->user();
        $exam_session = ExamSession::findOrFail($exam_session_id);
        $esaiExamId = $exam_session->exam_id_esai;

        $assigned_student_ids = AsesorAssignment::where('user_id', $asesor->id)
            ->where('exam_session_id', $exam_session_id)
            ->pluck('student_id')
            ->all();

        foreach ($request->scores as $student_row) {
            abort_unless(
                in_array($student_row['student_id'], $assigned_student_ids),
                403,
                'Anda tidak ditugaskan untuk menilai peserta ini.'
            );
        }

        DB::transaction(function () use ($request, $asesor, $exam_session_id, $esaiExamId) {
            foreach ($request->scores as $student_row) {
                foreach ($student_row['answers'] as $item) {
                    AnswerEssay::where('id', $item['answer_essay_id'])
                        ->where('exam_id', $esaiExamId)
                        ->where('exam_session_id', $exam_session_id)
                        ->where('student_id', $student_row['student_id'])
                        ->update([
                            'score'       => $item['score'],
                            'assessed_by' => $asesor->id,
                            'assessed_at' => now(),
                        ]);
                }

                // Total dihitung ulang dari nilai tersimpan (nilai 0 ikut dihitung),
                // bukan dari angka kiriman browser.
                $avg = self::averageScore($esaiExamId, $exam_session_id, (int) $student_row['student_id']);

                if ($avg !== null) {
                    Grade::where('exam_id', $esaiExamId)
                        ->where('exam_session_id', $exam_session_id)
                        ->where('student_id', $student_row['student_id'])
                        ->update(['grade' => $avg]);
                }
            }
        });

        return back()->with('success', 'Nilai esai berhasil disimpan.');
    }

    /**
     * Berkas jawaban Essay Migas peserta yang ditugaskan ke asesor ini (unduh, atau ?pratinjau=1
     * untuk pdf/gambar di tab baru).
     */
    public function file(Request $request, int $exam_session_id, int $answer_essay_id)
    {
        $exam_session = ExamSession::findOrFail($exam_session_id);

        $answer = AnswerEssay::where('id', $answer_essay_id)
            ->where('exam_session_id', $exam_session_id)
            ->where('exam_id', $exam_session->exam_id_esai)
            ->firstOrFail();

        abort_unless(
            AsesorAssignment::where('user_id', auth()->id())
                ->where('exam_session_id', $exam_session_id)
                ->where('student_id', $answer->student_id)
                ->exists(),
            403,
            'Anda tidak ditugaskan untuk menilai peserta ini.'
        );
        abort_unless(AnswerFile::isEssayMigasPath($answer->answer), 404);

        return $request->boolean('pratinjau')
            ? AnswerFile::preview($answer->answer)
            : AnswerFile::download($answer->answer);
    }

    /** Rata-rata nilai jawaban esai yang sudah dinilai; null bila belum ada yang dinilai. */
    public static function averageScore(?int $examId, int $sessionId, int $studentId): ?float
    {
        $scores = AnswerEssay::where('exam_id', $examId)
            ->where('exam_session_id', $sessionId)
            ->where('student_id', $studentId)
            ->whereNotNull('score')
            ->pluck('score');

        return $scores->isEmpty() ? null : round($scores->avg(), 2);
    }
}
