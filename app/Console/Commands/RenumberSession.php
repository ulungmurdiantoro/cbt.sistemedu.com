<?php

namespace App\Console\Commands;

use App\Models\ExamSession;
use App\Models\NumberingCounter;
use App\Models\ParticipantResult;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Menomori ulang SK/SP/sertifikat satu sesi mulai dari nomor urut tertentu (urut
 * No. Peserta), lalu menyamakan counter dengan nomor tertinggi yang terpakai —
 * untuk menutup celah penomoran tanpa SQL manual. Bagian selain nomor urut
 * ("/SK-SP/LSP-EDUKIA/IX/2026", "003-010-10-2026-") dipertahankan.
 */
class RenumberSession extends Command
{
    protected $signature = 'numbering:renumber-session
        {session : ID sesi ujian}
        {--sk= : Nomor urut SK pertama untuk sesi ini}
        {--sp= : Nomor urut SP pertama untuk sesi ini}
        {--sertifikat= : Nomor urut sertifikat pertama untuk sesi ini (5 digit terakhir)}
        {--allow-distributed : Tetap ubah walau dokumennya sudah dikirim ke peserta}
        {--dry-run : Tampilkan rencana perubahan tanpa menyimpan}
        {--force : Jalankan tanpa konfirmasi}';

    protected $description = 'Nomori ulang SK/SP/sertifikat satu sesi mulai nomor tertentu dan sesuaikan counter penomoran';

    // type => [kolom nomor, kolom tanda sudah dikirim]
    private const TYPES = [
        'sk'         => ['sk_number', 'distributed_at'],
        'sp'         => ['sp_number', 'sp_distributed_at'],
        'sertifikat' => ['sertifikat_number', 'distributed_at'],
    ];

    public function handle(): int
    {
        $sessionId = (int) $this->argument('session');
        $starts    = array_filter([
            'sk'         => $this->option('sk'),
            'sp'         => $this->option('sp'),
            'sertifikat' => $this->option('sertifikat'),
        ], fn ($v) => $v !== null);

        if (!$starts) {
            $this->error('Isi --sk=, --sp= dan/atau --sertifikat= (nomor urut pertama).');

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
                // tidak bentrok dengan unique index sk_number / sertifikat_number.
                foreach ($plan['changes'] as $c) {
                    $c['row']->update([$column => "TMP-{$type}-{$c['row']->id}"]);
                }
                foreach ($plan['changes'] as $c) {
                    $c['row']->update([$column => $c['new']]);
                }

                NumberingCounter::updateOrCreate(
                    ['type' => $type, 'scope' => $plan['scope'], 'year' => $plan['year']],
                    ['last_number' => $plan['counter_new']]
                );
            }
        });

        $this->info('Selesai. Jalankan php artisan numbering:audit untuk memeriksa hasilnya.');

        return self::SUCCESS;
    }

    /** @return array{changes: array, year: int, scope: ?string, counter_new: int}|null */
    private function plan(string $type, int $start, int $sessionId, Collection $rows): ?array
    {
        [$column, $sentColumn] = self::TYPES[$type];
        $label = $type === 'sertifikat' ? 'Sertifikat' : strtoupper($type);
        $fmt   = $type === 'sertifikat' ? '%05d' : '%03d';

        $rows = $rows->filter(fn ($r) => $r->{$column})->values();
        if ($rows->isEmpty()) {
            $this->error("Sesi #{$sessionId} tidak punya nomor {$label} yang sudah final.");

            return null;
        }

        $changes = [];
        foreach ($rows as $i => $row) {
            $parsed = $this->parse($type, $row->{$column});
            if (!$parsed) {
                $this->error("Format nomor {$label} tidak dikenali: {$row->{$column}}");

                return null;
            }
            $changes[] = ['row' => $row, 'old' => $row->{$column}, 'new' => $parsed['build']($start + $i), 'seq' => $start + $i] + $parsed;
        }

        // SK/SP diurutkan global per tahun; sertifikat per kode skema + tahun.
        $groups = array_unique(array_column($changes, 'group'));
        if (count($groups) > 1) {
            $this->error("Nomor {$label} sesi ini berasal dari lebih dari satu tahun/skema — nomori per kelompok secara manual.");

            return null;
        }
        $group = $groups[0];
        $year  = $changes[0]['year'];

        // Nomor urut yang sudah dipakai sesi lain dalam kelompok yang sama.
        $taken = ParticipantResult::where('exam_session_id', '!=', $sessionId)->whereNotNull($column)->pluck($column)
            ->map(fn ($n) => ($p = $this->parse($type, $n)) && $p['group'] === $group ? $p['seq'] : null)
            ->filter();

        $clash = collect($changes)->pluck('seq')->intersect($taken);
        if ($clash->isNotEmpty()) {
            $this->error("Nomor urut {$label} berikut sudah dipakai sesi lain: " . $clash->map(fn ($n) => sprintf($fmt, $n))->implode(', '));

            return null;
        }

        // Counter sertifikat per kelas (classroom_id ujian sesi, sama dengan saat finalisasi).
        $scope = null;
        if ($type === 'sertifikat') {
            $scope = ExamSession::with('examPg', 'examEsai')->find($sessionId)?->referenceExam?->classroom_id;
            if (!$scope) {
                $this->error("Sesi #{$sessionId} tidak punya ujian PG/Esai — kelas untuk counter sertifikat tidak diketahui.");

                return null;
            }
            $scope = (string) $scope;
        }

        $counter    = NumberingCounter::where('type', $type)->where('scope', $scope)->where('year', $year)->value('last_number');
        $counterNew = max($taken->max() ?? 0, collect($changes)->max('seq'));

        // Sertifikat: FR.AK.14 bermeterai juga memuat nomornya (file jadi, tidak dibuat ulang).
        $isSent = fn ($row) => $row->{$sentColumn} || ($type === 'sertifikat' && $row->materai_status === 'stamped');

        $this->newLine();
        $this->line("<options=bold>{$label} sesi #{$sessionId} ({$rows->count()} nomor)</>");
        $this->table(['No Peserta', 'Nama', 'Nomor lama', 'Nomor baru', 'Terkirim'], collect($changes)->map(fn ($c) => [
            $c['row']->student?->no_participant, $c['row']->student?->name, $c['old'], $c['new'],
            $isSent($c['row']) ? 'ya' : '-',
        ])->all());
        $this->line("  Counter {$label} {$year}" . ($scope ? " (kelas #{$scope})" : '') . ': ' . ($counter ?? 'tidak ada')
            . " → {$counterNew} (nomor berikutnya " . sprintf($fmt, $counterNew + 1) . ')');

        if ($counter !== null && $counterNew < $counter) {
            $this->warn('  Counter diturunkan — pastikan nomor ' . sprintf("{$fmt}–{$fmt}", $counterNew + 1, $counter) . ' memang belum dipakai di register di luar aplikasi.');
        }

        $sent = collect($changes)->filter(fn ($c) => $isSent($c['row']) && $c['old'] !== $c['new'])->count();
        if ($sent > 0 && !$this->option('allow-distributed')) {
            $what = $type === 'sertifikat' ? 'sertifikat sesi ini sudah dikirim ke peserta / FR.AK.14-nya sudah bermeterai' : "dokumen {$label} sesi ini sudah dikirim ke peserta";
            $this->error("{$sent} {$what} — mengganti nomornya membuat dokumen di tangan peserta tidak cocok lagi. Tambahkan --allow-distributed bila tetap ingin diubah (lalu kirim ulang).");

            return null;
        }

        return ['changes' => $changes, 'year' => $year, 'scope' => $scope, 'counter_new' => $counterNew];
    }

    /**
     * Pecah nomor jadi nomor urut + kelompok penomorannya, dan cara menyusun ulang dengan nomor urut lain.
     *
     * @return array{seq: int, year: int, group: string, build: \Closure}|null
     */
    private function parse(string $type, ?string $number): ?array
    {
        $number = ParticipantResult::cleanNumber($number) ?? '';

        if ($type === 'sertifikat') {
            // "003-010-10-2026-00001" — urut per kode skema (003) + tahun
            if (!preg_match('/^((\d{3})-\d+-\d{2}-(\d{4})-)(\d+)$/', $number, $m)) {
                return null;
            }

            return ['seq' => (int) $m[4], 'year' => (int) $m[3], 'group' => "{$m[2]}|{$m[3]}", 'build' => fn ($n) => $m[1] . sprintf('%05d', $n)];
        }

        // "017/SPT/EDUKIA/IX/2026" — urut global per tahun
        if (!preg_match('/^(\d+)(\/.*\/(\d{4}))$/', $number, $m)) {
            return null;
        }

        return ['seq' => (int) $m[1], 'year' => (int) $m[3], 'group' => $m[3], 'build' => fn ($n) => sprintf('%03d', $n) . $m[2]];
    }
}
