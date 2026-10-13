<?php

namespace App\Support;

use App\Support\InitialAssessmentRubric\Rev03;
use App\Support\InitialAssessmentRubric\Rev04;
use InvalidArgumentException;

/**
 * Rubrik Penilaian Awal Kelayakan Pemohon (FR.APL.03).
 *
 * Kriteria & ambang batas per skema ditanam di kelas per revisi dokumen
 * (InitialAssessmentRubric\RevNN), bukan dikonfigurasi lewat UI. Penilaian baru selalu
 * memakai CURRENT; penilaian yang sudah tersimpan tetap ditampilkan & dicetak dengan
 * revisi yang tercatat di initial_assessments.revision, supaya skor dan PDF-nya tidak
 * berubah saat dokumen direvisi. Revisi baru → tambah kelas + daftarkan di REVISIONS.
 */
class InitialAssessmentRubric
{
    public const CURRENT = 'Rev.04';

    public const REVISIONS = [
        'Rev.03' => Rev03::class,
        'Rev.04' => Rev04::class,
    ];

    /**
     * Rubrik untuk satu skema: criteria, threshold, ruang_lingkup, revision, outcome.
     * Skema yang belum terdaftar di dokumen memakai rubrik fallback revisi tsb.
     */
    public static function for(int $classroomId, string $revision = self::CURRENT): array
    {
        $class  = self::revisionClass($revision);
        $rubric = $class::map()[$classroomId] ?? $class::fallback();

        return $rubric + ['revision' => $revision, 'outcome' => $class::outcome()];
    }

    /** Kalimat hasil, meniru format keterangan di dokumen FR.APL.03 revisi tsb. */
    public static function resultSentence(int $score, int $threshold, string $revision = self::CURRENT): string
    {
        $passed     = $score >= $threshold;
        $comparison = $passed ? "\u{2265} {$threshold}" : "< {$threshold}";
        $outcome    = self::revisionClass($revision)::outcome()[$passed ? 'pass' : 'fail'];

        return "Nilai {$score} ({$comparison}) = {$outcome}";
    }

    /** @return class-string<Rev03|Rev04> */
    private static function revisionClass(string $revision): string
    {
        return self::REVISIONS[$revision] ?? throw new InvalidArgumentException("Revisi FR.APL.03 tidak dikenal: {$revision}");
    }

    /**
     * Saring jawaban dari browser: hanya kriteria & opsi yang ada di rubrik, plus isian
     * "sebutkan" untuk opsi "lainnya" yang benar-benar dipilih.
     * Bentuk hasil: [kriteria => opsi] (single), [kriteria => [opsi, ...]] (multi),
     * 'sebutkan' => [kriteria => teks] bila ada.
     */
    public static function normalize(array $rubric, array $answers): array
    {
        $clean    = [];
        $sebutkan = [];

        foreach ($rubric['criteria'] as $criterion) {
            $key   = $criterion['key'];
            $valid = array_column($criterion['options'], 'key');
            $given = $answers[$key] ?? null;

            if ($criterion['type'] === 'multi') {
                $given    = is_array($given) ? array_filter($given, 'is_string') : [];
                $selected = array_values(array_intersect($valid, $given));
                $clean[$key] = $selected;
            } else {
                $selected    = is_string($given) && in_array($given, $valid, true) ? [$given] : [];
                $clean[$key] = $selected[0] ?? null;
            }

            foreach ($criterion['options'] as $option) {
                if (!empty($option['sebutkan']) && in_array($option['key'], $selected, true)) {
                    $text = trim((string) ($answers['sebutkan'][$key] ?? ''));
                    if ($text !== '') {
                        $sebutkan[$key] = mb_substr($text, 0, 255);
                    }
                }
            }
        }

        if ($sebutkan) {
            $clean['sebutkan'] = $sebutkan;
        }

        return $clean;
    }

    /** Label kriteria 'single' yang belum dijawab. */
    public static function missing(array $rubric, array $answers): array
    {
        return collect($rubric['criteria'])
            ->filter(fn ($c) => $c['type'] === 'single' && empty($answers[$c['key']]))
            ->pluck('label')
            ->all();
    }

    /**
     * Hitung total skor dari jawaban yang dikirim.
     * $answers: [criterion_key => option_key] untuk type single,
     *           [criterion_key => [option_key, ...]] untuk type multi.
     */
    public static function score(array $rubric, array $answers): int
    {
        $total = 0;

        foreach ($rubric['criteria'] as $criterion) {
            $given = $answers[$criterion['key']] ?? null;

            if ($criterion['type'] === 'multi') {
                $selected = is_array($given) ? $given : [];
                foreach ($criterion['options'] as $option) {
                    if (in_array($option['key'], $selected, true)) {
                        $total += $option['score'] ?? 0;
                    }
                }
                continue;
            }

            foreach ($criterion['options'] as $option) {
                if ($option['key'] === $given) {
                    $total += $option['score'] ?? 0;
                    break;
                }
            }
        }

        return $total;
    }
}
