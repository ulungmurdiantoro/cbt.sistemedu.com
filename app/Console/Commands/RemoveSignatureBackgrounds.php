<?php

namespace App\Console\Commands;

use App\Models\AssessmentApplication;
use App\Models\User;
use App\Support\SignatureImageProcessor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class RemoveSignatureBackgrounds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'signatures:remove-background
        {--dry-run : Tampilkan apa yang akan diproses tanpa mengubah file apa pun}
        {--force : Jalankan tanpa konfirmasi}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Proses ulang TTD yang sudah tersimpan untuk menghapus latar belakangnya (file asli otomatis dicadangkan ke signature-backups/ sebelum ditimpa)';

    public function handle(): int
    {
        $disk   = Storage::disk('private');
        $dryRun = (bool) $this->option('dry-run');

        $targets = collect();

        User::whereNotNull('signature_path')->get(['id', 'name', 'signature_path'])->each(function ($u) use ($targets) {
            $targets->push(['path' => $u->signature_path, 'label' => "User #{$u->id} ({$u->name})"]);
        });

        $columns = [
            'signature_path'        => 'TTD Pakta (Peserta)',
            'signature_form_path'   => 'TTD Formulir (Peserta)',
            'admin_signature_path'  => 'TTD Admin (Approve)',
            'asesor_signature_path' => 'TTD Asesor (Verifikasi Akhir)',
        ];

        foreach ($columns as $column => $label) {
            AssessmentApplication::whereNotNull($column)->get(['id', $column])->each(function ($app) use ($targets, $column, $label) {
                $targets->push(['path' => $app->$column, 'label' => "Permohonan #{$app->id} — {$label}"]);
            });
        }

        $targets = $targets->unique('path')->values();

        if ($targets->isEmpty()) {
            $this->info('Tidak ada TTD tersimpan yang perlu diproses.');
            return self::SUCCESS;
        }

        $this->info("Ditemukan {$targets->count()} TTD tersimpan.");

        if (!$dryRun && !$this->option('force')) {
            if (!$this->confirm('Lanjutkan memproses & menimpa file-file ini? (file asli otomatis dicadangkan ke signature-backups/)')) {
                $this->warn('Dibatalkan.');
                return self::SUCCESS;
            }
        }

        $processed = 0;
        $skipped   = 0;
        $errors    = 0;

        foreach ($targets as $target) {
            $path = $target['path'];

            if (!$disk->exists($path)) {
                $this->warn("Dilewati (file tidak ditemukan): {$path} ({$target['label']})");
                $skipped++;
                continue;
            }

            if ($dryRun) {
                $this->line("[DRY RUN] akan diproses: {$path} ({$target['label']})");
                continue;
            }

            try {
                $raw = $disk->get($path);

                $backupPath = 'signature-backups/' . $path;
                if (!$disk->exists($backupPath)) {
                    $disk->put($backupPath, $raw);
                }

                $disk->put($path, SignatureImageProcessor::removeBackground($raw));
                $this->line("Diproses: {$path} ({$target['label']})");
                $processed++;
            } catch (\Throwable $e) {
                $this->error("Gagal memproses {$path}: {$e->getMessage()}");
                $errors++;
            }
        }

        if ($dryRun) {
            $this->info("Selesai (dry run). {$targets->count()} TTD akan diproses kalau dijalankan tanpa --dry-run.");
        } else {
            $this->info("Selesai. Diproses: {$processed}, dilewati: {$skipped}, error: {$errors}.");
            $this->info('Cadangan file asli tersimpan di disk private, folder signature-backups/.');
        }

        return self::SUCCESS;
    }
}
