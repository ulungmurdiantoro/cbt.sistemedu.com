<?php

namespace App\Http\Controllers\Student;

use App\Models\Grade;
use App\Models\ExamGroup;
use App\Http\Controllers\Controller;

abstract class BaseExamController extends Controller
{
    protected function studentId(): int
    {
        return (int) auth()->guard('student')->user()->id;
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
