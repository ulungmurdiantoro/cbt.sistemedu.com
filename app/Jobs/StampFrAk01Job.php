<?php

namespace App\Jobs;

use App\Models\AssessmentApplication;
use App\Services\DocumentGeneratorService;
use App\Services\PeruriService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

/**
 * Bubuhkan e-meterai Peruri ke FR.AK.01 setelah peserta menandatangani pakta
 * integritas. Dipicu otomatis (tidak ada langkah bayar) dari
 * ApplicationController::savePakta().
 */
class StampFrAk01Job implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(
        public readonly int $applicationId,
    ) {}

    public function handle(DocumentGeneratorService $generator, PeruriService $peruri): void
    {
        $application = AssessmentApplication::find($this->applicationId);

        if (!$application || $application->materai_status === 'stamped') {
            return; // idempotent
        }

        try {
            $pdf = $generator->generateFrAk01($application);

            $sn = $peruri->generateSerialNumber([
                'nodoc'    => $application->code,
                'tgldoc'   => now()->format('Y-m-d'),
                'namafile' => 'FR-AK-01-' . $application->code . '.pdf',
            ]);

            // Tabel TTD (LSP / Asesor / Asesi) selalu di HALAMAN TERAKHIR dokumen
            // (setelah page-break), tapi nomornya bisa 2 atau 3 tergantung panjang
            // isi klausul per peserta — jadi target halamannya dihitung dinamis,
            // jangan dipatok angka.
            $lastPage = $this->pdfPageCount($pdf);

            $stamped = $peruri->stamp($pdf, $sn['qrBase64'], [
                'refToken'         => $sn['sn'],
                'reason'           => 'Persetujuan Asesmen FR.AK.01',
                // Meterai ditaruh DI SAMPING KIRI kotak TTD Asesi (baris paling
                // bawah tabel TTD), tidak menimpa gambar tanda tangan.
                // Koordinat PDF (origin kiri-bawah). Iterasi 3: dari 470 (kelewat
                // rendah) dinaikkan ~30pt supaya pas sejajar baris Asesi.
                'visLLX'           => 150,
                'visLLY'           => 500,
                'visURX'           => 235,
                'visURY'           => 585,
                'visSignaturePage' => $lastPage,
            ]);

            $path = "materai/fr-ak-01/{$application->id}.pdf";
            Storage::disk('private')->put($path, $stamped);

            $application->update([
                'materai_status'         => 'stamped',
                'materai_stamped_at'     => now(),
                'materai_document_path'  => $path,
                'materai_failure_reason' => null,
            ]);
        } catch (\Throwable $e) {
            $application->update([
                'materai_status'         => 'failed',
                'materai_failure_reason' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /** Hitung jumlah halaman dari bytes PDF hasil mPDF. */
    private function pdfPageCount(string $pdf): int
    {
        if (preg_match('/\/Type\s*\/Pages\b[^>]*?\/Count\s+(\d+)/s', $pdf, $m)) {
            return max(1, (int) $m[1]);
        }
        // fallback: hitung objek /Type /Page (bukan /Pages)
        $pages = preg_match_all('/\/Type\s*\/Page(?![s])/', $pdf);
        return max(1, $pages);
    }
}
