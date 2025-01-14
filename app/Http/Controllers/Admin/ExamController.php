<?php

namespace App\Http\Controllers\Admin;

use App\Models\Exam;
use App\Models\Essay;
use App\Models\Question;
use App\Models\Classroom;
use Illuminate\Http\Request;
use App\Imports\QuestionsImport;
use App\Imports\EssaysImport;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;

class ExamController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //get exams
        $exams = Exam::when(request()->q, function($exams) {
            $exams = $exams->where('title', 'like', '%'. request()->q . '%');
        })->with('classroom', 'questions', 'essays')->latest()->paginate(10);

        //append query string to pagination links
        $exams->appends(['q' => request()->q]);

        //render with inertia
        return inertia('Admin/Exams/Index', [
            'exams' => $exams,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //get lessons
        // $lessons = Lesson::all();

        //get classrooms
        $classrooms = Classroom::all();
        
        //render with inertia
        return inertia('Admin/Exams/Create', [
            // 'lessons' => $lessons,
            'classrooms' => $classrooms,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //validate request
        $request->validate([
            'title'             => 'required',
            'type'              => 'required',
            'classroom_id'      => 'required|integer',
            'duration'          => 'required|integer',
            'description'       => 'required',
            'random_question'   => 'required',
            'random_answer'     => 'required',
            'show_answer'       => 'required',
        ]);

        //create exam
        Exam::create([
            'exams_code'        => 'exms-' . rand(11, 99) . uniqid(),
            'title'             => $request->title,
            'type'              => $request->type,
            'classroom_id'      => $request->classroom_id,
            'duration'          => $request->duration,
            'description'       => $request->description,
            'random_question'   => $request->random_question,
            'random_answer'     => $request->random_answer,
            'show_answer'       => $request->show_answer,
        ]);

        //redirect
        return redirect()->route('admin.exams.index');

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        
        //get exam
        $exam = Exam::with('classroom')->findOrFail($id);
        // $exam = Exam::where('exams_code', $exams_code)->with('classroom')->firstOrFail();
        
        //get relation questions with pagination
        $exam->setRelation('questions', $exam->questions()->paginate(10));
        $exam->setRelation('essays', $exam->essays()->paginate(10));
        
        // $question = Exam::withCount('questions')->get();
        // $essay = Exam::withCount('essays')->get();
        // dd($exam);
        //render with inertia
        return inertia('Admin/Exams/Show', [
            'exam' => $exam,
            // 'question' => $question,
            // 'essay' => $essay,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //get exam
        $exam = Exam::findOrFail($id);

        //get lessons
        // $lessons = Lesson::all();

        //get classrooms
        $classrooms = Classroom::all();

        //render with inertia
        return inertia('Admin/Exams/Edit', [
            'exam' => $exam,
            // 'lessons' => $lessons,
            'classrooms' => $classrooms,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Exam $exam)
    {
        //validate request
        $request->validate([
            'title'             => 'required',
            'type'              => 'required',
            'classroom_id'      => 'required|integer',
            'duration'          => 'required|integer',
            'description'       => 'required',
            'random_question'   => 'required',
            'random_answer'     => 'required',
            'show_answer'       => 'required',
        ]);

        //update exam
        $exam->update([
            'title'             => $request->title,
            'type'              => $request->type,
            'classroom_id'      => $request->classroom_id,
            'duration'          => $request->duration,
            'description'       => $request->description,
            'random_question'   => $request->random_question,
            'random_answer'     => $request->random_answer,
            'show_answer'       => $request->show_answer,
        ]);

        //redirect
        return redirect()->route('admin.exams.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //get exam
        $exam = Exam::findOrFail($id);

        //delete exam
        $exam->delete();

        //redirect
        return redirect()->route('admin.exams.index');
    }

    /**
     * createQuestion
     *
     * @param  mixed $exam
     * @return void
     */
    public function createQuestion(Exam $exam)
    {        
        //render with inertia
        // dd($exam);
        return inertia('Admin/Questions/Create', [
            'exam' => $exam,
        ]);
        
    }
    
    /**
     * storeQuestion
     *
     * @param  mixed $request
     * @param  mixed $exam
     * @return void
     */
    public function storeQuestion(Request $request, Exam $exam)
    {
        //validate request
        $request->validate([
            'question'          => 'required',
            'option_1'          => 'required',
            'option_2'          => 'required',
            'option_3'          => 'required',
            'option_4'          => 'required',
            'option_5'          => 'required',
            'answer'            => 'required',
        ]);
        
        //create question
        Question::create([
            'exam_id'           => $exam->id,
            // 'questions_code'    => 'qstn-' . rand(11, 99) . uniqid(),
            'question'          => $request->question,
            'option_1'          => $request->option_1,
            'option_2'          => $request->option_2,
            'option_3'          => $request->option_3,
            'option_4'          => $request->option_4,
            'option_5'          => $request->option_5,
            'answer'            => $request->answer,
        ]);
        
        //redirect
        return redirect()->route('admin.exams.show', $exam->id);
    }

    /**
     * editQuestion
     *
     * @param  mixed $exam
     * @param  mixed $question
     * @return void
     */
    public function editQuestion(Exam $exam, Question $question)
    {
        //render with inertia
        // dd($question);
        return inertia('Admin/Questions/Edit', [
            'exam' => $exam,
            'question' => $question,
        ]);
    }

    /**
     * updateQuestion
     *
     * @param  mixed $request
     * @param  mixed $exam
     * @param  mixed $question
     * @return void
     */
    public function updateQuestion(Request $request, Exam $exam, Question $question)
    {
        //validate request
        $request->validate([
            'question'          => 'required',
            'option_1'          => 'required',
            'option_2'          => 'required',
            'option_3'          => 'required',
            'option_4'          => 'required',
            'option_5'          => 'required',
            'answer'            => 'required',
        ]);
        
        //update question
        $question->update([
            'question'          => $request->question,
            'option_1'          => $request->option_1,
            'option_2'          => $request->option_2,
            'option_3'          => $request->option_3,
            'option_4'          => $request->option_4,
            'option_5'          => $request->option_5,
            'answer'            => $request->answer,
        ]);
        
        //redirect
        return redirect()->route('admin.exams.show', $exam->id);
    }

    /**
     * destroyQuestion
     *
     * @param  mixed $exam
     * @param  mixed $question
     * @return void
     */
    public function destroyQuestion(Exam $exam, Question $question)
    {
        //delete question
        $question->delete();
        
        //redirect
        return redirect()->route('admin.exams.show', $exam->id);
    }

    /**
     * import
     *
     * @return void
     */
    public function import(Exam $exam)
    {
        // dd($exam);
        return inertia('Admin/Questions/Import', [
            'exam' => $exam
        ]);
    }
    
    /**
     * storeImport
     *
     * @param  mixed $request
     * @return void
     */
    public function storeImport(Request $request, Exam $exam)
    {
        $request->validate([
            'file' => 'required|mimes:csv,xls,xlsx'
        ]);

        // import data
        Excel::import(new QuestionsImport(), $request->file('file'));

        //redirect
        return redirect()->route('admin.exams.show', $exam->id);
    }

    /**
     * createEssay
     *
     * @param  mixed $exam
     * @return void
     */
    public function createEssay(Exam $exam)
    {        
        //render with inertia
        // dd($exam);
        return inertia('Admin/Essays/Create', [
            'exam' => $exam,
        ]);
        
    }
    
    /**
     * storeEssay
     *
     * @param  mixed $request
     * @param  mixed $exam
     * @return void
     */
    public function storeEssay(Request $request, Exam $exam)
    {
        //validate request
        $request->validate([
            'question'             => 'required',
            'answer'               => 'required',
        ]);
        
        //create essay
        Essay::create([
            'exam_id'              => $exam->id,
            'essays_code'          => 'essy-' . rand(11, 99) . uniqid(),
            'question'             => $request->question,
            'answer'               => $request->answer,
        ]);
        
        //redirect
        return redirect()->route('admin.exams.show', $exam->id);
    }

    /**
     * editEssay
     *
     * @param  mixed $exam
     * @param  mixed $essay
     * @return void
     */
    public function editEssay(Exam $exam, Essay $essays)
    {
        //render with inertia
        // dd($essay);
        return inertia('Admin/Essays/Edit', [
            'exam' => $exam,
            'essay' => $essays,
        ]);
    }

    /**
     * updateEssay
     *
     * @param  mixed $request
     * @param  mixed $exam
     * @param  mixed $essay
     * @return void
     */
    public function updateEssay(Request $request, Exam $exam, Essay $essays)
    {
        //validate request
        $request->validate([
            'question'          => 'required',
            'answer'            => 'required',
        ]);
        
        //update essay
        $essays->update([
            'question'          => $request->question,
            'answer'            => $request->answer,
        ]);
        
        //redirect
        return redirect()->route('admin.exams.show', $exam->id);
    }

    /**
     * destroyEssay
     *
     * @param  mixed $exam
     * @param  mixed $essay
     * @return void
     */
    public function destroyEssay(Exam $exam, Essay $essays)
    {
        //delete essay
        $essays->delete();
        
        //redirect
        return redirect()->route('admin.exams.show', $exam->id);
    }

    /**
     * import
     *
     * @return void
     */
    public function EssayImport(Exam $exam)
    {
        dd($exam);
        return inertia('Admin/Essays/Import', [
            'exam' => $exam
        ]);
    }
    
    /**
     * storeImport
     *
     * @param  mixed $request
     * @return void
     */
    public function EssayStoreImport(Request $request, Exam $exam)
    {
        
        $request->validate([
            'file' => 'required|mimes:csv,xls,xlsx'
        ]);

        // import data
        Excel::import(new EssaysImport(), $request->file('file'));

        //redirect
        return redirect()->route('admin.exams.show', $exam->id);
    }
}