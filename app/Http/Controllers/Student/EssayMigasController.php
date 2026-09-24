<?php

namespace App\Http\Controllers\Student;

use App\Models\AnswerEssay;
use App\Models\Essay;
use App\Support\AnswerFile;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EssayMigasController extends BaseExamController
{
    public function confirmation($id)
    {
        $exam_group = $this->examGroup((int) $id);

        if (!$exam_group) {
            return redirect()->route('student.dashboard');
        }

        if ($redirect = $this->tugasRedirectIfRequired($exam_group)) {
            return $redirect;
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

        if ($redirect = $this->tugasRedirectIfRequired($exam_group)) {
            return $redirect;
        }

        $examId    = (int) $exam_group->exam->id;
        $sessionId = (int) $exam_group->exam_session->id;

        $grade = $this->currentGrade($examId, $sessionId);

        if (!$grade || $grade->end_time) {
            return redirect()->route('student.dashboard');
        }

        // Sudah dimulai: lanjutkan tanpa mereset waktu atau mengacak ulang soal.
        if ($grade->start_time) {
            return redirect()->route('student.essaysmigas.show', ['id' => (int) $exam_group->id, 'page' => 1]);
        }

        $grade->start_time = Carbon::now();
        $grade->save();

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

        $grade = $this->currentGrade($examId, $sessionId);

        if (!$grade || $grade->end_time) {
            return redirect()->route('student.essaysmigas.resultEssay', ['essay_group_id' => (int) $exam_group->id]);
        }

        $all_essays = AnswerEssay::with(['essay' => $this->essayForStudent()])
            ->where('student_id', $this->studentId())
            ->where('exam_id', $examId)
            ->where('exam_session_id', $sessionId)
            ->orderBy('essay_order', 'ASC')
            ->get()
            ->each->makeHidden(AnswerEssay::ASSESSMENT_COLUMNS);

        $essay_answered = AnswerEssay::where('student_id', $this->studentId())
            ->where('exam_id', $examId)
            ->where('exam_session_id', $sessionId)
            ->whereNotNull('answer')
            ->count();

        $essay_active = AnswerEssay::with(['essay' => $this->essayForStudent(), 'essay.exam'])
            ->where('student_id', $this->studentId())
            ->where('exam_id', $examId)
            ->where('exam_session_id', $sessionId)
            ->where('essay_order', (int) $page)
            ->first()
            ?->makeHidden(AnswerEssay::ASSESSMENT_COLUMNS);

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
            $existingFile = $this->fileInfo($existing->answer, $examId, $sessionId);
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
            'duration'        => $this->gradeForTimer($grade),
            'existing_file'   => $existingFile,
            'file_accept'     => AnswerFile::accept(),
        ]);
    }

    public function updateDuration(Request $request, $grade_id)
    {
        $grade = $this->ownedGrade((int) $grade_id);

        if ($grade->end_time === null) {
            $this->syncDuration($grade, $request->duration);
        }

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
            $existingFile = $this->fileInfo($existing->answer, $examId, $sessionId);
        }

        return inertia('Student/EssayMigasUpload', [
            'exam_id'         => $examId,
            'exam_session_id' => $sessionId,
            'duration'        => ['duration' => $this->remainingMs($grade), 'id' => (int) $grade->id],
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
            'file'            => AnswerFile::rules(),
        ], AnswerFile::messages());

        $examId    = (int) $request->exam_id;
        $sessionId = (int) $request->exam_session_id;

        $grade = $this->currentGrade($examId, $sessionId);
        if (!$grade) {
            return response()->json(['success' => false, 'message' => 'Grade tidak ditemukan.'], 404);
        }

        if (!$this->acceptsAnswers($grade)) {
            return response()->json(['success' => false, 'message' => 'Waktu ujian sudah habis atau ujian telah diakhiri.'], 422);
        }

        $this->syncDuration($grade, $request->duration);

        $file = $request->file('file');

        $old = AnswerEssay::where('exam_id', $examId)
            ->where('exam_session_id', $sessionId)
            ->where('student_id', $this->studentId())
            ->whereNotNull('answer')
            ->value('answer');

        AnswerFile::delete($old);

        $storedPath = AnswerFile::store($file, "essay_migas_answers/{$examId}/{$sessionId}/{$this->studentId()}");

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
            'file'    => [
                'name' => $file->getClientOriginalName(),
                'path' => $storedPath,
                'url'  => route('student.essaysmigas.download', [$examId, $sessionId]),
                'size' => $file->getSize(),
            ],
        ]);
    }

    public function download($exam_id, $exam_session_id)
    {
        $path = AnswerEssay::where('exam_id', (int) $exam_id)
            ->where('exam_session_id', (int) $exam_session_id)
            ->where('student_id', $this->studentId())
            ->whereNotNull('answer')
            ->value('answer');

        return AnswerFile::download($path);
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
        if ($grade && $grade->end_time === null) {
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

        $grade = $this->currentGrade((int) $request->exam_id, (int) $request->exam_session_id);

        if (!$this->acceptsAnswers($grade)) {
            return response()->json(['success' => false, 'message' => 'Waktu ujian sudah habis atau ujian telah diakhiri.'], 422);
        }

        $this->syncDuration($grade, $request->duration);

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

    private function fileInfo(string $path, int $examId, int $sessionId): array
    {
        return [
            'path' => $path,
            'url'  => route('student.essaysmigas.download', [$examId, $sessionId]),
            'name' => basename($path),
            'size' => AnswerFile::size($path),
        ];
    }
}
