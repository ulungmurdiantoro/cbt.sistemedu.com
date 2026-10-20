<?php

namespace App\Services;

use App\Models\NumberingCounter;
use App\Models\ParticipantResult;
use Illuminate\Support\Facades\DB;

class NumberingService
{
    /** Format: 001/SPT/EDUKIA/VI/2026 */
    public function nextSpNumber(): string
    {
        return DB::transaction(function () {
            $year = now()->year;

            $counter = NumberingCounter::lockForUpdate()
                ->firstOrCreate(
                    ['type' => 'sp', 'scope' => null, 'year' => $year],
                    ['last_number' => 0]
                );

            $counter->increment('last_number');
            $counter->refresh();

            $n     = str_pad($counter->last_number, 3, '0', STR_PAD_LEFT);
            $bulan = $this->toRoman(now()->month);

            return "{$n}/SPT/EDUKIA/{$bulan}/{$year}";
        });
    }

    /** Format: 111/SK-SP/LSP-EDUKIA/V/2026 */
    public function nextSkNumber(): string
    {
        return DB::transaction(function () {
            $year = now()->year;

            $counter = NumberingCounter::lockForUpdate()
                ->firstOrCreate(
                    ['type' => 'sk', 'scope' => null, 'year' => $year],
                    ['last_number' => 0]
                );

            $counter->increment('last_number');
            $counter->refresh();

            $n    = str_pad($counter->last_number, 3, '0', STR_PAD_LEFT);
            $bulan = $this->toRoman(now()->month);

            return "{$n}/SK-SP/LSP-EDUKIA/{$bulan}/{$year}";
        });
    }

    /**
     * Format: 015-001-05-2026-00001
     * @param string $kodeSkema  e.g. "PJKP-001-015"
     * @param string $kodeBatch  e.g. "BATCH-2026-001"
     * @param int    $classroomId
     */
    public function nextSertifikatNumber(string $kodeSkema, string $kodeBatch, int $classroomId): string
    {
        return DB::transaction(function () use ($kodeSkema, $kodeBatch, $classroomId) {
            $year  = now()->year;
            $scope = (string) $classroomId;

            $counter = NumberingCounter::lockForUpdate()
                ->firstOrCreate(
                    ['type' => 'sertifikat', 'scope' => $scope, 'year' => $year],
                    ['last_number' => 0]
                );

            $skema3  = self::skemaCode($kodeSkema);

            // Counter bisa tertinggal dari nomor yang sudah terbit untuk skema ini (counter hilang/dibuat
            // ulang, atau kelas dobel berkode skema sama) — lanjutkan dari nomor tertinggi yang tersimpan
            // supaya urutan tidak mulai lagi dari 00001.
            $counter->update(['last_number' => max($counter->last_number, $this->highestSertifikatSeq($skema3, $year)) + 1]);

            preg_match('/(\d+)$/', $kodeBatch, $m);
            $batch3  = isset($m[1]) ? str_pad($m[1], 3, '0', STR_PAD_LEFT) : '000';

            $bulan   = str_pad(now()->month, 2, '0', STR_PAD_LEFT);
            $seq     = str_pad($counter->last_number, 5, '0', STR_PAD_LEFT);

            return "{$skema3}-{$batch3}-{$bulan}-{$year}-{$seq}";
        });
    }

    /** "EDUKIA-IMR-2024-003" → "003" (3 digit terakhir), bagian pertama nomor sertifikat. */
    public static function skemaCode(?string $kodeSkema): string
    {
        return str_pad(substr(preg_replace('/[^0-9]/', '', (string) $kodeSkema), -3), 3, '0', STR_PAD_LEFT);
    }

    /** Nomor sertifikat "003-010-10-2026-00082" → [skema '003', tahun 2026, urut 82]; null bila format lain. */
    public static function parseSertifikatNumber(?string $number): ?array
    {
        return $number && preg_match('/^(\d{3})-\d+-\d{2}-(\d{4})-(\d+)$/', $number, $m)
            ? ['skema' => $m[1], 'year' => (int) $m[2], 'seq' => (int) $m[3]]
            : null;
    }

    /** Nomor urut sertifikat tertinggi yang sudah terbit untuk kode skema + tahun ini (lintas sesi & kelas). */
    public function highestSertifikatSeq(string $skema3, int $year): int
    {
        return (int) ParticipantResult::where('sertifikat_number', 'like', "{$skema3}-%-{$year}-%")
            ->pluck('sertifikat_number')
            ->map(fn ($n) => self::parseSertifikatNumber($n))
            ->filter(fn ($p) => $p && $p['skema'] === $skema3 && $p['year'] === $year)
            ->max('seq');
    }

    /** Format: KEP/EDUKIA/I-2026/001 */
    public function nextKeputusanNumber(): string
    {
        return DB::transaction(function () {
            $year = now()->year;

            $counter = NumberingCounter::lockForUpdate()
                ->firstOrCreate(
                    ['type' => 'keputusan', 'scope' => null, 'year' => $year],
                    ['last_number' => 0]
                );

            $counter->increment('last_number');
            $counter->refresh();

            $n     = str_pad($counter->last_number, 3, '0', STR_PAD_LEFT);
            $bulan = $this->toRoman(now()->month);

            return "KEP/EDUKIA/{$bulan}-{$year}/{$n}";
        });
    }

    private function toRoman(int $month): string
    {
        return ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'][$month - 1];
    }
}
