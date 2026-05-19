<?php

namespace App\Http\Controllers\Asesor;

use App\Http\Controllers\Controller;
use App\Models\AsesorAssignment;
use App\Models\ExamSession;
use App\Models\InterviewAssessment;
use App\Models\Student;
use Illuminate\Http\Request;

class InterviewAssessmentController extends Controller
{
    // Bobot wawancara: (jumlah 4 kriteria) × 0.075
    const BOBOT = 0.075;

    public function show(int $exam_session_id)
    {
        $asesor = auth()->user();

        $exam_session = ExamSession::with('exam.classroom')->findOrFail($exam_session_id);

        $assigned_student_ids = AsesorAssignment::where('user_id', $asesor->id)
            ->where('exam_session_id', $exam_session_id)
            ->pluck('student_id');

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
            'bobot'        => self::BOBOT,
        ]);
    }

    public function store(Request $request, int $exam_session_id)
    {
        $request->validate([
            'assessments'                               => 'required|array',
            'assessments.*.student_id'                  => 'required|exists:students,id',
            'assessments.*.gaya_wawancara'              => 'nullable|numeric|min:0|max:100',
            'assessments.*.penguasaan_materi'           => 'nullable|numeric|min:0|max:100',
            'assessments.*.kemampuan_hadapi_pertanyaan' => 'nullable|numeric|min:0|max:100',
            'assessments.*.hasil_worksheet'             => 'nullable|numeric|min:0|max:100',
            'assessments.*.catatan'                     => 'nullable|string|max:1000',
        ]);

        $asesor = auth()->user();

        foreach ($request->assessments as $item) {
            $sum = collect([
                $item['gaya_wawancara'],
                $item['penguasaan_materi'],
                $item['kemampuan_hadapi_pertanyaan'],
                $item['hasil_worksheet'],
            ])->filter(fn($v) => $v !== null)->sum();

            $total = round($sum * self::BOBOT, 2);

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
