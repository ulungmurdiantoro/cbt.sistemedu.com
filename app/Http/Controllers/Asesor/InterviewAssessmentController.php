<?php

namespace App\Http\Controllers\Asesor;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInterviewAssessmentRequest;
use App\Models\AsesorAssignment;
use App\Models\ExamSession;
use App\Models\InterviewAssessment;
use App\Models\Student;

class InterviewAssessmentController extends Controller
{
    private static function bobot(): float
    {
        return (float) config('lsp.bobot_wawancara', 0.075);
    }

    public function show(int $exam_session_id)
    {
        $asesor = auth()->user();

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
            'bobot'        => self::bobot(),
        ]);
    }

    public function store(StoreInterviewAssessmentRequest $request, int $exam_session_id)
    {

        $asesor = auth()->user();

        $assigned_student_ids = AsesorAssignment::where('user_id', $asesor->id)
            ->where('exam_session_id', $exam_session_id)
            ->pluck('student_id')
            ->all();

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

            $total = round($sum * self::bobot(), 2);

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
                    'total_nilai'                 => $total,
                    'catatan'                     => $item['catatan'] ?? null,
                ]
            );
        }

        return back()->with('success', 'Nilai wawancara berhasil disimpan.');
    }
}
