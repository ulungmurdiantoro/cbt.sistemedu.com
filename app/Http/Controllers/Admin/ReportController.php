<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use App\Models\Exam;
use App\Models\Grade;
use App\Models\ExamSession;
use Illuminate\Http\Request;
use App\Exports\GradesExport;
use App\Exports\GradesEssayExport;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{    
    /**
     * index
     *
     * @return void
     */
    public function index()
    {
        //geta ll exams
        $exams = Exam::with( 'classroom')->get();

        return inertia('Admin/Reports/Index', [
            'exams'         => $exams,
            'grades'        => []
        ]);
    }
    
    /**
     * filter
     *
     * @param  mixed $request
     * @return void
     */
    public function filter(Request $request)
    {
        $request->validate([
            'exam_id'       => 'required',
        ]);

        //geta ll exams
        $exams = Exam::with('classroom')->get();

        //get exam
        $exam = Exam::with('classroom')
                ->where('id', $request->exam_id)
                ->first();

        if($exam) {

            //get exam session
            $exam_session = ExamSession::where('exam_id', $exam->id)->first();

            //get grades / nilai
            $grades = Grade::with('student', 'exam.classroom', 'exam_session')
                    ->where('exam_id', $exam->id)
                    ->where('exam_session_id', $exam_session->id)        
                    ->get();

        } else {
            $grades = [];
        }        
        // dd($grades);
        return inertia('Admin/Reports/Index', [
            'exams'         => $exams,
            'grades'         => $grades,
        ]);
        
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //get grade
        $grade = Grade::with('student', 'exam.classroom', 'questions.answers', 'answers', 'exam_session')
        ->findOrFail($id);

        $grade->setRelation('questions', $grade->exam->questions()->paginate(10));
        $grade->setRelation('answers', $grade->exam->answers()->where('student_id', $grade->student_id)->paginate(10));
        $grade->setRelation('essays', $grade->exam->essays()->paginate(10));
        $grade->setRelation('essaysanswers', $grade->exam->essaysanswers()->where('student_id', $grade->student_id)->paginate(10));
        // dd($grade->exam->essaysanswers);
        return inertia('Admin/Reports/Show', [
            'grade' => $grade,
        ]);
    }

    /**
     * export
     *
     * @param  mixed $request
     * @return void
     */
    public function export(Request $request)
    {
        //get exam
        $exam = Exam::with('classroom')
                ->where('id', $request->exam_id)
                ->first();

        //get exam session
        $exam_session = ExamSession::where('exam_id', $exam->id)->first();

        //get grades / nilai
        $grades = Grade::with('student', 'exam.classroom', 'exam_session', 'answers')
                ->where('exam_id', $exam->id)
                ->where('exam_session_id', $exam_session->id)        
                ->get();

        if ($exam->type == 'Essay') {
            return Excel::download(new GradesEssayExport($grades), 'essay_grades_' . $exam->title . ' — ' . Carbon::now() . '.xlsx');
        } else {
            return Excel::download(new GradesExport($grades), 'grades_' . $exam->title . ' — ' . Carbon::now() . '.xlsx');
        }
    }
}