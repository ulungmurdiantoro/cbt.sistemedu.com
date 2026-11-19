<?php

namespace App\Http\Controllers\Peserta;

use App\Http\Controllers\Controller;
use App\Models\AssessmentApplication;
use App\Models\ExamGroup;
use App\Models\StudentTask;
use App\Support\AnswerFile;
use App\Support\UjianPraktik;
use Illuminate\Http\Request;

// Upload tugas dari dashboard portal peserta — sama dengan student/tugas/{sesi} di portal
// ujian. Disimpan ke akun ujian (student) permohonan ini, jadi langsung memenuhi syarat
// "wajib upload tugas sebelum ujian" dan terlihat asesor saat menilai wawancara.
class TaskController extends Controller
{
    private const TIMED_MESSAGE = 'Ujian Praktik ini berbatas waktu dan dikerjakan dari portal ujian (login dengan No. Peserta).';

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

        // Ujian Praktik berwaktu hanya dikerjakan dari portal ujian (waktu mulai dicatat di sana).
        if (UjianPraktik::timed(UjianPraktik::examFor($application->exam_session_id))) {
            return back()->withErrors(['file' => self::TIMED_MESSAGE]);
        }

        $request->validate(['file' => AnswerFile::rules()], AnswerFile::messages());

        StudentTask::storeUpload($student->id, $application->exam_session_id, $request->file('file'));

        return back()->with('success', 'Tugas berhasil diunggah.');
    }

    /** Unduh template Ujian Praktik sesi permohonan ini (hanya Ujian Praktik tanpa batas waktu). */
    public function template(AssessmentApplication $application)
    {
        abort_if($application->participant_id !== auth()->guard('participant')->id(), 403);

        $student = $application->student()->with('classroom')->first();

        abort_unless($application->isApproved() && $student && $application->exam_session_id, 403, 'Permohonan belum disetujui.');

        $praktik = UjianPraktik::examFor($application->exam_session_id);

        abort_unless($student->requiresTugas() && $praktik, 404);
        abort_unless(
            ExamGroup::where('student_id', $student->id)->where('exam_session_id', $application->exam_session_id)->exists(),
            403,
            'Akun ujian Anda tidak terdaftar pada sesi ini.'
        );
        abort_unless($praktik->praktik_template_path, 404, 'Template belum tersedia.');
        abort_if(UjianPraktik::timed($praktik), 403, self::TIMED_MESSAGE);

        return AnswerFile::download($praktik->praktik_template_path, $praktik->praktik_template_name);
    }
}
