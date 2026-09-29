<?php

namespace App\Console\Commands;

use App\Models\ParticipantResult;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Buang spasi dari nomor SK/SP yang sudah tersimpan (mis. "489/SPT/EDUKIA /IX/2026"
 * hasil ketikan manual). Nomor baru sudah otomatis bersih lewat mutator
 * ParticipantResult; perintah ini untuk data lama.
 */
class NormalizeNumbers extends Command
{
    protected $signature = 'numbering:normalize
        {--dry-run : Tampilkan nomor yang akan dirapikan tanpa menyimpan}
        {--force : Jalankan tanpa konfirmasi}';

    protected $description = 'Hapus spasi dari nomor SK/SP yang sudah tersimpan';

    public function handle(): int
    {
        $changes = [];

        ParticipantResult::numbered()->with('student:id,no_participant')->get()->each(function ($r) use (&$changes) {
            foreach (['sk_number', 'sp_number'] as $column) {
                $clean = ParticipantResult::cleanNumber($r->{$column});
                if ($clean !== $r->{$column}) {
                    $changes[] = ['row' => $r, 'column' => $column, 'old' => $r->{$column}, 'new' => $clean];
                }
            }
        });

        if (!$changes) {
            $this->info('Semua nomor SK/SP sudah rapi, tidak ada spasi.');

            return self::SUCCESS;
        }

        foreach ($changes as $c) {
            $exists = ParticipantResult::where($c['column'], $c['new'])->where('id', '!=', $c['row']->id)->exists();
            if ($exists) {
                $this->error("Nomor {$c['new']} sudah dipakai baris lain — perbaiki manual dulu.");

                return self::FAILURE;
            }
        }

        $this->table(['Sesi', 'No Peserta', 'Kolom', 'Nomor lama', 'Nomor baru'], array_map(fn ($c) => [
            $c['row']->exam_session_id, $c['row']->student?->no_participant, $c['column'], "\"{$c['old']}\"", $c['new'],
        ], $changes));

        if ($this->option('dry-run')) {
            $this->warn('Dry run — ' . count($changes) . ' nomor akan dirapikan, belum ada yang diubah.');

            return self::SUCCESS;
        }

        if (!$this->option('force') && !$this->confirm('Rapikan ' . count($changes) . ' nomor di atas?')) {
            $this->line('Dibatalkan.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($changes) {
            foreach ($changes as $c) {
                $c['row']->update([$c['column'] => $c['new']]);
            }
        });

        $this->info('Selesai — ' . count($changes) . ' nomor dirapikan. PDF SK/SP akan dibuat ulang dengan nomor baru saat diunduh.');

        return self::SUCCESS;
    }
}
