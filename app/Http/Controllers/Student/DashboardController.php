<?php

namespace App\Http\Controllers\Student;

use App\Models\Grade;
use App\Models\ExamGroup;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $student = auth()->guard('student')->user()->load('classroom');

        //get exam groups
        $exam_groups = ExamGroup::with('exam', 'exam_session', 'student.classroom')
            ->where('student_id', $student->id)
            ->get();

        //wajib upload tugas sebelum ujian, kecuali skema yang dikecualikan
        //(lihat Student::requiresTugas) — tugas ini nantinya dilihat asesor
        //saat menilai wawancara peserta. Sesi yang memilih Ujian Praktik/Keterampilan wajib
        //untuk semua skema (Student::requiresTugasFor) → tugas_required_by_session.
        $requires_tugas = $student->requiresTugas();

        // Per sesi: "Tugas" atau "Ujian Praktik/Keterampilan" (sesi memilih ujian praktik/keterampilan); sudah diunggah semua bagian;
        // selesai tanpa unggahan lengkap (Ujian Praktik/Keterampilan berwaktu yang habis waktunya — ujian lain tetap terbuka);
        // dan progres bila Ujian Praktik/Keterampilan terdiri dari beberapa bagian.
        $tugas_required_by_session = [];
        $tugas_by_session         = [];
        $tugas_label_by_session   = [];
        $tugas_closed_by_session  = [];
        $tugas_progress_by_session = [];
        foreach ($exam_groups->pluck('exam_session_id')->unique() as $sid) {
            if ($student->requiresTugasFor((int) $sid)) {
                $tugas_required_by_session[$sid] = true;
                $status = \App\Support\UjianPraktik::tugasStatus((int) $sid, $student->id);
                $tugas_label_by_session[$sid] = $status['label'];
                if ($status['uploaded_all']) {
                    $tugas_by_session[$sid] = true;
                } elseif ($status['done']) {
                    $tugas_closed_by_session[$sid] = true;
                }
                if ($status['total'] > 1) {
                    $tugas_progress_by_session[$sid] = ['done' => $status['done_count'], 'total' => $status['total']];
                }
            }
        }

        //define variable array
        $data = [];

        //get nilai
        foreach($exam_groups as $exam_group) {
            
            //get data nilai / grade
            $grade = Grade::where('exam_id', $exam_group->exam_id)
                ->where('exam_session_id', $exam_group->exam_session_id)
                ->where('student_id', auth()->guard('student')->user()->id)
                ->first();

            //jika nilai / grade kosong, maka buat baru
            if($grade == null) {

                //create defaul grade
                $grade = new Grade();
                $grade->grades_code     = 'grds-' . Str::ulid();
                $grade->exam_id         = $exam_group->exam_id;
                $grade->exam_session_id = $exam_group->exam_session_id;
                $grade->student_id      = auth()->guard('student')->user()->id;
                $grade->duration        = $exam_group->exam->duration * 60000;
                $grade->total_correct   = 0;
                $grade->grade           = 0;
                $grade->save();

            }

            $data[] = [
                'exam_group' => $exam_group,
                'grade'      => $grade
            ];

        }

        //return with inertia
        return inertia('Student/Dashboard/Index', [
            'exam_groups'       => $data,
            'requires_tugas'    => $requires_tugas,
            'tugas_required_by_session' => (object) $tugas_required_by_session,
            'tugas_by_session'  => (object) $tugas_by_session,
            'tugas_label_by_session'  => (object) $tugas_label_by_session,
            'tugas_closed_by_session' => (object) $tugas_closed_by_session,
            'tugas_progress_by_session' => (object) $tugas_progress_by_session,
        ]);
    }
}