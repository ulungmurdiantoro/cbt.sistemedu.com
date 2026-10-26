<?php

namespace App\Http\Controllers\CertificationManager;

use App\Http\Controllers\Controller;
use App\Models\ExamSession;
use App\Models\ParticipantResult;
use App\Services\DocumentGeneratorService;
use App\Services\SertifikasiReviewService;
use Illuminate\Support\Facades\Auth;

// Manager Mutu: halaman Tinjau Sertifikasi yang sama dengan Pengambil Keputusan (semua
// sesi), tetapi centang Verifikasi-nya tahap 2 — hanya untuk peserta yang sudah diverifikasi PK —
// dan dialah yang menekan Finalisasi (nomor SK/SP/Sertifikat + Keputusan Sertifikasi).
class SertifikasiController extends Controller
{
    public function __construct(private SertifikasiReviewService $review) {}

    public function show(ExamSession $examSession)
    {
        return inertia('CertificationManager/Sertifikasi/Show', [
            'exam_session'        => $examSession,
            'rows'                => $this->review->rows($examSession),
            'pengambil_keputusan' => $this->review->pengambilKeputusan($examSession),
        ]);
    }

    public function toggleVerify(ExamSession $examSession, int $studentId)
    {
        $result = ParticipantResult::where('exam_session_id', $examSession->id)
            ->where('student_id', $studentId)
            ->firstOrFail();

        abort_if($result->is_finalized, 422, 'Peserta ini sudah difinalisasi, verifikasi tidak bisa diubah.');

        if ($result->cert_manager_verified_at) {
            $result->update(['cert_manager_verified_at' => null, 'cert_manager_verified_by' => null]);
        } else {
            abort_if(
                !$result->manager_verified_at,
                422,
                'Peserta ini belum diverifikasi Pengambil Keputusan.'
            );
            $result->update(['cert_manager_verified_at' => now(), 'cert_manager_verified_by' => Auth::id()]);
        }

        return back()->with('success', 'Status verifikasi berhasil diperbarui.');
    }

    public function finalize(ExamSession $examSession)
    {
        $count = $this->review->finalize($examSession, Auth::user());

        abort_if(
            $count === 0,
            422,
            'Tidak ada peserta yang sudah diverifikasi Pengambil Keputusan dan Manager Mutu. Centang Verifikasi minimal satu peserta terlebih dahulu.'
        );

        return redirect()->back()->with('success', $count . ' peserta berhasil difinalisasi — sertifikat siap diterbitkan untuk yang LULUS. Peserta yang belum diverifikasi tetap berstatus Draft dan bisa difinalisasi belakangan.');
    }

    public function downloadKeputusan(ExamSession $examSession, DocumentGeneratorService $generator)
    {
        abort_if(!$examSession->keputusan_number, 404, 'Sesi ini belum difinalisasi.');

        return $this->pdf($generator->generateKeputusanSertifikasi($examSession), 'Keputusan Sertifikasi - ' . $examSession->title);
    }

    /** Preview = peserta yang sudah Final + yang akan ikut difinalisasi bila Finalisasi diklik sekarang. */
    public function previewKeputusan(ExamSession $examSession, DocumentGeneratorService $generator)
    {
        return $this->pdf(
            $generator->generateKeputusanSertifikasi($examSession, preview: true, previewNeedsCertManager: true),
            'Preview Keputusan Sertifikasi - ' . $examSession->title
        );
    }

    private function pdf(string $pdf, string $name)
    {
        return response($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . str_replace('"', '', $name) . '.pdf"',
        ]);
    }
}
