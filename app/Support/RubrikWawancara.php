<?php

namespace App\Support;

/**
 * Rubrik Indikator Penilaian Wawancara — sama untuk semua skema. Hanya panduan asesor: nilai tetap
 * diketik 0–100 per kriteria dan Total = rata-ratanya (InterviewAssessmentController / ResultCalculatorService).
 * Tampil di halaman Nilai Wawancara asesor (prop `rubrik`) dan PDF Hasil Ujian (kolom Kategori).
 *
 * Kriteria (D) Worksheet tidak punya deskripsi di tabel sumber → hanya pita nilainya.
 */
class RubrikWawancara
{
    /** Pita nilai, urut dari tertinggi. Sama dengan pita() di Pages/Asesor/Wawancara/Show.vue. */
    public const PITA = [
        ['kode' => 'a', 'label' => 'N > 80'],
        ['kode' => 'b', 'label' => '71 ≤ N ≤ 80'],
        ['kode' => 'c', 'label' => '60 ≤ N ≤ 70'],
        ['kode' => 'd', 'label' => 'N < 60'],
    ];

    /** Deskripsi per kriteria (kolom interview_assessments) per pita. */
    public const KRITERIA = [
        'gaya_wawancara' => [
            'nama'      => 'Gaya Wawancara',
            'deskripsi' => [
                'a' => 'Pembicara sangat tenang, sangat semangat dan tidak bergantung pada catatan (membaca). Pembicara selalu kontak mata dengan pendengar.',
                'b' => 'Pembicara tenang, semangat dan jarang bergantung pada catatan (membaca), dan sering melakukan kontak mata dengan pendengar.',
                'c' => 'Pembicara cukup tenang, cukup semangat dan bergantung pada catatan (membaca). Cukup sering melakukan kontak mata dengan pendengar.',
                'd' => 'Pembicara kurang tenang, kurang semangat dan sangat bergantung pada catatan (membaca). Kurang melakukan kontak mata dengan pendengar.',
            ],
        ],
        'penguasaan_materi' => [
            'nama'      => 'Penguasaan Materi',
            'deskripsi' => [
                'a' => 'Sangat menguasai topik bahasan dan memiliki wawasan yang sangat luas.',
                'b' => 'Menguasai topik bahasan dan memiliki wawasan yang luas.',
                'c' => 'Cukup menguasai topik bahasan dan memiliki wawasan yang cukup luas.',
                'd' => 'Kurang menguasai topik bahasan dan memiliki wawasan yang kurang luas.',
            ],
        ],
        'kemampuan_hadapi_pertanyaan' => [
            'nama'      => 'Kemampuan Menghadapi Pertanyaan',
            'deskripsi' => [
                'a' => 'Menjawab pertanyaan dengan sangat baik menggunakan argumen yang sangat tepat.',
                'b' => 'Menjawab pertanyaan dengan baik menggunakan argumen yang tepat.',
                'c' => 'Menjawab pertanyaan dengan cukup baik menggunakan argumen yang cukup tepat.',
                'd' => 'Menjawab pertanyaan dengan kurang baik dan menggunakan argumen yang kurang tepat.',
            ],
        ],
    ];

    /** Kode pita untuk nilai $n (null bila kosong): > 80 → a, 71–80 → b, 60–70 → c, < 60 → d. */
    public static function pita(mixed $n): ?string
    {
        if ($n === null || $n === '' || ! is_numeric($n)) {
            return null;
        }
        $n = (float) $n;

        return match (true) {
            $n > 80  => 'a',
            $n >= 71 => 'b',
            $n >= 60 => 'c',
            default  => 'd',
        };
    }

    public static function label(?string $pita): ?string
    {
        return collect(self::PITA)->firstWhere('kode', $pita)['label'] ?? null;
    }

    /** Deskripsi kriteria $kolom untuk nilai $n (null bila kriteria tanpa deskripsi / nilai kosong). */
    public static function deskripsi(string $kolom, mixed $n): ?string
    {
        $pita = self::pita($n);

        return $pita ? (self::KRITERIA[$kolom]['deskripsi'][$pita] ?? null) : null;
    }

    /** Untuk halaman asesor. */
    public static function props(): array
    {
        return ['pita' => self::PITA, 'kriteria' => self::KRITERIA];
    }
}
