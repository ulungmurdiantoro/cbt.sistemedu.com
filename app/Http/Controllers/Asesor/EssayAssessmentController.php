<?php

namespace App\Http\Controllers\Asesor;

use App\Http\Controllers\Controller;
use App\Models\AnswerEssay;
use App\Models\AsesorAssignment;
use App\Models\Essay;
use App\Models\ExamSession;
use App\Models\Grade;
use App\Models\ParticipantResult;
use Illuminate\Http\Request;

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

        // Soal-soal esai
        $essays = Essay::where('exam_id', $esaiExamId)
            ->orderBy('id')
            ->get();

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
                ->orderBy('essay_order')
                ->get();

            $grade = Grade::where('exam_id', $esaiExamId)
                ->where('exam_session_id', $exam_session_id)
                ->where('student_id', $student_id)
                ->first();

            $students_data[] = [
                'student_id' => $student_id,
                'student'    => $student_map[$student_id] ?? null,
                'answers'    => $answers,
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

        foreach ($request->scores as $student_row) {
            $scores = collect($student_row['answers'])->pluck('score')->filter()->values();
            $avg = $scores->count() > 0 ? round($scores->avg(), 2) : null;

            foreach ($student_row['answers'] as $item) {
                AnswerEssay::where('id', $item['answer_essay_id'])
                    ->where('exam_session_id', $exam_session_id)
                    ->where('student_id', $student_row['student_id'])
                    ->update([
                        'score'       => $item['score'],
                        'assessed_by' => $asesor->id,
                        'assessed_at' => now(),
                    ]);
            }

            if ($avg !== null) {
                Grade::where('exam_id', $esaiExamId)
                    ->where('exam_session_id', $exam_session_id)
                    ->where('student_id', $student_row['student_id'])
                    ->update(['grade' => $avg]);
            }
        }

        return back()->with('success', 'Nilai esai berhasil disimpan.');
    }
}
