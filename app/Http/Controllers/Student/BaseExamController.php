<?php

namespace App\Http\Controllers\Student;

use Carbon\Carbon;
use App\Models\Essay;
use App\Models\Grade;
use App\Models\Question;
use App\Models\ExamGroup;
use App\Models\StudentTask;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

abstract class BaseExamController extends Controller
{
    /** Toleransi (menit) di atas durasi ujian untuk gangguan koneksi peserta. */
    protected const GRACE_MINUTES = 30;

    protected function studentId(): int
    {
        return (int) auth()->guard('student')->user()->id;
    }

    /**
     * Peserta wajib mengunggah tugas (kecuali skema yang dikecualikan, lihat
     * Student::requiresTugas) sebelum bisa mengerjakan ujian pada sesi ini.
     * Tugas ini nantinya dilihat asesor saat menilai wawancara peserta.
     */
    protected function tugasRedirectIfRequired(ExamGroup $exam_group): ?RedirectResponse
    {
        $student = $exam_group->student;

        if (! $student->requiresTugas()) {
            return null;
        }

        $uploaded = StudentTask::where('student_id', $student->id)
            ->where('exam_session_id', $exam_group->exam_session_id)
            ->exists();

        if ($uploaded) {
            return null;
        }

        return redirect()
            ->route('student.tugas.show', $exam_group->exam_session_id)
            ->with('error', 'Anda wajib mengunggah tugas terlebih dahulu sebelum mengerjakan ujian ini.');
    }

    protected function currentGrade(int $examId, int $sessionId): ?Grade
    {
        return Grade::where('exam_id', $examId)
            ->where('exam_session_id', $sessionId)
            ->where('student_id', $this->studentId())
            ->first();
    }

    protected function examGroup(int $groupId): ?ExamGroup
    {
        return ExamGroup::with('exam', 'exam_session', 'student.classroom')
            ->where('student_id', $this->studentId())
            ->where('id', $groupId)
            ->first();
    }

    protected function ownedGrade(int $gradeId): Grade
    {
        return Grade::where('id', $gradeId)
            ->where('student_id', $this->studentId())
            ->firstOrFail();
    }

    /**
     * Sisa waktu (ms) menurut server. grades.duration dikurangi oleh timer klien
     * (berhenti saat peserta offline), jadi dibatasi lagi dengan jam dinding:
     * start_time + durasi ujian + toleransi gangguan koneksi.
     */
    protected function remainingMs(Grade $grade): int
    {
        $stored = max(0, (int) $grade->duration);

        if (! $grade->start_time) {
            return $stored;
        }

        $deadline = Carbon::parse($grade->start_time)
            ->addMinutes((int) $grade->exam->duration + self::GRACE_MINUTES);

        return (int) min($stored, max(0, now()->diffInMilliseconds($deadline, false)));
    }

    /** Ujian sedang berjalan: sudah dimulai, belum diakhiri, dan waktu masih ada. */
    protected function acceptsAnswers(?Grade $grade): bool
    {
        return $grade
            && $grade->start_time !== null
            && $grade->end_time === null
            && $this->remainingMs($grade) > 0;
    }

    /** Durasi dari klien hanya boleh mengurangi sisa waktu, tidak pernah menambah. */
    protected function syncDuration(Grade $grade, mixed $reported): void
    {
        if (! is_numeric($reported)) {
            return;
        }

        $grade->duration = max(0, min((int) $reported, (int) $grade->duration));
        $grade->save();
    }

    /** Grade untuk dikirim ke halaman ujian, dengan duration = sisa waktu versi server. */
    protected function gradeForTimer(Grade $grade): Grade
    {
        $grade->duration = $this->remainingMs($grade);

        return $grade;
    }

    /** Kolom relasi soal yang aman dikirim ke browser peserta (tanpa kunci jawaban). */
    protected function questionForStudent(): \Closure
    {
        return fn ($q) => $q->select(Question::STUDENT_COLUMNS);
    }

    protected function essayForStudent(): \Closure
    {
        return fn ($q) => $q->select(Essay::STUDENT_COLUMNS);
    }
}
