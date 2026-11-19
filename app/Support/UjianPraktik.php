<?php

namespace App\Support;

use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\StudentPraktikAttempt;

/**
 * Ujian Praktik = ujian berjenis Exam::TYPE_PRAKTIK yang dipilih sesi (exam_sessions.exam_id_praktik).
 * Peserta membaca panduan, mengunduh template, lalu mengunggah hasil lewat alur tugas (StudentTask).
 * Sesi tanpa ujian praktik memakai "Upload Tugas" biasa.
 *
 * Bila exams.duration > 0 (berwaktu):
 * - Waktu mulai saat peserta menekan "Mulai Ujian Praktik" (sekali per sesi, tidak bisa diulang;
 *   durasi dibekukan saat mulai).
 * - Panduan & template baru terlihat/bisa diunduh setelah mulai dan selama waktu berjalan.
 * - Unggah diterima sampai batas waktu + UPLOAD_GRACE_MINUTES, setelah itu ditutup.
 * - Waktu habis tanpa unggahan tidak mengunci ujian lain: syarat tugas dianggap selesai.
 */
class UjianPraktik
{
    public const UPLOAD_GRACE_MINUTES = 2;

    /** Ujian praktik sesi (null bila sesi memakai Upload Tugas). */
    public static function examFor(int $sessionId): ?Exam
    {
        $exam = ExamSession::find($sessionId)?->examPraktik;

        return $exam?->isPraktik() ? $exam : null;
    }

    public static function timed(?Exam $exam): bool
    {
        return $exam !== null && (int) $exam->duration > 0;
    }

    /** Data tugas untuk halaman peserta. Template hanya disebut namanya; filenya lewat controller. */
    public static function tugasProps(?Exam $exam): array
    {
        if (! $exam) {
            return ['praktik' => false, 'label' => 'Tugas', 'panduan' => null, 'template_name' => null, 'durasi' => null];
        }

        return [
            'praktik'       => true,
            'label'         => 'Ujian Praktik',
            'title'         => $exam->title,
            'panduan'       => $exam->praktik_panduan,
            'template_name' => $exam->praktik_template_path ? $exam->praktik_template_name : null,
            'durasi'        => self::timed($exam) ? (int) $exam->duration : null,
        ];
    }

    public static function attempt(int $studentId, int $sessionId): ?StudentPraktikAttempt
    {
        return StudentPraktikAttempt::where('student_id', $studentId)->where('exam_session_id', $sessionId)->first();
    }

    /** Mulai (atau ambil waktu mulai yang sudah ada — memulai ulang tidak mereset waktu). */
    public static function start(Exam $exam, int $studentId, int $sessionId): StudentPraktikAttempt
    {
        return StudentPraktikAttempt::firstOrCreate(
            ['student_id' => $studentId, 'exam_session_id' => $sessionId],
            ['exam_id' => $exam->id, 'started_at' => now(), 'durasi' => (int) $exam->duration]
        );
    }

    /** Status untuk halaman peserta. Sisa waktu dihitung server, jadi tidak bergantung jam/zona waktu browser. */
    public static function state(?Exam $exam, int $studentId, int $sessionId): array
    {
        if (! self::timed($exam)) {
            return ['timed' => false];
        }

        $attempt = self::attempt($studentId, $sessionId);

        return [
            'timed'             => true,
            'durasi'            => $attempt?->durasi ?? (int) $exam->duration,
            'started'           => $attempt !== null,
            'expired'           => $attempt !== null && now()->greaterThanOrEqualTo($attempt->deadline()),
            'remaining_seconds' => $attempt ? max(0, now()->diffInSeconds($attempt->deadline(), false)) : null,
        ];
    }

    /** Panduan & template hanya untuk peserta yang sudah mulai dan waktunya belum habis. */
    public static function materialsOpen(?Exam $exam, int $studentId, int $sessionId): bool
    {
        if (! self::timed($exam)) {
            return true;
        }

        $attempt = self::attempt($studentId, $sessionId);

        return $attempt !== null && now()->lessThan($attempt->deadline());
    }

    /** Null = boleh unggah; selain itu pesan penolakan. */
    public static function uploadBlockedReason(?Exam $exam, int $studentId, int $sessionId): ?string
    {
        if (! self::timed($exam)) {
            return null;
        }

        $attempt = self::attempt($studentId, $sessionId);

        if (! $attempt) {
            return 'Mulai Ujian Praktik terlebih dahulu.';
        }

        if (now()->greaterThan($attempt->deadline()->addMinutes(self::UPLOAD_GRACE_MINUTES))) {
            return 'Waktu Ujian Praktik sudah habis. Hasil tidak dapat diunggah lagi.';
        }

        return null;
    }

    /** Waktu habis (lewat toleransi) → syarat tugas sebelum ujian dianggap selesai walau tanpa unggahan. */
    public static function closed(?Exam $exam, int $studentId, int $sessionId): bool
    {
        if (! self::timed($exam)) {
            return false;
        }

        $attempt = self::attempt($studentId, $sessionId);

        return $attempt !== null && now()->greaterThan($attempt->deadline()->addMinutes(self::UPLOAD_GRACE_MINUTES));
    }
}
