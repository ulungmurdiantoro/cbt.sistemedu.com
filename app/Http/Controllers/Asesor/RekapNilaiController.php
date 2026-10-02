<?php

namespace App\Http\Controllers\Asesor;

use App\Http\Controllers\Controller;
use App\Models\AnswerEssay;
use App\Models\AsesorAssignment;
use App\Models\AssessmentApplication;
use App\Models\ExamSession;
use App\Models\GradingScheme;
use App\Models\Student;
use App\Services\ResultCalculatorService;

/**
 * Rekap Nilai (baca saja) untuk asesor — versi ringkas Admin\ResultController@show, hanya
 * peserta yang ditugaskan ke asesor ini. Nilai dihitung tanpa disimpan
 * (ResultCalculatorService::calculateForSession); keputusan sebelum finalisasi
 * Pengambil Keputusan ditampilkan sebagai sementara.
 */
class RekapNilaiController extends Controller
{
    public function __construct(
        private ResultCalculatorService $calculator,
    ) {}

    public function show(int $exam_session_id)
    {
        $assigned = AsesorAssignment::where('user_id', auth()->id())
            ->where('exam_session_id', $exam_session_id)
            ->pluck('student_id');

        abort_if($assigned->isEmpty(), 403, 'Anda tidak ditugaskan pada sesi ini.');

        $examSession = ExamSession::with('examPg.classroom', 'examEsai.classroom')->findOrFail($exam_session_id);

        $results    = collect($this->calculator->calculateForSession($examSession, $assigned));
        $studentIds = $results->pluck('student_id');
        $students   = Student::whereIn('id', $studentIds)->get()->keyBy('id');

        $rekomendasi = AssessmentApplication::where('exam_session_id', $exam_session_id)
            ->whereIn('student_id', $studentIds)
            ->pluck('asesor_rekomendasi', 'student_id');

        // Jawaban esai yang belum diberi nilai — supaya rekap juga jadi pengingat tugas menilai.
        $esaiBelumDinilai = $examSession->exam_id_esai
            ? AnswerEssay::where('exam_session_id', $exam_session_id)
                ->where('exam_id', $examSession->exam_id_esai)
                ->whereIn('student_id', $studentIds)
                ->whereNull('score')
                ->selectRaw('student_id, COUNT(*) as total')
                ->groupBy('student_id')
                ->pluck('total', 'student_id')
            : collect();

        $rows = $results->map(fn ($r) => [
            'student_id'         => $r->student_id,
            'no_participant'     => $students->get($r->student_id)?->no_participant,
            'name'               => $students->get($r->student_id)?->name,
            'nilai_pg'           => $r->nilai_pg,
            'nilai_esai'         => $r->nilai_esai,
            'esai_belum_dinilai' => (int) ($esaiBelumDinilai[$r->student_id] ?? 0),
            'nilai_wawancara'    => $r->nilai_wawancara,
            'nilai_akhir'        => $r->nilai_akhir,
            'keputusan'          => $r->keputusan,
            'is_finalized'       => (bool) $r->is_finalized,
            'rekomendasi'        => $rekomendasi[$r->student_id] ?? null,
        ])->sortBy('no_participant')->values();

        $classroomId = $examSession->referenceExam?->classroom_id;
        $scheme      = $classroomId ? GradingScheme::where('classroom_id', $classroomId)->first() : null;

        return inertia('Asesor/RekapNilai/Show', [
            'exam_session' => $examSession,
            'rows'         => $rows,
            // Default sama dengan ResultCalculatorService bila skema penilaian belum diatur.
            'scheme'       => [
                'bobot_pg'        => (float) ($scheme?->bobot_pg ?? 0),
                'bobot_esai'      => (float) ($scheme?->bobot_esai ?? 0),
                'bobot_wawancara' => (float) ($scheme?->bobot_wawancara ?? 0),
                'nilai_kelulusan' => (float) ($scheme?->nilai_kelulusan ?? 70),
            ],
        ]);
    }
}
