<?php

namespace App\Http\Controllers\Peserta;

use App\Http\Controllers\Controller;
use App\Models\ExamSession;
use App\Models\ParticipantResult;
use App\Services\DocumentGeneratorService;
use App\Services\RemidiService;
use Illuminate\Support\Facades\Auth;

class ResultController extends Controller
{
    /** SP (tahap 1) — bisa diunduh peserta begitu admin mengirim SP, sebelum SK/Sertifikat terbit. */
    public function downloadSp(int $sessionId, int $studentId, DocumentGeneratorService $generator)
    {
        $participant = Auth::guard('participant')->user();

        $result = ParticipantResult::where('exam_session_id', $sessionId)
            ->where('student_id', $studentId)
            ->where('is_finalized', true)
            ->whereNotNull('sp_distributed_at')
            ->whereHas('student', fn($q) => $q->where('participant_id', $participant->id))
            ->firstOrFail();

        $pdf      = $generator->spPdf($result);
        $filename = 'SP_' . $result->student?->no_participant . '.pdf';

        return response($pdf, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', "inline; filename=\"{$filename}\"");
    }

    public function downloadSk(int $sessionId, int $studentId, DocumentGeneratorService $generator)
    {
        $participant = Auth::guard('participant')->user();

        $result = ParticipantResult::where('exam_session_id', $sessionId)
            ->where('student_id', $studentId)
            ->where('is_finalized', true)
            // SK & Sertifikat baru boleh diunduh peserta setelah admin benar-benar
            // mengirimkannya (tahap 2) — bukan langsung begitu difinalisasi, supaya
            // masa koreksi SP (tahap 1) tidak bisa dilewati lewat URL langsung.
            ->whereNotNull('distributed_at')
            ->whereHas('student', fn($q) => $q->where('participant_id', $participant->id))
            ->firstOrFail();

        // FR.AK.14 hanya berlaku untuk peserta yang LULUS (surat pernyataan
        // pemegang sertifikat) — yang tidak lulus tetap bebas unduh SK-nya.
        if ($result->keputusan === 'LULUS') {
            abort_if($result->materai_status !== 'stamped', 422, 'Tanda tangani dan selesaikan e-meterai FR.AK.14 terlebih dahulu.');
        }

        // Pakai varian yang sama seperti yang benar-benar dikirim admin (with_kan),
        // supaya file yang diunduh ulang peserta konsisten dengan yang diterima via email.
        $pdf      = $generator->skPdf($result, (bool) $result->with_kan);
        $filename = 'SK_' . $result->student?->no_participant . '.pdf';

        return response($pdf, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', "inline; filename=\"{$filename}\"");
    }

    public function downloadSertifikat(int $sessionId, int $studentId, DocumentGeneratorService $generator)
    {
        $participant = Auth::guard('participant')->user();

        $result = ParticipantResult::where('exam_session_id', $sessionId)
            ->where('student_id', $studentId)
            ->where('is_finalized', true)
            ->where('keputusan', 'LULUS')
            ->whereNotNull('distributed_at')
            ->whereHas('student', fn($q) => $q->where('participant_id', $participant->id))
            ->firstOrFail();

        abort_if($result->materai_status !== 'stamped', 422, 'Tanda tangani dan selesaikan e-meterai FR.AK.14 terlebih dahulu.');

        // Pakai varian yang sama seperti yang benar-benar dikirim admin (with_kan),
        // supaya file yang diunduh ulang peserta konsisten dengan yang diterima via email.
        $pdf      = $generator->sertifikatPdf($result, (bool) $result->with_kan);
        $filename = 'Sertifikat_' . $result->student?->no_participant . '.pdf';

        return response($pdf, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', "inline; filename=\"{$filename}\"");
    }

    public function startRemidi(int $sessionId, RemidiService $remidiService)
    {
        $participant = Auth::guard('participant')->user();

        $session = ExamSession::findOrFail($sessionId);

        abort_unless(
            $session->remidi_start_at && now()->between($session->remidi_start_at, $session->remidi_end_at ?? now()->addYear()),
            403, 'Window remidi belum/sudah berakhir.'
        );

        // Temukan student milik participant di sesi ini
        $student = $participant->students()
            ->whereHas('examGroups', fn($q) => $q->where('exam_session_id', $sessionId))
            ->firstOrFail();

        $remidiService->startRemidi($session, $student);

        return redirect()->route('peserta.dashboard')
            ->with('success', 'Remidi berhasil diaktifkan. Silakan login ke halaman ujian.');
    }
}
