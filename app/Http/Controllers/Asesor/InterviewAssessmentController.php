<?php

namespace App\Http\Controllers\Asesor;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInterviewAssessmentRequest;
use App\Models\AsesorAssignment;
use App\Models\ExamSession;
use App\Models\GradingScheme;
use App\Models\InterviewAssessment;
use App\Models\Student;

class InterviewAssessmentController extends Controller
{
    private function getFaktorWawancara(int $examSessionId): float
    {
        $session     = ExamSession::find($examSessionId);
        $classroomId = $session?->referenceExam?->classroom_id;
        $scheme      = $classroomId
            ? GradingScheme::where('classroom_id', $classroomId)->first()
            : null;

        return $scheme?->faktor_wawancara ?? 0.075;
    }

    public function show(int $exam_session_id)
    {
        $asesor       = auth()->user();
        $exam_session = ExamSession::with('examPg.classroom', 'examEsai.classroom')->findOrFail($exam_session_id);

        $assigned_student_ids = AsesorAssignment::where('user_id', $asesor->id)
            ->where('exam_session_id', $exam_session_id)
            ->pluck('student_id');

        abort_if($assigned_student_ids->isEmpty(), 403, 'Anda tidak ditugaskan pada sesi ini.');

        $students = Student::whereIn('id', $assigned_student_ids)
            ->orderBy('no_participant')
            ->get();

        $assessments = InterviewAssessment::where('exam_session_id', $exam_session_id)
            ->whereIn('student_id', $assigned_student_ids)
            ->get()
            ->keyBy('student_id');

        return inertia('Asesor/Wawancara/Show', [
            'exam_session' => $exam_session,
            'students'     => $students,
            'assessments'  => $assessments,
            'bobot'        => $this->getFaktorWawancara($exam_session_id),
        ]);
    }

    public function store(StoreInterviewAssessmentRequest $request, int $exam_session_id)
    {
        $asesor = auth()->user();

        $assigned_student_ids = AsesorAssignment::where('user_id', $asesor->id)
            ->where('exam_session_id', $exam_session_id)
            ->pluck('student_id')
            ->all();

        $faktor = $this->getFaktorWawancara($exam_session_id);

        foreach ($request->assessments as $item) {
            abort_unless(
                in_array($item['student_id'], $assigned_student_ids),
                403,
                'Anda tidak ditugaskan untuk menilai peserta ini.'
            );

            $sum = collect([
                $item['gaya_wawancara'],
                $item['penguasaan_materi'],
                $item['kemampuan_hadapi_pertanyaan'],
                $item['hasil_worksheet'],
            ])->filter(fn($v) => $v !== null)->sum();

            InterviewAssessment::updateOrCreate(
                [
                    'exam_session_id' => $exam_session_id,
                    'student_id'      => $item['student_id'],
                ],
                [
                    'asesor_id'                   => $asesor->id,
                    'gaya_wawancara'              => $item['gaya_wawancara'],
                    'penguasaan_materi'           => $item['penguasaan_materi'],
                    'kemampuan_hadapi_pertanyaan' => $item['kemampuan_hadapi_pertanyaan'],
                    'hasil_worksheet'             => $item['hasil_worksheet'],
                    'total_nilai'                 => round($sum * $faktor, 2),
                    'catatan'                     => $item['catatan'] ?? null,
                ]
            );
        }

        return back()->with('success', 'Nilai wawancara berhasil disimpan.');
    }
}
