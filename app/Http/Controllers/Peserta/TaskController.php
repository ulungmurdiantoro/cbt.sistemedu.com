<?php

namespace App\Http\Controllers\Peserta;

use App\Http\Controllers\Controller;
use App\Models\AssessmentApplication;
use App\Models\ExamGroup;
use App\Models\StudentTask;
use App\Support\AnswerFile;
use Illuminate\Http\Request;

// Upload tugas dari dashboard portal peserta — sama dengan student/tugas/{sesi} di portal
// ujian. Disimpan ke akun ujian (student) permohonan ini, jadi langsung memenuhi syarat
// "wajib upload tugas sebelum ujian" dan terlihat asesor saat menilai wawancara.
class TaskController extends Controller
{
    public function store(Request $request, AssessmentApplication $application)
    {
        abort_if($application->participant_id !== auth()->guard('participant')->id(), 403);

        $student = $application->student()->with('classroom')->first();

        abort_unless($application->isApproved() && $student && $application->exam_session_id, 403, 'Permohonan belum disetujui.');
        abort_unless($student->requiresTugas(), 404);
        abort_unless(
            ExamGroup::where('student_id', $student->id)->where('exam_session_id', $application->exam_session_id)->exists(),
            403,
            'Akun ujian Anda tidak terdaftar pada sesi ini.'
        );

        $request->validate(['file' => AnswerFile::rules()], AnswerFile::messages());

        StudentTask::storeUpload($student->id, $application->exam_session_id, $request->file('file'));

        return back()->with('success', 'Tugas berhasil diunggah.');
    }
}
