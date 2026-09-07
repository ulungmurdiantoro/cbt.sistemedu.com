<?php

namespace App\Http\Controllers\Peserta;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Peserta\Concerns\StoresSignatures;
use App\Jobs\StampFrAk14Job;
use App\Models\ParticipantResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

// FR.AK.14 — Surat Pernyataan Pemegang Sertifikat. Peserta wajib
// menandatangani ini (lalu e-meterainya dibubuhkan otomatis, gratis) sebelum
// boleh mengunduh SK & Sertifikat — lihat gate di ResultController.
class FrAk14Controller extends Controller
{
    use StoresSignatures;

    private function findResult(int $sessionId, int $studentId): ParticipantResult
    {
        $participant = Auth::guard('participant')->user();

        return ParticipantResult::where('exam_session_id', $sessionId)
            ->where('student_id', $studentId)
            ->where('is_finalized', true)
            ->where('keputusan', 'LULUS')
            ->whereHas('student', fn($q) => $q->where('participant_id', $participant->id))
            ->firstOrFail();
    }

    public function show(int $sessionId, int $studentId)
    {
        $result = $this->findResult($sessionId, $studentId);
        $result->load(['student.participant', 'examSession.examPg.classroom', 'examSession.examEsai.classroom']);

        $classroom = $result->examSession?->referenceExam?->classroom;

        return inertia('Peserta/Hasil/FrAk14', [
            'sessionId'    => $sessionId,
            'studentId'    => $studentId,
            'namaPeserta'  => $result->student?->name,
            'nik'          => $result->student?->participant?->nik,
            'namaSkema'    => $classroom?->title,
            'noSertifikat' => $result->sertifikat_number,
            'result'       => $result->only([
                'fr_ak_14_signed_at', 'materai_status', 'materai_stamped_at', 'materai_failure_reason',
            ]),
        ]);
    }

    public function sign(Request $request, int $sessionId, int $studentId)
    {
        $result = $this->findResult($sessionId, $studentId);
        abort_if($result->fr_ak_14_signed_at, 422, 'FR.AK.14 sudah ditandatangani.');

        $path = $this->storeSignature($request, 'ttd_frak14_' . $result->id);

        $result->update([
            'fr_ak_14_signature_path' => $path,
            'fr_ak_14_signed_at'      => now(),
            'materai_status'          => 'processing',
        ]);

        StampFrAk14Job::dispatch($result->id);

        return back()->with('success', 'FR.AK.14 berhasil ditandatangani. Materai elektronik sedang diproses otomatis.');
    }

    // Sajikan file tanda tangan secara privat (hanya pemilik hasil)
    public function serveSignature(int $sessionId, int $studentId)
    {
        $result = $this->findResult($sessionId, $studentId);

        $path = $result->fr_ak_14_signature_path;
        abort_if(!$path || !Storage::disk('private')->exists($path), 404);

        return response()->file(Storage::disk('private')->path($path), [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma'        => 'no-cache',
        ]);
    }

    public function retry(int $sessionId, int $studentId)
    {
        $result = $this->findResult($sessionId, $studentId);
        abort_unless($result->materai_status === 'failed', 422, 'Materai tidak dalam status gagal.');

        $result->update(['materai_status' => 'processing', 'materai_failure_reason' => null]);
        StampFrAk14Job::dispatch($result->id);

        return back()->with('success', 'Pembubuhan materai dicoba ulang.');
    }
}
