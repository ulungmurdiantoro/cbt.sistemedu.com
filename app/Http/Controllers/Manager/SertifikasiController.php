<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\ExamSession;
use App\Models\ParticipantResult;
use App\Services\DocumentGeneratorService;
use App\Services\SertifikasiReviewService;
use Illuminate\Support\Facades\Auth;

// Pengambil Keputusan (hanya sesi yang ditunjuk admin — middleware pk_session) meninjau
// semua bahan penentuan kelulusan lalu mencentang Verifikasi (tahap 1). Verifikasi tahap 2 &
// Finalisasi dipindah ke Manager Sertifikasi (CertificationManager\SertifikasiController).
class SertifikasiController extends Controller
{
    public function __construct(private SertifikasiReviewService $review) {}

    public function show(ExamSession $examSession)
    {
        return inertia('Manager/Sertifikasi/Show', [
            'exam_session'        => $examSession,
            'rows'                => $this->review->rows($examSession),
            'pengambil_keputusan' => $this->review->pengambilKeputusan($examSession),
        ]);
    }

    /** PK mencentang/membatalkan centang "sudah diverifikasi" untuk satu peserta. */
    public function toggleVerify(ExamSession $examSession, int $studentId)
    {
        $result = ParticipantResult::where('exam_session_id', $examSession->id)
            ->where('student_id', $studentId)
            ->firstOrFail();

        abort_if($result->is_finalized, 422, 'Peserta ini sudah difinalisasi, verifikasi tidak bisa diubah.');

        if ($result->manager_verified_at) {
            // Verifikasi Manager Sertifikasi berdiri di atas verifikasi PK — lepas dulu yang itu.
            abort_if(
                $result->cert_manager_verified_at,
                422,
                'Peserta ini sudah diverifikasi Manager Sertifikasi. Minta Manager Sertifikasi melepas centangnya dulu.'
            );
            $result->update(['manager_verified_at' => null, 'manager_verified_by' => null]);
        } else {
            $result->update(['manager_verified_at' => now(), 'manager_verified_by' => Auth::id()]);
        }

        return back()->with('success', 'Status verifikasi berhasil diperbarui.');
    }

    /** Unduh berita acara Keputusan Sertifikasi satu sesi (setelah difinalisasi). */
    public function downloadKeputusan(ExamSession $examSession, DocumentGeneratorService $generator)
    {
        abort_if(!$examSession->keputusan_number, 404, 'Sesi ini belum difinalisasi.');

        return $this->pdf($generator->generateKeputusanSertifikasi($examSession), 'Keputusan Sertifikasi - ' . $examSession->title);
    }

    /**
     * Preview berita acara sebelum finalisasi — peserta yang sudah Final + yang sudah
     * dicentang Verifikasi PK. Nomor & tanggal masih placeholder.
     */
    public function previewKeputusan(ExamSession $examSession, DocumentGeneratorService $generator)
    {
        return $this->pdf(
            $generator->generateKeputusanSertifikasi($examSession, preview: true),
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
