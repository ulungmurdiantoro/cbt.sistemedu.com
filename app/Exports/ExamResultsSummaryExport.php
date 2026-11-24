<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/** Sheet ringkasan di ZIP "Unduh Hasil Ujian" (App\Services\ExamResultsArchiveService). */
class ExamResultsSummaryExport implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithStyles, WithStrictNullComparison
{
    /**
     * @param  array  $rows  baris dari ExamResultsArchiveService::build()
     * @param  array{pg: bool, esai: bool, wawancara: bool, praktik: bool}  $components  komponen yang dipakai sesi
     */
    public function __construct(private array $rows, private array $components)
    {
    }

    public function title(): string
    {
        return 'Rekap Hasil Ujian';
    }

    private function columns(): array
    {
        $c = $this->components;

        return array_filter([
            'no_peserta'   => 'No. Peserta',
            'nama'         => 'Nama',
            'pg_terjawab'  => $c['pg'] ? 'PG Terjawab' : null,
            'pg_benar'     => $c['pg'] ? 'PG Benar' : null,
            'pg'           => $c['pg'] ? 'Nilai PG' : null,
            'esai_dinilai' => $c['esai'] ? 'Esai Dinilai' : null,
            'esai'         => $c['esai'] ? 'Nilai Esai' : null,
            'wawancara'    => $c['wawancara'] ? 'Nilai Wawancara' : null,
            'praktik'      => $c['praktik'] ? 'Nilai Praktik/Keterampilan' : null,
            'akhir'        => 'Nilai Akhir',
            'keputusan'    => 'Keputusan',
            'unggahan'     => 'Berkas Unggahan',
        ]);
    }

    public function headings(): array
    {
        return array_values($this->columns());
    }

    public function array(): array
    {
        $keys = array_keys($this->columns());

        return array_map(fn (array $row) => array_map(function ($key) use ($row) {
            $value = $row[$key] ?? null;

            return is_float($value) ? round($value, 2) : $value;
        }, $keys), $this->rows);
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
