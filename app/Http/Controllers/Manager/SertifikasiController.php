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

// Pengambil Keputusan meninjau semua bahan penentuan kelulusan sebelum
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

        $results = \Illuminate\Database\Eloquent\Collection::make($this->calculator->recalcForSession($examSession));
        $results->load('manager:id,name');
        $studentIds = $results->pluck('student_id');

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

            // FR.APL.01 dihitung dari dokumen WAJIB saja — persyaratan opsional tidak
            // boleh menghalangi status "Lengkap".
            $requiredReqIds = $application?->classroom?->documentRequirements
                ?->where('is_required', true)->pluck('id') ?? collect();
            $totalDoc    = $requiredReqIds->count();
            $verifiedDoc = $application?->documents
                ?->whereIn('classroom_document_requirement_id', $requiredReqIds)
                ->where('status', 'verified')->count() ?? 0;

            $assessment = $application?->initialAssessment;

            return [
                'result_id'          => $r->id,
                'student_id'         => $r->student_id,
                'application_id'     => $application?->id,
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
                'asesor_id'          => $assignment?->asesor?->id,
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

                // Verifikasi Pengambil Keputusan — syarat wajib sebelum finalisasi
                'manager_verified_at' => $r->manager_verified_at,
                'manager_verified_by' => $r->manager?->name,
            ];
        })->sortBy('name')->values();

        return inertia('Manager/Sertifikasi/Show', [
            'exam_session' => $examSession,
            'rows'         => $rows,
        ]);
    }

    /** Manager mencentang/membatalkan centang "sudah diverifikasi" untuk satu peserta. */
    public function toggleVerify(ExamSession $examSession, int $studentId)
    {
        $result = ParticipantResult::where('exam_session_id', $examSession->id)
            ->where('student_id', $studentId)
            ->firstOrFail();

        abort_if($result->is_finalized, 422, 'Peserta ini sudah difinalisasi, verifikasi tidak bisa diubah.');

        if ($result->manager_verified_at) {
            $result->update(['manager_verified_at' => null, 'manager_verified_by' => null]);
        } else {
            $result->update(['manager_verified_at' => now(), 'manager_verified_by' => Auth::id()]);
        }

        return back()->with('success', 'Status verifikasi berhasil diperbarui.');
    }

    public function finalize(ExamSession $examSession)
    {
        $examSession->load(['examPg.classroom', 'examEsai.classroom']);

        $classroomId = $examSession->referenceExam?->classroom_id;
        $classroom   = $classroomId ? Classroom::find($classroomId) : null;

        $results = ParticipantResult::where('exam_session_id', $examSession->id)
            ->where('is_finalized', false)
            ->get();

        abort_if(
            $results->isEmpty(),
            422,
            'Tidak ada peserta yang perlu difinalisasi.'
        );
        abort_if(
            $results->whereNull('manager_verified_at')->isNotEmpty(),
            422,
            'Semua peserta harus diverifikasi Pengambil Keputusan sebelum finalisasi.'
        );

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

            // Satu nomor Keputusan Sertifikasi per sesi, diterbitkan sekali saja.
            if (!$examSession->keputusan_number) {
                $examSession->update([
                    'keputusan_number'    => $this->numbering->nextKeputusanNumber(),
                    'keputusan_issued_at' => now(),
                    'keputusan_issued_by' => Auth::id(),
                ]);
            }
        });

        return redirect()->back()->with('success', 'Finalisasi nilai berhasil — sertifikat siap diterbitkan untuk peserta yang LULUS.');
    }

    /** Unduh berita acara Keputusan Sertifikasi satu sesi (setelah difinalisasi). */
    public function downloadKeputusan(ExamSession $examSession, \App\Services\DocumentGeneratorService $generator)
    {
        abort_if(!$examSession->keputusan_number, 404, 'Sesi ini belum difinalisasi.');

        $pdf = $generator->generateKeputusanSertifikasi($examSession);

        return response($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Keputusan Sertifikasi - ' . str_replace('"', '', $examSession->title) . '.pdf"',
        ]);
    }
}
