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
 * - Panduan & template baru terlihat/bisa diunduh setelah mulai dan selama waktu berjalan.
 * - Unggah diterima sampai batas waktu + UPLOAD_GRACE_MINUTES, setelah itu ditutup.
 * - Waktu habis tanpa unggahan tidak mengunci ujian lain: bagian itu dianggap selesai.
 */
class UjianPraktik
{
    public const UPLOAD_GRACE_MINUTES = 2;

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

    /** Status untuk halaman peserta. Sisa waktu dihitung server, jadi tidak bergantung jam/zona waktu browser. */
    public static function state(?Exam $exam, int $studentId, int $sessionId, int $bagian = 1): array
    {
        if (! self::timed($exam)) {
            return ['timed' => false];
        }

        $attempt = self::attempt($studentId, $sessionId, $bagian);

        return [
            'timed'             => true,
            'durasi'            => $attempt?->durasi ?? (int) $exam->duration,
            'started'           => $attempt !== null,
            'expired'           => $attempt !== null && now()->greaterThanOrEqualTo($attempt->deadline()),
            'remaining_seconds' => $attempt ? max(0, now()->diffInSeconds($attempt->deadline(), false)) : null,
        ];
    }

    /** Panduan & template hanya untuk peserta yang sudah mulai dan waktunya belum habis. */
    public static function materialsOpen(?Exam $exam, int $studentId, int $sessionId, int $bagian = 1): bool
    {
        if (! self::timed($exam)) {
            return true;
        }

        $attempt = self::attempt($studentId, $sessionId, $bagian);

        return $attempt !== null && now()->lessThan($attempt->deadline());
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

        if (now()->greaterThan($attempt->deadline()->addMinutes(self::UPLOAD_GRACE_MINUTES))) {
            return 'Waktu Ujian Praktik/Keterampilan sudah habis. Hasil tidak dapat diunggah lagi.';
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

        return $attempt !== null && now()->greaterThan($attempt->deadline()->addMinutes(self::UPLOAD_GRACE_MINUTES));
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
