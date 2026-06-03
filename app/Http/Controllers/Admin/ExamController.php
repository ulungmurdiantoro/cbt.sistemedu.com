<?php

namespace App\Http\Controllers\Admin;

use App\Models\Exam;
use App\Models\Essay;
use App\Models\Question;
use App\Models\Classroom;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Imports\QuestionsImport;
use App\Imports\EssaysImport;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;

class ExamController extends Controller
{
    public function index()
    {
        $exams = Exam::when(request()->q, function ($exams) {
            $exams->where('title', 'like', '%' . request()->q . '%');
        })->with('classroom', 'questions', 'essays')->latest()->paginate(10);

        $exams->appends(['q' => request()->q]);

        return inertia('Admin/Exams/Index', [
            'exams' => $exams,
        ]);
    }

    public function create()
    {
        return inertia('Admin/Exams/Create', [
            'classrooms' => Classroom::all(),
        ]);
    }

    public function store(Request $request)
    {
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

        Exam::create([
            'exams_code'        => 'exms-' . Str::ulid(),
            'title'             => $request->title,
            'type'              => $request->type,
            'classroom_id'      => $request->classroom_id,
            'duration'          => $request->duration,
            'description'       => $request->description,
            'random_question'   => $request->random_question,
            'random_answer'     => $request->random_answer,
            'show_answer'       => $request->show_answer,
        ]);

        return redirect()->route('admin.exams.index');
    }

    public function show($id)
    {
        $exam = Exam::with('classroom')->findOrFail($id);

        $exam->setRelation('questions', $exam->questions()->paginate(10));
        $exam->setRelation('essays', $exam->essays()->paginate(10));
        $exam->setRelation('migas_essays', $exam->migas_essays()->paginate(10));

        return inertia('Admin/Exams/Show', [
            'exam' => $exam,
        ]);
    }

    public function edit($id)
    {
        return inertia('Admin/Exams/Edit', [
            'exam'       => Exam::findOrFail($id),
            'classrooms' => Classroom::all(),
        ]);
    }

    public function update(Request $request, Exam $exam)
    {
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

        return redirect()->route('admin.exams.index');
    }

    public function destroy($id)
    {
        Exam::findOrFail($id)->delete();

        return redirect()->route('admin.exams.index');
    }

    public function createQuestion(Exam $exam)
    {
        return inertia('Admin/Questions/Create', [
            'exam' => $exam,
        ]);
    }

    public function storeQuestion(Request $request, Exam $exam)
    {
        $request->validate([
            'question'  => 'required',
            'option_1'  => 'required',
            'option_2'  => 'required',
            'option_3'  => 'required',
            'option_4'  => 'required',
            'option_5'  => 'required',
            'answer'    => 'required',
        ]);

        Question::create([
            'exam_id'   => $exam->id,
            'question'  => $request->question,
            'option_1'  => $request->option_1,
            'option_2'  => $request->option_2,
            'option_3'  => $request->option_3,
            'option_4'  => $request->option_4,
            'option_5'  => $request->option_5,
            'answer'    => $request->answer,
        ]);

        return redirect()->route('admin.exams.show', $exam->id);
    }

    public function editQuestion(Exam $exam, Question $question)
    {
        return inertia('Admin/Questions/Edit', [
            'exam'     => $exam,
            'question' => $question,
        ]);
    }

    public function updateQuestion(Request $request, Exam $exam, Question $question)
    {
        $request->validate([
            'question'  => 'required',
            'option_1'  => 'required',
            'option_2'  => 'required',
            'option_3'  => 'required',
            'option_4'  => 'required',
            'option_5'  => 'required',
            'answer'    => 'required',
        ]);

        $question->update([
            'question'  => $request->question,
            'option_1'  => $request->option_1,
            'option_2'  => $request->option_2,
            'option_3'  => $request->option_3,
            'option_4'  => $request->option_4,
            'option_5'  => $request->option_5,
            'answer'    => $request->answer,
        ]);

        return redirect()->route('admin.exams.show', $exam->id);
    }

    public function destroyQuestion(Exam $exam, Question $question)
    {
        $question->delete();

        return redirect()->route('admin.exams.show', $exam->id);
    }

    public function import(Exam $exam)
    {
        return inertia('Admin/Questions/Import', [
            'exam' => $exam,
        ]);
    }

    public function storeImport(Request $request, Exam $exam)
    {
        $request->validate([
            'file' => 'required|mimes:csv,xls,xlsx',
        ]);

        Excel::import(new QuestionsImport(), $request->file('file'));

        return redirect()->route('admin.exams.show', $exam->id);
    }

    public function createEssay(Exam $exam)
    {
        return inertia('Admin/Essays/Create', [
            'exam' => $exam,
        ]);
    }

    public function storeEssay(Request $request, Exam $exam)
    {
        $request->validate([
            'question' => 'required',
            'answer'   => 'nullable',
            'is_essay' => 'nullable|boolean',
        ]);

        Essay::create([
            'exam_id'     => $exam->id,
            'essays_code' => 'essy-' . Str::ulid(),
            'question'    => $request->question,
            'answer'      => $request->answer,
            'is_essay'    => $request->boolean('is_essay'),
        ]);

        return redirect()->route('admin.exams.show', $exam->id);
    }

    public function editEssay(Exam $exam, Essay $essays)
    {
        return inertia('Admin/Essays/Edit', [
            'exam'  => $exam,
            'essay' => $essays,
        ]);
    }

    public function updateEssay(Request $request, Exam $exam, Essay $essays)
    {
        $request->validate([
            'question' => 'required',
            'answer'   => 'nullable',
            'is_essay' => 'nullable|boolean',
        ]);

        $essays->update([
            'question' => $request->question,
            'answer'   => $request->answer,
            'is_essay' => $request->boolean('is_essay'),
        ]);

        return redirect()->route('admin.exams.show', $exam->id);
    }

    public function destroyEssay(Exam $exam, Essay $essays)
    {
        $essays->delete();

        return redirect()->route('admin.exams.show', $exam->id);
    }

    public function EssayImport(Exam $exam)
    {
        return inertia('Admin/Essays/Import', [
            'exam' => $exam,
        ]);
    }

    public function EssayStoreImport(Request $request, Exam $exam)
    {
        $request->validate([
            'file' => 'required|mimes:csv,xls,xlsx',
        ]);

        Excel::import(new EssaysImport(), $request->file('file'));

        return redirect()->route('admin.exams.show', $exam->id);
    }
}
