<?php

namespace App\Http\Controllers\Student;

use Carbon\Carbon;
use App\Models\Essay;
use App\Models\Grade;
use App\Models\Question;
use App\Models\ExamGroup;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

abstract class BaseExamController extends Controller
{
    /** Toleransi (menit) di atas durasi ujian untuk gangguan koneksi peserta — config lsp.toleransi_koneksi_menit (30). */
    protected function graceMinutes(): int
    {
        return \App\Support\UjianBerjalan::graceMinutes();
    }

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

        if (! $student->requiresTugasFor($exam_group->exam_session_id)) {
            return null;
        }

        // Sesi dengan Ujian Praktik/Keterampilan: PG/Esai tidak dikunci — praktik dikerjakan lewat kartunya sendiri, urutan bebas.
        // Hanya "Upload Tugas" biasa yang tetap jadi syarat sebelum ujian.
        if (\App\Support\UjianPraktik::parts($exam_group->exam_session_id)) {
            return null;
        }

        // Semua bagian Ujian Praktik/Keterampilan sudah diunggah — atau waktunya habis (tidak mengunci ujian lain walau tanpa unggahan).
        if (\App\Support\UjianPraktik::tugasDone($exam_group->exam_session_id, $student->id)) {
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
        return \App\Support\UjianBerjalan::remainingMs($grade);
    }

    /**
     * Satu ujian berjalan per sesi: ujian ini belum dimulai dan ujian lain sedang berjalan → kembali ke dashboard.
     * Ujian yang sudah dimulai selalu boleh dilanjutkan.
     */
    protected function otherExamRunningRedirect(ExamGroup $exam_group): ?RedirectResponse
    {
        $grade = $this->currentGrade($exam_group->exam_id, $exam_group->exam_session_id);
        if ($grade?->start_time) {
            return null;
        }

        $reason = \App\Support\UjianBerjalan::blockedReason($exam_group->exam_session_id, $this->studentId(), (int) $exam_group->exam_id);

        return $reason ? redirect()->route('student.dashboard')->with('error', $reason) : null;
    }

    /** Untuk halaman konfirmasi: alasan terkunci + bagian praktik yang ditutup bila ujian ini dimulai. */
    protected function confirmationLock(ExamGroup $exam_group, ?Grade $grade): array
    {
        if ($grade?->start_time || $grade?->end_time) {
            return ['blocked' => null, 'closes' => []];
        }

        $sid = (int) $exam_group->exam_session_id;

        return [
            'blocked' => \App\Support\UjianBerjalan::blockedReason($sid, $this->studentId(), (int) $exam_group->exam_id),
            // Bagian praktik yang sudah diunggah tetapi waktunya masih ada → ditutup bila ujian ini dimulai
            'closes'  => \App\Support\UjianPraktik::partsClosedByStarting($sid, $this->studentId(), 0),
        ];
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
