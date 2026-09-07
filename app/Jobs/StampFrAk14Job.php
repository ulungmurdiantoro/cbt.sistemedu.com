<?php

namespace App\Jobs;

use App\Models\ParticipantResult;
use App\Services\DocumentGeneratorService;
use App\Services\PeruriService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

/**
 * Bubuhkan e-meterai Peruri ke FR.AK.14 setelah peserta menandatangani surat
 * pernyataan pemegang sertifikat. Download SK & Sertifikat digembok sampai
 * job ini sukses (materai_status = 'stamped') — lihat ResultController.
 */
class StampFrAk14Job implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(
        public readonly int $participantResultId,
    ) {}

    public function handle(DocumentGeneratorService $generator, PeruriService $peruri): void
    {
        $result = ParticipantResult::find($this->participantResultId);

        if (!$result || $result->materai_status === 'stamped') {
            return; // idempotent
        }

        try {
            $pdf = $generator->generateFrAk14($result);

            $sn = $peruri->generateSerialNumber([
                'nodoc'    => $result->sertifikat_number ?? (string) $result->id,
                'tgldoc'   => now()->format('Y-m-d'),
                'namafile' => 'FR-AK-14-' . ($result->sertifikat_number ?? $result->id) . '.pdf',
            ]);

            $stamped = $peruri->stamp($pdf, $sn['qrBase64'], [
                'refToken'         => $sn['sn'],
                'reason'           => 'Surat Pernyataan Pemegang Sertifikat FR.AK.14',
                // Posisi QR dekat blok TTD "Yang menyatakan," — dikalibrasi
                // lewat https://e-form.peruri.co.id/pdfviewer/ saat testing.
                'visLLX'           => 380,
                'visLLY'           => 60,
                'visURX'           => 480,
                'visURY'           => 160,
                'visSignaturePage' => 2,
            ]);

            $path = "materai/fr-ak-14/{$result->id}.pdf";
            Storage::disk('private')->put($path, $stamped);

            $result->update([
                'materai_status'         => 'stamped',
                'materai_stamped_at'     => now(),
                'materai_document_path'  => $path,
                'materai_failure_reason' => null,
            ]);
        } catch (\Throwable $e) {
            $result->update([
                'materai_status'         => 'failed',
                'materai_failure_reason' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
