<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\AsesorAssignment;
use App\Models\AssessmentApplication;
use App\Models\Classroom;
use App\Models\ExamSession;
use App\Models\ParticipantResult;
use App\Models\Student;
use App\Services\NumberingService;
use App\Services\ResultCalculatorService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

// Manager Sertifikasi meninjau semua bahan penentuan kelulusan sebelum
// finalisasi: kelengkapan dokumen (FR.APL.01), kelayakan awal (FR.APL.03),
// laporan asesmen (nama asesor + rekomendasi Kompeten/Belum Kompeten), dan
// nilai PG/Esai/Wawancara/Akhir. Wewenang finalisasi dipindah ke sini dari
// Admin\ResultController.
class SertifikasiController extends Controller
{
    public function __construct(
        private ResultCalculatorService $calculator,
        private NumberingService $numbering,
    ) {}

    public function show(ExamSession $examSession)
    {
        $examSession->load(['examPg.classroom', 'examEsai.classroom']);

        $results = $this->calculator->recalcForSession($examSession);
        $studentIds = collect($results)->pluck('student_id');

        $students = Student::whereIn('id', $studentIds)->with('participant')->get()->keyBy('id');

        $applications = AssessmentApplication::whereIn('student_id', $studentIds)
            ->where('exam_session_id', $examSession->id)
            ->with(['documents', 'classroom.documentRequirements', 'initialAssessment'])
            ->get()
            ->keyBy('student_id');

        $assignments = AsesorAssignment::where('exam_session_id', $examSession->id)
            ->whereIn('student_id', $studentIds)
            ->with('asesor:id,name')
            ->get()
            ->keyBy('student_id');

        $rows = collect($results)->map(function ($r) use ($students, $applications, $assignments) {
            $student     = $students->get($r->student_id);
            $application = $applications->get($r->student_id);
            $assignment  = $assignments->get($r->student_id);

            $totalDoc    = $application?->classroom?->documentRequirements?->count() ?? 0;
            $verifiedDoc = $application?->documents?->where('status', 'verified')->count() ?? 0;

            $assessment = $application?->initialAssessment;

            return [
                'result_id'          => $r->id,
                'student_id'         => $r->student_id,
                'no_participant'     => $student?->no_participant,
                'name'               => $student?->name,

                // FR.APL.01 — kelengkapan dokumen
                'apl01_total'        => $totalDoc,
                'apl01_verified'     => $verifiedDoc,
                'apl01_complete'     => $totalDoc > 0 && $verifiedDoc === $totalDoc,

                // FR.APL.03 — kelayakan awal
                'apl03_done'         => (bool) $assessment,
                'apl03_eligible'     => $assessment?->is_eligible,
                'apl03_score'        => $assessment?->total_score,

                // Laporan Asesmen — asesor & rekomendasi
                'asesor_name'        => $assignment?->asesor?->name,
                'asesor_verified_at' => $application?->asesor_verified_at,
                'asesor_rekomendasi' => $application?->asesor_rekomendasi,

                // Nilai
                'nilai_pg'        => $r->nilai_pg,
                'nilai_esai'      => $r->nilai_esai,
                'nilai_wawancara' => $r->nilai_wawancara,
                'nilai_akhir'     => $r->nilai_akhir,
                'keputusan'       => $r->keputusan,
                'is_finalized'    => $r->is_finalized,
            ];
        })->sortBy('name')->values();

        return inertia('Manager/Sertifikasi/Show', [
            'exam_session' => $examSession,
            'rows'         => $rows,
        ]);
    }

    public function finalize(ExamSession $examSession)
    {
        $examSession->load(['examPg.classroom', 'examEsai.classroom']);

        $classroomId = $examSession->referenceExam?->classroom_id;
        $classroom   = $classroomId ? Classroom::find($classroomId) : null;

        $results = ParticipantResult::where('exam_session_id', $examSession->id)
            ->where('is_finalized', false)
            ->get();

        DB::transaction(function () use ($results, $classroom, $classroomId, $examSession) {
            foreach ($results as $result) {
                $skNum = $this->numbering->nextSkNumber();
                $spNum = $this->numbering->nextSpNumber();

                $sertifikatNum = null;
                if ($result->keputusan === 'LULUS' && $classroom) {
                    $sertifikatNum = $this->numbering->nextSertifikatNumber(
                        $classroom->kode_skema ?? '',
                        $examSession->kode_batch ?? '',
                        $classroomId
                    );
                }

                $result->update([
                    'is_finalized'      => true,
                    'finalized_at'      => now(),
                    'finalized_by'      => Auth::id(),
                    'sk_number'         => $skNum,
                    'sp_number'         => $spNum,
                    'sertifikat_number' => $sertifikatNum,
                    'valid_until'       => now()->addYears(config('lsp.sertifikat_valid_years', 3)),
                ]);
            }
        });

        return redirect()->back()->with('success', 'Finalisasi nilai berhasil — sertifikat siap diterbitkan untuk peserta yang LULUS.');
    }
}
