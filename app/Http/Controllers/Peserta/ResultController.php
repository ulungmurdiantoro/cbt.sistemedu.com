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
    public function downloadSk(int $sessionId, int $studentId, DocumentGeneratorService $generator)
    {
        $participant = Auth::guard('participant')->user();

        $result = ParticipantResult::where('exam_session_id', $sessionId)
            ->where('student_id', $studentId)
            ->where('is_finalized', true)
            ->whereHas('student', fn($q) => $q->where('participant_id', $participant->id))
            ->firstOrFail();

        $pdf      = $generator->generateSk($result, 'with_kop');
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
            ->whereHas('student', fn($q) => $q->where('participant_id', $participant->id))
            ->firstOrFail();

        $pdf      = $generator->generateSertifikat($result, 'with_kop');
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
