<?php

namespace App\Support;

use App\Models\ExamSession;
use App\Models\GradingScheme;

/**
 * Nilai Ujian Praktik/Keterampilan sebagai komponen nilai sendiri. Berlaku bila Komposisi Nilai skema memberi bobot praktik
 * (grading_schemes.bobot_praktik_1/2 > 0, persen dari nilai akhir) DAN sesi memilih bagian praktik tersebut.
 * Contoh Lifting: PG 20 + Calculation (bagian 1) 50 + Gambar (bagian 2) 30.
 *
 * Skema tanpa bobot praktik (mis. Corporate Legal, HSE, ESG) tetap menilai keterampilan lewat kriteria
 * "Hasil Pengerjaan Worksheet" di wawancara — halaman Nilai Praktik/Keterampilan tidak dipakai.
 */
class NilaiPraktik
{
    /** Komposisi Nilai skema sesi (kelas ujian PG/Esai/Praktik pertama). */
    public static function schemeFor(ExamSession $session): ?GradingScheme
    {
        $classroomId = $session->referenceExam?->classroom_id;

        return $classroomId ? GradingScheme::where('classroom_id', $classroomId)->first() : null;
    }

    /** [bagian => bobot (% nilai akhir)] untuk bagian praktik sesi yang dinilai terpisah; kosong = tidak berlaku. */
    public static function weights(ExamSession $session, ?GradingScheme $scheme = null): array
    {
        $scheme ??= self::schemeFor($session);
        if (! $scheme) {
            return [];
        }

        $weights = [1 => (float) $scheme->bobot_praktik_1, 2 => (float) $scheme->bobot_praktik_2];

        return array_filter(array_intersect_key($weights, UjianPraktik::parts($session->id)), fn (float $b) => $b > 0);
    }

    public static function applies(ExamSession $session, ?GradingScheme $scheme = null): bool
    {
        return self::weights($session, $scheme) !== [];
    }

    /**
     * Nilai satu aspek yang dinilai per butir ($aspek['item'] = [{no, nama}], $aspek['pilihan'] = [{label, nilai}],
     * $pilih = {no butir: nilai}): jumlah nilai butir (maks. 100). Null selama ada butir yang belum dipilih.
     */
    public static function itemScore(array $aspek, array $pilih): ?float
    {
        $sum = 0.0;
        foreach ($aspek['item'] ?? [] as $item) {
            $v = $pilih[$item['no']] ?? null;
            if ($v === null || $v === '') {
                return null;
            }
            $sum += (float) $v;
        }

        return round($sum, 2);
    }

    /**
     * Nilai satu bagian dari nilai per aspek rubrik ($aspek = [{no, nama, bobot}], $nilai = {no: 0–100}):
     * Σ nilai × bobot / Σ bobot. Null selama masih ada aspek yang kosong (nilai per aspek tetap tersimpan).
     */
    public static function aspekScore(array $aspek, array $nilai): ?float
    {
        $sum = 0.0;
        $bobot = 0.0;
        foreach ($aspek as $a) {
            $v = $nilai[$a['no']] ?? null;
            if ($v === null || $v === '') {
                return null;
            }
            $sum += (float) $v * (float) $a['bobot'];
            $bobot += (float) $a['bobot'];
        }

        return $bobot > 0 ? round($sum / $bobot, 2) : null;
    }

    /**
     * Nilai praktik/keterampilan gabungan dari nilai per bagian ([bagian => nilai|null]) — rata-rata berbobot bagian yang sudah
     * dinilai — dan jumlah bobotnya (dipakai ResultCalculatorService seperti bobot komponen lain).
     *
     * @return array{0: ?float, 1: float}  [nilai 0–100 atau null, bobot]
     */
    public static function combine(array $scores, array $weights): array
    {
        $sum = 0.0;
        $bobot = 0.0;
        foreach ($weights as $bagian => $b) {
            if (isset($scores[$bagian]) && $scores[$bagian] !== null) {
                $sum += (float) $scores[$bagian] * $b;
                $bobot += $b;
            }
        }

        return $bobot > 0 ? [round($sum / $bobot, 2), $bobot] : [null, 0.0];
    }
}
