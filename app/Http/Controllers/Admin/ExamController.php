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
use App\Support\AnswerFile;
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
            'file_accept' => AnswerFile::accept(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate(array_merge([
            'title'             => 'required',
            'type'              => 'required',
            'classroom_id'      => 'required|integer',
            'duration'          => 'required|integer',
            'description'       => 'required',
            'random_question'   => 'required',
            'random_answer'     => 'required',
            'show_answer'       => 'required',
        ], $this->praktikRules($request)), $this->praktikMessages());

        $exam = Exam::create([
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

        $this->savePraktik($request, $exam);

        return redirect()->route('admin.exams.index');
    }

    /**
     * Ujian Praktik/Keterampilan: panduan (teks biasa) + template wajib; durasi boleh 0 = tanpa batas waktu.
     * $exam diisi saat update — template lama tetap dipakai bila tidak diganti.
     */
    private function praktikRules(Request $request, ?Exam $exam = null): array
    {
        if ($request->input('type') !== Exam::TYPE_PRAKTIK) {
            return [];
        }

        $hasTemplate = $exam?->praktik_template_path !== null;

        return [
            'duration'        => 'required|integer|min:0|max:1440',
            'praktik_panduan' => 'nullable|string|max:20000',
            'template'        => $hasTemplate
                ? array_merge(['nullable'], array_slice(AnswerFile::rules(), 1))
                : AnswerFile::rules(),
        ];
    }

    private function praktikMessages(): array
    {
        return array_merge(AnswerFile::messages('template'), [
            'template.required' => 'Template wajib diunggah untuk Ujian Praktik/Keterampilan.',
        ]);
    }

    private function savePraktik(Request $request, Exam $exam): void
    {
        if (! $exam->isPraktik()) {
            return;
        }

        $exam->praktik_panduan = $request->input('praktik_panduan');

        if ($file = $request->file('template')) {
            $oldPath = $exam->praktik_template_path;

            $exam->praktik_template_path = AnswerFile::store($file, "praktik_templates/{$exam->id}");
            $exam->praktik_template_name = $file->getClientOriginalName();
            $exam->save();

            // Hapus file lama setelah yang baru tersimpan, supaya upload gagal tidak menghilangkan template.
            AnswerFile::delete($oldPath);

            return;
        }

        $exam->save();
    }

    /** Unduh template Ujian Praktik/Keterampilan (pratinjau admin). */
    public function praktikTemplate(Exam $exam)
    {
        abort_unless($exam->isPraktik() && $exam->praktik_template_path, 404, 'Template belum diunggah.');

        return AnswerFile::download($exam->praktik_template_path, $exam->praktik_template_name);
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
        $exam = Exam::findOrFail($id);

        return inertia('Admin/Exams/Edit', [
            // Nama template ikut ditampilkan; path tetap tersembunyi ($hidden)
            'exam'       => $exam->toArray() + ['has_template' => $exam->praktik_template_path !== null],
            'file_accept' => AnswerFile::accept(),
            'classrooms' => Classroom::all(),
        ]);
    }

    public function update(Request $request, Exam $exam)
    {
        $request->validate(array_merge([
            'title'             => 'required',
            'type'              => 'required',
            'classroom_id'      => 'required|integer',
            'duration'          => 'required|integer',
            'description'       => 'required',
            'random_question'   => 'required',
            'random_answer'     => 'required',
            'show_answer'       => 'required',
        ], $this->praktikRules($request, $exam)), $this->praktikMessages());

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

        $this->savePraktik($request, $exam);

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
