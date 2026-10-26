<?php

namespace App\Services;

use App\Models\AsesorAssignment;
use App\Models\AssessmentApplication;
use App\Models\Classroom;
use App\Models\ExamSession;
use App\Models\ParticipantResult;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Halaman Tinjau Sertifikasi dipakai dua portal dengan isi yang sama:
 *   1. Pengambil Keputusan (portal /manager, hanya sesi yang ditunjuk) — centang Verifikasi tahap 1.
 *   2. Manager Sertifikasi (portal /manager-sertifikasi, semua sesi) — centang Verifikasi tahap 2
 *      (hanya peserta yang sudah diverifikasi PK), lalu Finalisasi.
 * Bahan peninjauan: kelengkapan dokumen (FR.APL.01), kelayakan awal (FR.APL.03), laporan asesmen
 * (nama asesor + rekomendasi K/BK), nilai PG/Esai/Wawancara/Akhir.
 */
class SertifikasiReviewService
{
    public function __construct(
        private ResultCalculatorService $calculator,
        private NumberingService $numbering,
    ) {}

    /** Satu baris per peserta terdaftar, urut No. Peserta. */
    public function rows(ExamSession $examSession): Collection
    {
        $examSession->loadMissing(['examPg.classroom', 'examEsai.classroom']);

        $results = EloquentCollection::make($this->calculator->recalcForSession($examSession));
        $results->load('manager:id,name', 'certManager:id,name');
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

        return $results->map(function ($r) use ($students, $applications, $assignments) {
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

                // Verifikasi tahap 1 (Pengambil Keputusan) & tahap 2 (Manager Sertifikasi) —
                // keduanya syarat finalisasi.
                'manager_verified_at'      => $r->manager_verified_at,
                'manager_verified_by'      => $r->manager?->name,
                'cert_manager_verified_at' => $r->cert_manager_verified_at,
                'cert_manager_verified_by' => $r->certManager?->name,
            ];
        })->sortBy('no_participant')->values();
    }

    /** Pengambil Keputusan sesi + status TTD-nya (TTD ini yang tercetak di Keputusan Sertifikasi). */
    public function pengambilKeputusan(ExamSession $examSession): ?array
    {
        $user = $examSession->pengambilKeputusan;

        return $user ? [
            'id'            => $user->id,
            'name'          => $user->name,
            'has_signature' => (bool) $user->signature_path,
        ] : null;
    }

    /**
     * Finalisasi peserta yang sudah diverifikasi Pengambil Keputusan DAN Manager Sertifikasi.
     * Peserta lain (mis. berhalangan hadir & ikut batch susulan) tetap Draft dan bisa
     * difinalisasi belakangan. Mengembalikan jumlah peserta yang difinalisasi.
     */
    public function finalize(ExamSession $examSession, User $finalizer): int
    {
        $examSession->loadMissing(['examPg.classroom', 'examEsai.classroom']);

        $classroomId = $examSession->referenceExam?->classroom_id;
        $classroom   = $classroomId ? Classroom::find($classroomId) : null;

        $results = ParticipantResult::where('exam_session_id', $examSession->id)
            ->where('is_finalized', false)
            ->whereNotNull('manager_verified_at')
            ->whereNotNull('cert_manager_verified_at')
            // Abaikan baris yatim (akun lama hasil re-issue/merge duplikat, peserta
            // yang sudah dipindah/dikeluarkan dari sesi) — kalau tidak, baris
            // verifikasi lama bisa ikut menghabiskan nomor SK/SP resmi untuk
            // orang yang tidak ada di daftar Tinjau Sertifikasi.
            ->enrolled()
            ->with('student:id,no_participant')
            ->get()
            // Nomor SK/SP dibagikan urut No. Peserta, bukan urutan baris di DB.
            ->sortBy(fn ($r) => $r->student->no_participant)
            ->values();

        if ($results->isEmpty()) {
            return 0;
        }

        DB::transaction(function () use ($results, $classroom, $classroomId, $examSession, $finalizer) {
            foreach ($results as $result) {
                // Peserta remidi masih memegang nomor dari finalisasi pertama —
                // pakai ulang supaya nomor lama tidak hangus dan urutan tidak loncat.
                $skNum = $result->sk_number ?: $this->numbering->nextSkNumber();
                $spNum = $result->sp_number ?: $this->numbering->nextSpNumber();

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
                    'finalized_by'      => $finalizer->id,
                    'sk_number'         => $skNum,
                    'sp_number'         => $spNum,
                    'sertifikat_number' => $sertifikatNum,
                    // valid_until SENGAJA belum dihitung di sini — Certification date &
                    // Valid until pada Sertifikat mengikuti tanggal SK/Sertifikat benar-benar
                    // DIKIRIM (distributed_at), bukan tanggal finalisasi, karena bisa ada jeda
                    // waktu antara finalisasi & pengiriman resmi (tahap "Kirim SK & Sertifikat").
                    // Dihitung di SendResultMailJob saat itu terjadi.
                ]);
            }

            // Satu nomor Keputusan Sertifikasi per sesi, diterbitkan sekali saja. Berita acara
            // itu atas nama & TTD Pengambil Keputusan sesi (bukan Manager Sertifikasi yang
            // menekan Finalisasi) — dibekukan di keputusan_issued_by supaya penunjukan PK yang
            // diganti belakangan tidak mengubah dokumen yang sudah terbit.
            if (!$examSession->keputusan_number) {
                $examSession->update([
                    'keputusan_number'    => $this->numbering->nextKeputusanNumber(),
                    'keputusan_issued_at' => now(),
                    'keputusan_issued_by' => $examSession->pengambil_keputusan_id ?? $results->first()->manager_verified_by,
                ]);
            }
        });

        return $results->count();
    }
}
