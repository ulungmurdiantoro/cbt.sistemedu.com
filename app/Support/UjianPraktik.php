<?php

namespace App\Support;

use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\StudentPraktikAttempt;
use App\Models\StudentTask;

/**
 * Ujian Praktik/Keterampilan = ujian berjenis Exam::TYPE_PRAKTIK yang dipilih sesi. Peserta membaca panduan, mengunduh
 * template, lalu mengunggah hasil lewat alur tugas (StudentTask). Sesi tanpa ujian praktik/keterampilan memakai "Upload Tugas" biasa.
 *
 * Satu sesi bisa punya dua bagian (mis. Lifting: Calculation lalu Gambar): bagian 1 = exam_sessions.exam_id_praktik,
 * bagian 2 = exam_id_praktik_2. Setiap bagian punya waktu mulai & unggahan sendiri (kolom `bagian` di
 * student_praktik_attempts / student_tasks); Upload Tugas biasa = bagian 1. Syarat tugas sebelum ujian lain
 * terpenuhi bila SEMUA bagian sudah diunggah atau waktunya habis (tugasStatus()).
 *
 * Bila exams.duration > 0 (berwaktu), per bagian:
 * - Waktu mulai saat peserta menekan "Mulai" (sekali, tidak bisa diulang; durasi dibekukan saat mulai).
 * - Waktu pengerjaan = durasi ujian (jam dinding, tidak dijeda). Panduan & template baru terlihat/bisa diunduh setelah mulai
 *   dan selama waktu pengerjaan.
 * - Sesudahnya waktu unggah (uploadMinutes(), lsp.praktik_waktu_unggah_menit = 10): hasil masih bisa diunggah/diganti,
 *   setelah itu unggahan ditutup. (Toleransi koneksi lsp.toleransi_koneksi_menit hanya untuk PG/Esai.)
 * - Waktu habis tanpa unggahan tidak mengunci ujian lain: bagian itu dianggap selesai.
 * - Beberapa bagian: urutan bebas, tetapi hanya satu yang berjalan (startBlockedReason): bagian lain baru bisa dimulai setelah
 *   bagian yang berjalan diunggah/habis, dan memulainya menutup bagian yang dimulai lebih dulu (laterPartStarted).
 */
class UjianPraktik
{
    /** Waktu unggah (menit) setelah waktu pengerjaan habis — config lsp.praktik_waktu_unggah_menit (10). */
    public static function uploadMinutes(): int
    {
        return max(0, (int) config('lsp.praktik_waktu_unggah_menit', 10));
    }

    /** Bagian Ujian Praktik/Keterampilan sesi: [bagian => Exam], kosong bila sesi memakai Upload Tugas. */
    public static function parts(int $sessionId): array
    {
        $session = ExamSession::with('examPraktik', 'examPraktik2')->find($sessionId);

        return array_filter(
            [1 => $session?->examPraktik, 2 => $session?->examPraktik ? $session->examPraktik2 : null],
            fn (?Exam $exam) => $exam?->isPraktik()
        );
    }

    /** Judul per bagian ({bagian: judul}) — hanya bila sesi punya lebih dari satu bagian, selain itu objek kosong. */
    public static function partTitles(int $sessionId): object
    {
        $parts = self::parts($sessionId);

        return (object) (count($parts) > 1 ? array_map(fn (Exam $exam) => $exam->title, $parts) : []);
    }

    /** Ujian praktik bagian $bagian sesi (null bila tidak ada / sesi memakai Upload Tugas). */
    public static function examFor(int $sessionId, int $bagian = 1): ?Exam
    {
        return self::parts($sessionId)[$bagian] ?? null;
    }

    public static function timed(?Exam $exam): bool
    {
        return $exam !== null && (int) $exam->duration > 0;
    }

    /**
     * Ujian Praktik/Keterampilan berwaktu atau yang terdiri dari beberapa bagian hanya dikerjakan dari portal ujian
     * (waktu mulai & unggahan per bagian dicatat di sana), bukan dari dashboard portal peserta.
     */
    public static function examPortalOnly(int $sessionId): bool
    {
        $parts = self::parts($sessionId);

        return count($parts) > 1 || self::timed($parts[1] ?? null);
    }

    /** Data tugas untuk halaman peserta. Template hanya disebut namanya; filenya lewat controller. */
    public static function tugasProps(?Exam $exam): array
    {
        if (! $exam) {
            return ['praktik' => false, 'label' => 'Tugas', 'panduan' => null, 'template_name' => null, 'durasi' => null];
        }

        return [
            'praktik'       => true,
            'label'         => 'Ujian Praktik/Keterampilan',
            'title'         => $exam->title,
            'panduan'       => $exam->praktik_panduan,
            'template_name' => $exam->praktik_template_path ? $exam->praktik_template_name : null,
            'durasi'        => self::timed($exam) ? (int) $exam->duration : null,
        ];
    }

    public static function attempt(int $studentId, int $sessionId, int $bagian = 1): ?StudentPraktikAttempt
    {
        return StudentPraktikAttempt::where('student_id', $studentId)
            ->where('exam_session_id', $sessionId)
            ->where('bagian', $bagian)
            ->first();
    }

    /** Mulai (atau ambil waktu mulai yang sudah ada — memulai ulang tidak mereset waktu). */
    public static function start(Exam $exam, int $studentId, int $sessionId, int $bagian = 1): StudentPraktikAttempt
    {
        return StudentPraktikAttempt::firstOrCreate(
            ['student_id' => $studentId, 'exam_session_id' => $sessionId, 'bagian' => $bagian],
            ['exam_id' => $exam->id, 'started_at' => now(), 'durasi' => (int) $exam->duration]
        );
    }

    /** Bagian yang sedang berjalan (sudah dimulai, belum diunggah, waktunya belum habis), selain $kecuali; null bila tidak ada. */
    public static function runningPart(int $sessionId, int $studentId, ?int $kecuali = null): ?int
    {
        foreach (self::tugasStatus($sessionId, $studentId)['parts'] as $b => $part) {
            if ($b !== $kecuali && $part['started'] && ! $part['done']) {
                return $b;
            }
        }

        return null;
    }

    /**
     * Urutan bagian bebas, tetapi hanya satu bagian yang boleh berjalan: bagian lain baru bisa dimulai setelah bagian yang sedang
     * berjalan diunggah atau waktunya habis. Null = boleh dimulai; selain itu pesan untuk peserta.
     */
    public static function startBlockedReason(int $sessionId, int $studentId, int $bagian): ?string
    {
        // Satu ujian berjalan per sesi — termasuk PG/Esai yang belum diakhiri (UjianBerjalan)
        return UjianBerjalan::blockedReason($sessionId, $studentId, null, $bagian);
    }

    /**
     * Bagian lain dimulai SETELAH bagian ini → bagian ini ditutup (template & unggahan), supaya dua waktu tidak berjalan bersamaan.
     * Urutan mulai dilihat dari id percobaan (dibuat saat menekan Mulai).
     */
    public static function laterPartStarted(int $studentId, int $sessionId, int $bagian): bool
    {
        $own = self::attempt($studentId, $sessionId, $bagian);

        if ($own === null) {
            return false;
        }

        // Bagian praktik lain, atau ujian PG/Esai, yang dimulai sesudah bagian ini
        return StudentPraktikAttempt::where('student_id', $studentId)
                ->where('exam_session_id', $sessionId)
                ->where('bagian', '!=', $bagian)
                ->where('id', '>', $own->id)
                ->exists()
            || \App\Models\Grade::where('student_id', $studentId)
                ->where('exam_session_id', $sessionId)
                ->where('start_time', '>', $own->started_at)
                ->exists();
    }

    /** Bagian yang akan ditutup bila $bagian dimulai sekarang: sudah dimulai dan belum ditutup (mis. sudah diunggah, waktu masih ada). */
    public static function partsClosedByStarting(int $sessionId, int $studentId, int $bagian): array
    {
        $out = [];
        foreach (self::parts($sessionId) as $b => $exam) {
            $attempt = $b === $bagian ? null : self::attempt($studentId, $sessionId, $b);
            if ($attempt && ! self::closed($exam, $studentId, $sessionId, $b)) {
                $out[] = $b;
            }
        }

        return $out;
    }

    /** Status untuk halaman peserta. Sisa waktu dihitung server, jadi tidak bergantung jam/zona waktu browser. */
    public static function state(?Exam $exam, int $studentId, int $sessionId, int $bagian = 1): array
    {
        if (! self::timed($exam)) {
            return ['timed' => false];
        }

        $attempt = self::attempt($studentId, $sessionId, $bagian);

        $uploadEnds = $attempt?->deadline()->addMinutes(self::uploadMinutes());
        $closedByNext = $attempt !== null && self::laterPartStarted($studentId, $sessionId, $bagian);

        return [
            'timed'                    => true,
            // Dua durasi: waktu pengerjaan (durasi ujian) lalu waktu unggah
            'durasi'                   => $attempt?->durasi ?? (int) $exam->duration,
            'waktu_unggah'             => self::uploadMinutes(),
            'started'                  => $attempt !== null,
            // Waktu pengerjaan habis (template ditutup; masih bisa unggah selama waktu unggah)
            'expired'                  => $attempt !== null && now()->greaterThanOrEqualTo($attempt->deadline()),
            'remaining_seconds'        => $attempt ? max(0, (int) now()->diffInSeconds($attempt->deadline(), false)) : null,
            // Waktu unggah juga habis: unggahan ditutup
            'closed'                   => $attempt !== null && ($closedByNext || now()->greaterThan($uploadEnds)),
            'upload_remaining_seconds' => $attempt && ! $closedByNext ? max(0, (int) now()->diffInSeconds($uploadEnds, false)) : 0,
            // Ditutup lebih awal karena bagian lain dimulai sesudahnya
            'closed_by_next'           => $closedByNext,
            // Belum boleh dimulai: bagian sebelumnya belum selesai
            'start_blocked'            => $attempt ? null : self::startBlockedReason($sessionId, $studentId, $bagian),
            // Bagian lain yang akan ditutup bila bagian ini dimulai (peringatan sebelum Mulai)
            'closes_parts'             => $attempt ? [] : self::partsClosedByStarting($sessionId, $studentId, $bagian),
        ];
    }

    /** Panduan & template hanya untuk peserta yang sudah mulai dan waktunya belum habis. */
    public static function materialsOpen(?Exam $exam, int $studentId, int $sessionId, int $bagian = 1): bool
    {
        if (! self::timed($exam)) {
            return true;
        }

        $attempt = self::attempt($studentId, $sessionId, $bagian);

        return $attempt !== null && now()->lessThan($attempt->deadline())
            && ! self::laterPartStarted($studentId, $sessionId, $bagian);
    }

    /** Null = boleh unggah; selain itu pesan penolakan. */
    public static function uploadBlockedReason(?Exam $exam, int $studentId, int $sessionId, int $bagian = 1): ?string
    {
        if (! self::timed($exam)) {
            return null;
        }

        $attempt = self::attempt($studentId, $sessionId, $bagian);

        if (! $attempt) {
            return 'Mulai Ujian Praktik/Keterampilan terlebih dahulu.';
        }

        if (now()->greaterThan($attempt->deadline()->addMinutes(self::uploadMinutes()))) {
            return 'Waktu Ujian Praktik/Keterampilan sudah habis. Hasil tidak dapat diunggah lagi.';
        }

        if (self::laterPartStarted($studentId, $sessionId, $bagian)) {
            return "Ujian lain sudah dimulai. Hasil Bagian {$bagian} tidak dapat diubah lagi.";
        }

        return null;
    }

    /** Waktu habis (lewat toleransi) → bagian ini dianggap selesai walau tanpa unggahan. */
    public static function closed(?Exam $exam, int $studentId, int $sessionId, int $bagian = 1): bool
    {
        if (! self::timed($exam)) {
            return false;
        }

        $attempt = self::attempt($studentId, $sessionId, $bagian);

        return $attempt !== null && (now()->greaterThan($attempt->deadline()->addMinutes(self::uploadMinutes()))
            || self::laterPartStarted($studentId, $sessionId, $bagian));
    }

    /**
     * Status tugas peserta di satu sesi, per bagian. Upload Tugas biasa = satu bagian tanpa ujian.
     *
     * @return array{label: string, parts: array<int, array>, uploaded_all: bool, done: bool, done_count: int, total: int}
     */
    public static function tugasStatus(int $sessionId, int $studentId): array
    {
        $exams = self::parts($sessionId) ?: [1 => null];
        $uploaded = StudentTask::where('student_id', $studentId)
            ->where('exam_session_id', $sessionId)
            ->pluck('bagian')
            ->map(fn ($b) => (int) $b)
            ->all();

        $parts = [];
        foreach ($exams as $bagian => $exam) {
            $isUploaded = in_array($bagian, $uploaded, true);
            $closed = ! $isUploaded && self::closed($exam, $studentId, $sessionId, $bagian);
            $attempt = self::timed($exam) ? self::attempt($studentId, $sessionId, $bagian) : null;
            $parts[$bagian] = [
                'bagian'   => $bagian,
                'title'    => $exam?->title,
                'durasi'   => self::timed($exam) ? (int) $exam->duration : null,
                'started'  => $attempt !== null,
                'uploaded' => $isUploaded,
                'closed'   => $closed,
                'done'     => $isUploaded || $closed,
            ];
        }

        $doneCount = count(array_filter($parts, fn ($p) => $p['done']));

        return [
            'label'        => $exams[1] ? 'Ujian Praktik/Keterampilan' : 'Tugas',
            'parts'        => $parts,
            'uploaded_all' => ! in_array(false, array_column($parts, 'uploaded'), true),
            'done'         => $doneCount === count($parts),
            'done_count'   => $doneCount,
            'total'        => count($parts),
        ];
    }

    /** Syarat "tugas sebelum ujian" terpenuhi: semua bagian sudah diunggah atau waktunya habis. */
    public static function tugasDone(int $sessionId, int $studentId): bool
    {
        return self::tugasStatus($sessionId, $studentId)['done'];
    }
}
