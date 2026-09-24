<?php

namespace App\Http\Controllers\Student;

use App\Models\ExamGroup;
use App\Models\ExamSession;
use App\Models\StudentTask;
use App\Support\AnswerFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StudentTaskController extends BaseExamController
{
    public function show(int $exam_session_id)
    {
        $student = auth()->guard('student')->user()->load('classroom');

        abort_unless($student->requiresTugas(), 404);

        $exam_session = ExamSession::findOrFail($exam_session_id);

        $task = StudentTask::where('student_id', $this->studentId())
            ->where('exam_session_id', $exam_session_id)
            ->first();

        return inertia('Student/Tugas/Show', [
            'exam_session'  => $exam_session,
            'existing_file' => $task ? [
                'name'        => $task->original_filename,
                'uploaded_at' => $task->uploaded_at,
            ] : null,
            'file_accept'   => AnswerFile::accept(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'exam_session_id' => ['required', 'integer', 'exists:exam_sessions,id'],
            'file'            => AnswerFile::rules(),
        ], AnswerFile::messages());

        $student = auth()->guard('student')->user()->load('classroom');

        abort_unless($student->requiresTugas(), 403);

        $sessionId = (int) $request->exam_session_id;

        abort_unless(
            ExamGroup::where('student_id', $this->studentId())->where('exam_session_id', $sessionId)->exists(),
            403,
            'Anda tidak terdaftar pada sesi ini.'
        );

        $file         = $request->file('file');
        $originalName = $file->getClientOriginalName();

        $existing = StudentTask::where('student_id', $this->studentId())
            ->where('exam_session_id', $sessionId)
            ->first();

        if ($existing && Storage::disk('private')->exists($existing->file_path)) {
            Storage::disk('private')->delete($existing->file_path);
        }

        $storedPath = AnswerFile::store($file, "student_tasks/{$sessionId}/{$this->studentId()}");

        StudentTask::updateOrCreate(
            ['student_id' => $this->studentId(), 'exam_session_id' => $sessionId],
            [
                'file_path'         => $storedPath,
                'original_filename' => $originalName,
                'file_size'         => $file->getSize(),
                'uploaded_at'       => now(),
            ]
        );

        return back()->with('success', 'Tugas berhasil diunggah.');
    }
}
