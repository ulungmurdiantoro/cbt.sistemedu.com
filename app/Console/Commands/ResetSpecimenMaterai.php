<?php

namespace App\Console\Commands;

use App\Models\AssessmentApplication;
use App\Models\ParticipantResult;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ResetSpecimenMaterai extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'materai:reset-specimen
        {--dry-run : Tampilkan apa yang akan direset tanpa mengubah data atau file apa pun}
        {--force : Jalankan tanpa konfirmasi}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset status materai FR.AK.01 & FR.AK.14 hasil staging Peruri (spesimen) dan hapus file PDF-nya, supaya bisa dibubuhi ulang di production (tanda tangan tidak disentuh)';

    public function handle(): int
    {
        // Pengaman: begitu materai production aktif, perintah ini akan menghapus
        // materai ASLI — jadi hanya boleh jalan saat saklar utama masih mati.
        if (config('materai.enabled')) {
            $this->error('MATERAI_ENABLED masih true. Set false dulu (lalu php artisan config:clear) — perintah ini hanya untuk membersihkan materai spesimen.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $disk   = Storage::disk('private');

        $groups = [
            'FR.AK.14 (participant_results)'      => ParticipantResult::where('materai_status', '!=', 'none')->get(),
            'FR.AK.01 (assessment_applications)' => AssessmentApplication::where('materai_status', '!=', 'none')->get(),
        ];

        $total = 0;
        foreach ($groups as $label => $rows) {
            $this->line("<options=bold>{$label}</>");

            if ($rows->isEmpty()) {
                $this->line('  tidak ada yang perlu direset.');
                continue;
            }

            foreach ($rows->groupBy('materai_status') as $status => $group) {
                $files = $group->filter(fn($r) => $r->materai_document_path && $disk->exists($r->materai_document_path))->count();
                $this->line("  {$status}: {$group->count()} baris, {$files} file PDF");
            }
            $total += $rows->count();
        }

        if ($total === 0) {
            $this->info('Tidak ada yang perlu direset.');

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->warn("Dry run — tidak ada perubahan. {$total} baris akan direset & file PDF-nya dihapus.");

            return self::SUCCESS;
        }

        if (!$this->option('force') && !$this->confirm("Reset {$total} baris ke status 'none' dan hapus file PDF spesimennya?")) {
            $this->line('Dibatalkan.');

            return self::SUCCESS;
        }

        foreach ($groups as $rows) {
            foreach ($rows as $row) {
                if ($row->materai_document_path) {
                    $disk->delete($row->materai_document_path);
                }

                $row->update([
                    'materai_status'         => 'none',
                    'materai_stamped_at'     => null,
                    'materai_document_path'  => null,
                    'materai_failure_reason' => null,
                ]);
            }
        }

        $this->info("Selesai — {$total} baris direset. Tanda tangan peserta tidak diubah.");

        return self::SUCCESS;
    }
}
