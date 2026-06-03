<?php

namespace App\Http\Controllers\Student;

use App\Models\AnswerEssay;
use App\Models\Essay;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EssayMigasController extends BaseExamController
{
    public function confirmation($id)
    {
        $exam_group = $this->examGroup((int) $id);

        if (!$exam_group) {
            return redirect()->route('student.dashboard');
        }

        return inertia('Student/EssaysMigas/Confirmation', [
            'exam_group' => $exam_group,
            'grade'      => $this->currentGrade($exam_group->exam->id, $exam_group->exam_session->id),
        ]);
    }

    public function startEssay($id)
    {
        $exam_group = $this->examGroup((int) $id);

        if (!$exam_group) {
            return redirect()->route('student.dashboard');
        }

        $examId    = (int) $exam_group->exam->id;
        $sessionId = (int) $exam_group->exam_session->id;

        $grade = $this->currentGrade($examId, $sessionId);
        if ($grade) {
            $grade->start_time = Carbon::now();
            $grade->save();
        }

        $essays = $exam_group->exam->random_essay === 'Y'
            ? Essay::where('exam_id', $examId)->inRandomOrder()->get()
            : Essay::where('exam_id', $examId)->get();

        $essayOrder = 1;

        foreach ($essays as $essay) {
            $options = [1];
            if ($exam_group->exam->random_answer === 'Y') {
                shuffle($options);
            }

            $answer = AnswerEssay::where('student_id', $this->studentId())
                ->where('exam_id', $examId)
                ->where('exam_session_id', $sessionId)
                ->where('essay_id', $essay->id)
                ->first();

            if ($answer) {
                $answer->essay_order = $essayOrder;
                $answer->save();
            } else {
                AnswerEssay::create([
                    'answeressays_code' => 'answess-' . Str::ulid(),
                    'exam_id'           => $examId,
                    'exam_session_id'   => $sessionId,
                    'essay_id'          => $essay->id,
                    'student_id'        => $this->studentId(),
                    'essay_order'       => $essayOrder,
                    'answer_order'      => implode(',', $options),
                    'answer'            => null,
                    'is_correct'        => 'N',
                ]);
            }

            $essayOrder++;
        }

        return redirect()->route('student.essaysmigas.show', [
            'id'   => (int) $exam_group->id,
            'page' => 1,
        ]);
    }

    public function show($id, $page)
    {
        $exam_group = $this->examGroup((int) $id);

        if (!$exam_group) {
            return redirect()->route('student.dashboard');
        }

        $examId    = (int) $exam_group->exam->id;
        $sessionId = (int) $exam_group->exam_session->id;

        $all_essays = AnswerEssay::with('essay')
            ->where('student_id', $this->studentId())
            ->where('exam_id', $examId)
            ->where('exam_session_id', $sessionId)
            ->orderBy('essay_order', 'ASC')
            ->get();

        $essay_answered = AnswerEssay::where('student_id', $this->studentId())
            ->where('exam_id', $examId)
            ->where('exam_session_id', $sessionId)
            ->whereNotNull('answer')
            ->count();

        $essay_active = AnswerEssay::with('essay.exam')
            ->where('student_id', $this->studentId())
            ->where('exam_id', $examId)
            ->where('exam_session_id', $sessionId)
            ->where('essay_order', (int) $page)
            ->first();

        $answer_order = ($essay_active && $essay_active->answer_order)
            ? explode(',', $essay_active->answer_order)
            : [];

        $existingFile = null;
        $existing = AnswerEssay::where('exam_id', $examId)
            ->where('exam_session_id', $sessionId)
            ->where('student_id', $this->studentId())
            ->whereNotNull('answer')
            ->first();

        if ($existing?->answer) {
            $path         = $existing->answer;
            $existingFile = [
                'path' => $path,
                'url'  => asset('storage/' . $path),
                'name' => basename($path),
                'size' => Storage::disk('public')->exists($path) ? Storage::disk('public')->size($path) : null,
            ];
        }

        return inertia('Student/EssaysMigas/Show', [
            'id'              => (int) $id,
            'page'            => (int) $page,
            'exam_id'         => $examId,
            'exam_session_id' => $sessionId,
            'exam_group'      => $exam_group,
            'all_essays'      => $all_essays,
            'essay_answered'  => $essay_answered,
            'essay_active'    => $essay_active,
            'answer_order'    => $answer_order,
            'duration'        => $this->currentGrade($examId, $sessionId),
            'existing_file'   => $existingFile,
        ]);
    }

    public function updateDuration(Request $request, $grade_id)
    {
        $grade = $this->ownedGrade((int) $grade_id);
        $grade->duration = $request->duration;
        $grade->save();

        return response()->json(['success' => true]);
    }

    public function showUploadPage($exam_id, $exam_session_id)
    {
        $examId    = (int) $exam_id;
        $sessionId = (int) $exam_session_id;

        $grade = $this->currentGrade($examId, $sessionId);
        if (!$grade) {
            abort(404, 'Grade tidak ditemukan');
        }

        $existing = AnswerEssay::where('exam_id', $examId)
            ->where('exam_session_id', $sessionId)
            ->where('student_id', $this->studentId())
            ->whereNotNull('answer')
            ->first();

        $existingFile = null;
        if ($existing?->answer) {
            $path         = $existing->answer;
            $existingFile = [
                'path' => $path,
                'url'  => asset('storage/' . $path),
                'name' => basename($path),
                'size' => Storage::disk('public')->exists($path) ? Storage::disk('public')->size($path) : null,
            ];
        }

        return inertia('Student/EssayMigasUpload', [
            'exam_id'         => $examId,
            'exam_session_id' => $sessionId,
            'duration'        => ['duration' => (int) ($grade->duration ?? 0), 'id' => (int) $grade->id],
            'all_essays'      => Essay::where('exam_id', $examId)->get(),
            'existing_file'   => $existingFile,
        ]);
    }

    public function answerQuestion(Request $request)
    {
        $request->validate([
            'exam_id'         => ['required', 'integer'],
            'exam_session_id' => ['required', 'integer'],
            'duration'        => ['nullable'],
            'file'            => ['required', 'file', 'max:20480'],
        ]);

        $examId    = (int) $request->exam_id;
        $sessionId = (int) $request->exam_session_id;

        $grade = $this->currentGrade($examId, $sessionId);
        if (!$grade) {
            return response()->json(['success' => false, 'message' => 'Grade tidak ditemukan.'], 404);
        }

        if ($request->filled('duration')) {
            $grade->duration = $request->duration;
            $grade->save();
        }

        $file         = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $extension    = $file->getClientOriginalExtension();
        $safeName     = Str::slug(pathinfo($originalName, PATHINFO_FILENAME));
        $filename     = $safeName . '-' . now()->format('YmdHis') . '-' . Str::random(6) . ($extension ? '.' . $extension : '');
        $directory    = "essay_migas_answers/{$examId}/{$sessionId}/{$this->studentId()}";

        $old = AnswerEssay::where('exam_id', $examId)
            ->where('exam_session_id', $sessionId)
            ->where('student_id', $this->studentId())
            ->whereNotNull('answer')
            ->value('answer');

        if ($old && Storage::disk('public')->exists($old)) {
            Storage::disk('public')->delete($old);
        }

        $storedPath = $file->storeAs($directory, $filename, 'public');
        $fileUrl    = asset('storage/' . $storedPath);

        $essayIds = Essay::where('exam_id', $examId)->pluck('id');
        foreach ($essayIds as $essayId) {
            AnswerEssay::updateOrCreate(
                ['exam_id' => $examId, 'exam_session_id' => $sessionId, 'student_id' => $this->studentId(), 'essay_id' => $essayId],
                ['answer' => $storedPath, 'is_correct' => 'N']
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'File jawaban berhasil diupload.',
            'file'    => ['name' => $originalName, 'path' => $storedPath, 'url' => $fileUrl, 'size' => $file->getSize()],
        ]);
    }

    public function download($exam_id, $exam_session_id)
    {
        $path = AnswerEssay::where('exam_id', (int) $exam_id)
            ->where('exam_session_id', (int) $exam_session_id)
            ->where('student_id', $this->studentId())
            ->whereNotNull('answer')
            ->value('answer');

        if (!$path || !Storage::disk('public')->exists($path)) {
            abort(404, 'File tidak ditemukan');
        }

        return Storage::disk('public')->download($path, basename($path));
    }

    public function endEssay(Request $request)
    {
        $request->validate([
            'exam_id'         => ['required', 'integer'],
            'exam_session_id' => ['required', 'integer'],
            'exam_group_id'   => ['required', 'integer'],
        ]);

        $examId    = (int) $request->exam_id;
        $sessionId = (int) $request->exam_session_id;

        $grade = $this->currentGrade($examId, $sessionId);
        if ($grade) {
            $grade->end_time      = Carbon::now();
            $grade->total_correct = AnswerEssay::where('exam_id', $examId)
                ->where('exam_session_id', $sessionId)
                ->where('student_id', $this->studentId())
                ->where('is_correct', 'Y')
                ->count();
            $grade->save();
        }

        return redirect()->route('student.essaysmigas.resultEssay', [
            'essay_group_id' => (int) $request->exam_group_id,
        ]);
    }

    public function resultEssay($exam_group_id)
    {
        $exam_group = $this->examGroup((int) $exam_group_id);

        if (!$exam_group) {
            return redirect()->route('student.dashboard');
        }

        return inertia('Student/EssaysMigas/Result', [
            'exam_group' => $exam_group,
            'grade'      => $this->currentGrade($exam_group->exam->id, $exam_group->exam_session->id),
        ]);
    }

    public function storeTextAnswer(Request $request)
    {
        $request->validate([
            'exam_id'         => 'required|integer',
            'exam_session_id' => 'required|integer',
            'exam_group_id'   => 'required|integer',
            'essay_id'        => 'required|integer',
            'answer'          => 'nullable|string',
            'duration'        => 'nullable',
        ]);

        AnswerEssay::updateOrCreate(
            [
                'student_id'      => $this->studentId(),
                'exam_id'         => $request->exam_id,
                'exam_session_id' => $request->exam_session_id,
                'essay_id'        => $request->essay_id,
            ],
            [
                'exam_group_id' => $request->exam_group_id,
                'answer'        => $request->answer,
                'duration'      => $request->duration,
            ]
        );

        return response()->json(['success' => true, 'message' => 'Jawaban berhasil disimpan.']);
    }
}
