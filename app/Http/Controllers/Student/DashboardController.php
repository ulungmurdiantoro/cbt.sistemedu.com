<?php

namespace App\Http\Controllers\Student;

use App\Models\Grade;
use App\Models\ExamGroup;
use App\Models\StudentTask;
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
        //saat menilai wawancara peserta
        $requires_tugas = $student->requiresTugas();

        $tugas_by_session = $requires_tugas
            ? StudentTask::where('student_id', $student->id)->get()->keyBy('exam_session_id')
            : collect();

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
            'tugas_by_session'  => $tugas_by_session,
        ]);
    }
}