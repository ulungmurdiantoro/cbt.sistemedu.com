<?php

namespace App\Support;

use App\Models\Exam;
use App\Models\Grade;
use Carbon\Carbon;

/**
 * Satu ujian berjalan per sesi: selama peserta mengerjakan satu ujian (PG / Esai / Esai Migas yang sudah dimulai tetapi belum
 * diakhiri dan waktunya masih ada, atau satu bagian Ujian Praktik/Keterampilan yang belum diunggah dan waktunya belum habis),
 * ujian lain di sesi yang sama belum bisa dimulai. Urutan ujian tetap bebas. Melanjutkan ujian yang sedang berjalan selalu boleh.
 */
class UjianBerjalan
{
    /** Toleransi koneksi PG/Esai (menit) — config lsp.toleransi_koneksi_menit. */
    public static function graceMinutes(): int
    {
        return max(0, (int) config('lsp.toleransi_koneksi_menit', 30));
    }

    /**
     * Sisa waktu PG/Esai (ms) menurut server: grades.duration (dikurangi timer klien) dibatasi jam dinding
     * start_time + durasi ujian + toleransi koneksi.
     */
    public static function remainingMs(Grade $grade): int
    {
        $stored = max(0, (int) $grade->duration);

        if (! $grade->start_time) {
            return $stored;
        }

        $deadline = Carbon::parse($grade->start_time)
            ->addMinutes((int) $grade->exam->duration + self::graceMinutes());

        return (int) min($stored, max(0, now()->diffInMilliseconds($deadline, false)));
    }

    /**
     * Ujian yang sedang berjalan di sesi, selain ujian $exceptExamId / bagian praktik $exceptBagian.
     *
     * @return array{label: string, exam_id: ?int, bagian: ?int}|null
     */
    public static function running(int $sessionId, int $studentId, ?int $exceptExamId = null, ?int $exceptBagian = null): ?array
    {
        $grades = Grade::with('exam')
            ->where('exam_session_id', $sessionId)
            ->where('student_id', $studentId)
            ->whereNotNull('start_time')
            ->whereNull('end_time')
            ->get();

        foreach ($grades as $grade) {
            if ((int) $grade->exam_id === $exceptExamId || ! $grade->exam || $grade->exam->isPraktik()) {
                continue;
            }
            if (self::remainingMs($grade) > 0) {
                return ['label' => self::label($grade->exam), 'exam_id' => (int) $grade->exam_id, 'bagian' => null];
            }
        }

        if ($bagian = UjianPraktik::runningPart($sessionId, $studentId, $exceptBagian)) {
            $label = count(UjianPraktik::parts($sessionId)) > 1
                ? "Ujian Praktik/Keterampilan Bagian {$bagian}"
                : 'Ujian Praktik/Keterampilan';

            return ['label' => $label, 'exam_id' => null, 'bagian' => $bagian];
        }

        return null;
    }

    /** Pesan untuk peserta bila ujian lain sedang berjalan; null = boleh dimulai. */
    public static function blockedReason(int $sessionId, int $studentId, ?int $exceptExamId = null, ?int $exceptBagian = null): ?string
    {
        $running = self::running($sessionId, $studentId, $exceptExamId, $exceptBagian);

        if (! $running) {
            return null;
        }

        $akhiri = $running['bagian'] ? 'Unggah hasilnya' : 'Akhiri ujian tersebut';

        return "{$running['label']} sedang berjalan. {$akhiri} terlebih dahulu sebelum mengerjakan ujian lain.";
    }

    /** "Ujian Pilihan Ganda" / "Ujian Esai" / "Ujian Praktik/Keterampilan". */
    public static function label(Exam $exam): string
    {
        return match (true) {
            $exam->isPraktik()                                 => 'Ujian Praktik/Keterampilan',
            in_array($exam->type, ['Essay', 'Essay Migas'], true) => 'Ujian Esai',
            default                                            => 'Ujian ' . $exam->type,
        };
    }
}
