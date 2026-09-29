<?php

namespace App\Console\Commands;

use App\Models\NumberingCounter;
use App\Models\ParticipantResult;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Menomori ulang SK/SP satu sesi mulai dari nomor urut tertentu (urut No.
 * Peserta), lalu menyamakan counter dengan nomor tertinggi yang terpakai —
 * untuk menutup celah penomoran tanpa SQL manual. Bagian setelah nomor urut
 * ("/SK-SP/LSP-EDUKIA/IX/2026") dipertahankan.
 */
class RenumberSession extends Command
{
    protected $signature = 'numbering:renumber-session
        {session : ID sesi ujian}
        {--sk= : Nomor urut SK pertama untuk sesi ini}
        {--sp= : Nomor urut SP pertama untuk sesi ini}
        {--allow-distributed : Tetap ubah walau dokumennya sudah dikirim ke peserta}
        {--dry-run : Tampilkan rencana perubahan tanpa menyimpan}
        {--force : Jalankan tanpa konfirmasi}';

    protected $description = 'Nomori ulang SK/SP satu sesi mulai nomor tertentu dan sesuaikan counter penomoran';

    // type => [kolom nomor, kolom tanda sudah dikirim]
    private const TYPES = [
        'sk' => ['sk_number', 'distributed_at'],
        'sp' => ['sp_number', 'sp_distributed_at'],
    ];

    public function handle(): int
    {
        $sessionId = (int) $this->argument('session');
        $starts    = array_filter(['sk' => $this->option('sk'), 'sp' => $this->option('sp')], fn ($v) => $v !== null);

        if (!$starts) {
            $this->error('Isi --sk= dan/atau --sp= (nomor urut pertama).');

            return self::FAILURE;
        }

        $rows = ParticipantResult::with('student:id,no_participant,name')
            ->where('exam_session_id', $sessionId)
            ->where('is_finalized', true)
            ->get()
            ->sortBy(fn ($r) => $r->student?->no_participant)
            ->values();

        $plans = [];
        foreach ($starts as $type => $start) {
            $plan = $this->plan($type, (int) $start, $sessionId, $rows);
            if ($plan === null) {
                return self::FAILURE;
            }
            $plans[$type] = $plan;
        }

        if ($this->option('dry-run')) {
            $this->warn('Dry run — tidak ada yang diubah.');

            return self::SUCCESS;
        }

        if (!$this->option('force') && !$this->confirm('Simpan perubahan nomor & counter di atas?')) {
            $this->line('Dibatalkan.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($plans) {
            foreach ($plans as $type => $plan) {
                [$column] = self::TYPES[$type];

                // Dua tahap supaya pergeseran yang tumpang-tindih (mis. 485→486)
                // tidak bentrok dengan unique index sk_number.
                foreach ($plan['changes'] as $c) {
                    $c['row']->update([$column => "TMP-{$type}-{$c['row']->id}"]);
                }
                foreach ($plan['changes'] as $c) {
                    $c['row']->update([$column => $c['new']]);
                }

                NumberingCounter::where('type', $type)->whereNull('scope')->where('year', $plan['year'])
                    ->update(['last_number' => $plan['counter_new']]);
            }
        });

        $this->info('Selesai. Jalankan php artisan numbering:audit untuk memeriksa hasilnya.');

        return self::SUCCESS;
    }

    /** @return array{changes: array, year: int, counter_new: int}|null */
    private function plan(string $type, int $start, int $sessionId, Collection $rows): ?array
    {
        [$column, $sentColumn] = self::TYPES[$type];
        $label = strtoupper($type);

        $rows = $rows->filter(fn ($r) => $r->{$column})->values();
        if ($rows->isEmpty()) {
            $this->error("Sesi #{$sessionId} tidak punya nomor {$label} yang sudah final.");

            return null;
        }

        $changes = [];
        foreach ($rows as $i => $row) {
            if (!preg_match('/^\d+(\/.*\/(\d{4}))$/', ParticipantResult::cleanNumber($row->{$column}), $m)) {
                $this->error("Format nomor {$label} tidak dikenali: {$row->{$column}}");

                return null;
            }
            $changes[] = ['row' => $row, 'old' => $row->{$column}, 'new' => sprintf('%03d', $start + $i) . $m[1], 'seq' => $start + $i, 'year' => (int) $m[2]];
        }

        $years = array_unique(array_column($changes, 'year'));
        if (count($years) > 1) {
            $this->error("Nomor {$label} sesi ini berasal dari lebih dari satu tahun — nomori per tahun secara manual.");

            return null;
        }
        $year = $years[0];

        // Nomor urut yang sudah dipakai sesi lain di tahun yang sama.
        $taken = ParticipantResult::where('exam_session_id', '!=', $sessionId)->whereNotNull($column)->pluck($column)
            ->map(fn ($n) => preg_match('/^(\d+)\/.*\/(\d{4})$/', ParticipantResult::cleanNumber($n), $m) && (int) $m[2] === $year ? (int) $m[1] : null)
            ->filter();

        $clash = collect($changes)->pluck('seq')->intersect($taken);
        if ($clash->isNotEmpty()) {
            $this->error("Nomor urut {$label} berikut sudah dipakai sesi lain: " . $clash->map(fn ($n) => sprintf('%03d', $n))->implode(', '));

            return null;
        }

        $counter    = NumberingCounter::where('type', $type)->whereNull('scope')->where('year', $year)->value('last_number');
        $counterNew = max($taken->max() ?? 0, collect($changes)->max('seq'));

        $this->newLine();
        $this->line("<options=bold>{$label} sesi #{$sessionId} ({$rows->count()} nomor)</>");
        $this->table(['No Peserta', 'Nama', 'Nomor lama', 'Nomor baru', 'Terkirim'], collect($changes)->map(fn ($c) => [
            $c['row']->student?->no_participant, $c['row']->student?->name, $c['old'], $c['new'],
            $c['row']->{$sentColumn} ? 'ya' : '-',
        ])->all());
        $this->line("  Counter {$label} {$year}: " . ($counter ?? 'tidak ada') . " → {$counterNew} (nomor berikutnya " . sprintf('%03d', $counterNew + 1) . ')');

        if ($counter !== null && $counterNew < $counter) {
            $this->warn('  Counter diturunkan — pastikan nomor ' . sprintf('%03d–%03d', $counterNew + 1, $counter) . ' memang belum dipakai di register di luar aplikasi.');
        }

        $sent = collect($changes)->filter(fn ($c) => $c['row']->{$sentColumn} && $c['old'] !== $c['new'])->count();
        if ($sent > 0 && !$this->option('allow-distributed')) {
            $this->error("{$sent} dokumen {$label} sesi ini sudah dikirim ke peserta — mengganti nomornya membuat dokumen di tangan peserta tidak cocok lagi. Tambahkan --allow-distributed bila tetap ingin diubah (lalu kirim ulang).");

            return null;
        }

        return ['changes' => $changes, 'year' => $year, 'counter_new' => $counterNew];
    }
}
