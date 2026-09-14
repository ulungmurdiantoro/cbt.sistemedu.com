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
    /**
     * Bobot wawancara sebagai pecahan (mis. 30% -> 0.30), murni untuk tampilan
     * "Bobot: X% dari total nilai ujian" di halaman penilaian. Nilai_wawancara
     * yang disimpan di sini SELALU skala 0-100 mentah (rata-rata 4 kriteria) —
     * bobot ini baru diterapkan sekali oleh ResultCalculatorService saat
     * menghitung nilai_akhir. Jangan dikalikan lagi di sini (dulu ada bug
     * double-weighting lewat faktor_wawancara — lihat riwayat commit).
     */
    private function getBobotWawancaraFraction(int $examSessionId): float
    {
        $session     = ExamSession::find($examSessionId);
        $classroomId = $session?->referenceExam?->classroom_id;
        $scheme      = $classroomId
            ? GradingScheme::where('classroom_id', $classroomId)->first()
            : null;

        return (float) ($scheme?->bobot_wawancara ?? 30) / 100;
    }

    public function show(int $exam_session_id)
    {
        $asesor       = auth()->user();
        $exam_session = ExamSession::with('examPg.classroom', 'examEsai.classroom')->findOrFail($exam_session_id);

        $assigned_student_ids = AsesorAssignment::where('user_id', $asesor->id)
            ->where('exam_session_id', $exam_session_id)
            ->pluck('student_id');

        abort_if($assigned_student_ids->isEmpty(), 403, 'Anda tidak ditugaskan pada sesi ini.');

        // Sembunyikan akun peserta yang sudah dinonaktifkan (mis. akun lama hasil
        // re-issue) supaya nama tidak tampil dobel saat menilai.
        $assigned_student_ids = Student::whereIn('id', $assigned_student_ids)
            ->where('is_active', true)
            ->pluck('id');

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
            'bobot'        => $this->getBobotWawancaraFraction($exam_session_id),
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

            // total_nilai = rata-rata 4 kriteria (skala 0-100), SEJAJAR dengan
            // nilai_pg & nilai_esai — bobot_wawancara diterapkan sekali oleh
            // ResultCalculatorService, bukan di sini (dulu dikali faktor_wawancara
            // lagi di sini -> bobot wawancara kepakai dua kali).
            $vals = collect([
                $item['gaya_wawancara'],
                $item['penguasaan_materi'],
                $item['kemampuan_hadapi_pertanyaan'],
                $item['hasil_worksheet'],
            ])->filter(fn($v) => $v !== null);
            $avg = $vals->count() > 0 ? round($vals->avg(), 2) : null;

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
                    'total_nilai'                 => $avg,
                    'catatan'                     => $item['catatan'] ?? null,
                ]
            );
        }

        return back()->with('success', 'Nilai wawancara berhasil disimpan.');
    }
}
