<?php

namespace App\Services;

use App\Models\NumberingCounter;
use Illuminate\Support\Facades\DB;

class NumberingService
{
    /** Format: 001/SP/LSP-EDUKIA/VI/2026 */
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

            return "{$n}/SP/LSP-EDUKIA/{$bulan}/{$year}";
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

            $counter->increment('last_number');
            $counter->refresh();

            $skema3  = substr(preg_replace('/[^0-9]/', '', $kodeSkema), -3);
            $skema3  = str_pad($skema3, 3, '0', STR_PAD_LEFT);

            preg_match('/(\d+)$/', $kodeBatch, $m);
            $batch3  = isset($m[1]) ? str_pad($m[1], 3, '0', STR_PAD_LEFT) : '000';

            $bulan   = str_pad(now()->month, 2, '0', STR_PAD_LEFT);
            $seq     = str_pad($counter->last_number, 5, '0', STR_PAD_LEFT);

            return "{$skema3}-{$batch3}-{$bulan}-{$year}-{$seq}";
        });
    }

    private function toRoman(int $month): string
    {
        return ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'][$month - 1];
    }
}
