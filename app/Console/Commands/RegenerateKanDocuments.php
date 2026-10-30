<?php

namespace App\Console\Commands;

use App\Models\ParticipantResult;
use App\Services\DocumentGeneratorService;
use Illuminate\Console\Command;

/**
 * Buat ulang PDF SK & Sertifikat varian "dengan KAN" yang sudah terkirim, mis. setelah
 * logo KAN diganti. Kunci cache sudah memuat isi file logo, jadi PDF lama tidak dipakai
 * lagi dan otomatis dibuat ulang saat diunduh; perintah ini membuatnya sekarang juga
 * supaya unduhan pertama peserta tidak lambat. Varian tanpa KAN tidak disentuh.
 */
class RegenerateKanDocuments extends Command
{
    protected $signature = 'documents:regenerate-kan
        {sessions?* : ID sesi (kosong = semua sesi)}
        {--dry-run : Tampilkan peserta yang akan dibuat ulang tanpa membuat PDF}';

    protected $description = 'Buat ulang PDF SK & Sertifikat dengan KAN yang sudah terkirim';

    public function handle(DocumentGeneratorService $generator): int
    {
        $results = ParticipantResult::query()
            ->where('with_kan', true)
            ->whereNotNull('distributed_at')
            ->when($this->argument('sessions'), fn ($q, $ids) => $q->whereIn('exam_session_id', $ids))
            ->with('student:id,no_participant,name')
            ->orderBy('exam_session_id')
            ->get();

        if ($results->isEmpty()) {
            $this->info('Tidak ada SK/Sertifikat dengan KAN yang sudah terkirim.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->table(['Sesi', 'No Peserta', 'Nama', 'No. SK', 'No. Sertifikat'], $results->map(fn ($r) => [
                $r->exam_session_id, $r->student?->no_participant, $r->student?->name, $r->sk_number ?? '—', $r->sertifikat_number ?? '—',
            ]));
            $this->warn("Dry run — {$results->count()} peserta akan dibuat ulang, belum ada PDF yang dibuat.");

            return self::SUCCESS;
        }

        $failed = 0;
        $bar = $this->output->createProgressBar($results->count());

        foreach ($results as $r) {
            try {
                if ($r->sk_number) {
                    $generator->skPdf($r, true);
                }
                if ($r->sertifikat_number) {
                    $generator->sertifikatPdf($r, true);
                }
            } catch (\Throwable $e) {
                $failed++;
                $this->newLine();
                $this->error("Sesi {$r->exam_session_id} / {$r->student?->no_participant}: {$e->getMessage()}");
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Selesai — ' . ($results->count() - $failed) . " peserta dibuat ulang" . ($failed ? ", {$failed} gagal." : '.'));

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
