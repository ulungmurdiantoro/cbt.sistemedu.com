<?php

namespace App\Http\Controllers\Student;

use App\Models\ExamGroup;
use App\Models\ExamSession;
use App\Models\StudentTask;
use App\Support\AnswerFile;
use App\Support\UjianPraktik;
use Illuminate\Http\Request;

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

        // Upload Tugas, atau Ujian Praktik bila sesi memilih ujian praktik (panduan + template).
        // Ujian Praktik berwaktu: panduan & template baru dikirim setelah peserta mulai dan selama waktu berjalan.
        $praktik = UjianPraktik::examFor($exam_session_id);
        $tugas   = UjianPraktik::tugasProps($praktik);
        if (! UjianPraktik::materialsOpen($praktik, $student->id, $exam_session_id)) {
            $tugas['panduan']       = null;
            $tugas['template_name'] = null;
        }

        return inertia('Student/Tugas/Show', [
            'exam_session'  => $exam_session->only(['id', 'title']),
            'existing_file' => $task ? [
                'name'        => $task->original_filename,
                'uploaded_at' => $task->uploaded_at,
            ] : null,
            'file_accept'   => AnswerFile::accept(),
            'tugas'         => $tugas,
            'timer'         => UjianPraktik::state($praktik, $student->id, $exam_session_id),
        ]);
    }

    /** Mulai Ujian Praktik berwaktu. Sekali per sesi: menekan lagi tidak mereset waktu. */
    public function start(int $exam_session_id)
    {
        $student = auth()->guard('student')->user()->load('classroom');
        $praktik = UjianPraktik::examFor($exam_session_id);

        abort_unless($student->requiresTugas() && UjianPraktik::timed($praktik), 404);
        $this->abortUnlessEnrolled($exam_session_id);

        UjianPraktik::start($praktik, $student->id, $exam_session_id);

        return redirect()->route('student.tugas.show', $exam_session_id);
    }

    /** Unduh template Ujian Praktik sesi (hanya peserta yang terdaftar di sesi). */
    public function template(int $exam_session_id)
    {
        $student = auth()->guard('student')->user()->load('classroom');
        $praktik = UjianPraktik::examFor($exam_session_id);

        abort_unless($student->requiresTugas() && $praktik, 404);
        $this->abortUnlessEnrolled($exam_session_id);
        abort_unless($praktik->praktik_template_path, 404, 'Template belum tersedia.');
        abort_unless(
            UjianPraktik::materialsOpen($praktik, $student->id, $exam_session_id),
            403,
            'Mulai Ujian Praktik terlebih dahulu, atau waktunya sudah habis.'
        );

        return AnswerFile::download($praktik->praktik_template_path, $praktik->praktik_template_name);
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

        $this->abortUnlessEnrolled($sessionId);

        if ($reason = UjianPraktik::uploadBlockedReason(UjianPraktik::examFor($sessionId), $student->id, $sessionId)) {
            abort(403, $reason);
        }

        StudentTask::storeUpload($this->studentId(), $sessionId, $request->file('file'));

        return back()->with('success', 'Tugas berhasil diunggah.');
    }

    private function abortUnlessEnrolled(int $sessionId): void
    {
        abort_unless(
            ExamGroup::where('student_id', $this->studentId())->where('exam_session_id', $sessionId)->exists(),
            403,
            'Anda tidak terdaftar pada sesi ini.'
        );
    }
}
