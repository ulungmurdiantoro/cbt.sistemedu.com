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
    public function show(Request $request, int $exam_session_id)
    {
        $student = auth()->guard('student')->user()->load('classroom');

        abort_unless($student->requiresTugasFor($exam_session_id), 404);

        $exam_session = ExamSession::findOrFail($exam_session_id);

        // Ujian Praktik/Keterampilan bisa dua bagian (?bagian=2); bawaan = bagian pertama yang belum selesai.
        $status = UjianPraktik::tugasStatus($exam_session_id, $student->id);
        $bagian = $this->bagian($request, $status);

        $task = StudentTask::where('student_id', $this->studentId())
            ->where('exam_session_id', $exam_session_id)
            ->where('bagian', $bagian)
            ->first();

        // Upload Tugas, atau Ujian Praktik/Keterampilan bila sesi memilih ujian praktik/keterampilan (panduan + template).
        // Ujian Praktik/Keterampilan berwaktu: panduan & template baru dikirim setelah peserta mulai dan selama waktu berjalan.
        $praktik = UjianPraktik::examFor($exam_session_id, $bagian);
        $tugas   = UjianPraktik::tugasProps($praktik);
        if (! UjianPraktik::materialsOpen($praktik, $student->id, $exam_session_id, $bagian)) {
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
            'timer'         => UjianPraktik::state($praktik, $student->id, $exam_session_id, $bagian),
            'bagian'        => $bagian,
            // Navigasi antar-bagian (hanya bila lebih dari satu bagian)
            'parts'         => $status['total'] > 1 ? array_values($status['parts']) : [],
        ]);
    }

    /** Mulai Ujian Praktik/Keterampilan berwaktu (per bagian). Sekali saja: menekan lagi tidak mereset waktu. */
    public function start(Request $request, int $exam_session_id)
    {
        $student = auth()->guard('student')->user()->load('classroom');
        $bagian  = $request->integer('bagian', 1);
        $praktik = UjianPraktik::examFor($exam_session_id, $bagian);

        abort_unless($student->requiresTugasFor($exam_session_id) && UjianPraktik::timed($praktik), 404);
        $this->abortUnlessEnrolled($exam_session_id);

        UjianPraktik::start($praktik, $student->id, $exam_session_id, $bagian);

        return redirect()->route('student.tugas.show', ['exam_session_id' => $exam_session_id] + ($bagian > 1 ? ['bagian' => $bagian] : []));
    }

    /** Unduh template Ujian Praktik/Keterampilan sesi (per bagian; hanya peserta yang terdaftar di sesi). */
    public function template(Request $request, int $exam_session_id)
    {
        $student = auth()->guard('student')->user()->load('classroom');
        $bagian  = $request->integer('bagian', 1);
        $praktik = UjianPraktik::examFor($exam_session_id, $bagian);

        abort_unless($student->requiresTugasFor($exam_session_id) && $praktik, 404);
        $this->abortUnlessEnrolled($exam_session_id);
        abort_unless($praktik->praktik_template_path, 404, 'Template belum tersedia.');
        abort_unless(
            UjianPraktik::materialsOpen($praktik, $student->id, $exam_session_id, $bagian),
            403,
            'Mulai Ujian Praktik/Keterampilan terlebih dahulu, atau waktunya sudah habis.'
        );

        return AnswerFile::download($praktik->praktik_template_path, $praktik->praktik_template_name);
    }

    public function store(Request $request)
    {
        $request->validate([
            'exam_session_id' => ['required', 'integer', 'exists:exam_sessions,id'],
            'bagian'          => ['nullable', 'integer', 'min:1'],
            'file'            => AnswerFile::rules(),
        ], AnswerFile::messages());

        $student = auth()->guard('student')->user()->load('classroom');

        $sessionId = (int) $request->exam_session_id;

        abort_unless($student->requiresTugasFor($sessionId), 403);
        $bagian    = $request->integer('bagian', 1);

        $this->abortUnlessEnrolled($sessionId);

        // Bagian harus ada di sesi (Upload Tugas biasa hanya bagian 1)
        abort_unless(array_key_exists($bagian, UjianPraktik::tugasStatus($sessionId, $student->id)['parts']), 404);

        if ($reason = UjianPraktik::uploadBlockedReason(UjianPraktik::examFor($sessionId, $bagian), $student->id, $sessionId, $bagian)) {
            abort(403, $reason);
        }

        StudentTask::storeUpload($this->studentId(), $sessionId, $request->file('file'), $bagian);

        return back()->with('success', 'Tugas berhasil diunggah.');
    }

    /** Bagian yang dibuka: ?bagian bila ada di sesi, selain itu bagian pertama yang belum selesai. */
    private function bagian(Request $request, array $status): int
    {
        $requested = $request->integer('bagian');
        if ($requested && isset($status['parts'][$requested])) {
            return $requested;
        }

        foreach ($status['parts'] as $bagian => $part) {
            if (! $part['done']) {
                return $bagian;
            }
        }

        return array_key_first($status['parts']);
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
