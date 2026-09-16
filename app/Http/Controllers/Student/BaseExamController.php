<?php

namespace App\Http\Controllers\Student;

use App\Models\Grade;
use App\Models\ExamGroup;
use App\Models\StudentTask;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

abstract class BaseExamController extends Controller
{
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
}
