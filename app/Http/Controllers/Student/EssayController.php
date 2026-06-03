<?php

namespace App\Http\Controllers\Student;

use Carbon\Carbon;
use App\Models\AnswerEssay;
use App\Models\Essay;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EssayController extends BaseExamController
{
    public function confirmation($id)
    {
        $exam_group = $this->examGroup((int) $id);

        return inertia('Student/Essays/Confirmation', [
            'exam_group' => $exam_group,
            'grade'      => $this->currentGrade($exam_group->exam->id, $exam_group->exam_session->id),
        ]);
    }

    public function startEssay($id)
    {
        $exam_group = $this->examGroup((int) $id);

        $grade = $this->currentGrade($exam_group->exam->id, $exam_group->exam_session->id);
        $grade->start_time = Carbon::now();
        $grade->save();

        $essays = $exam_group->exam->random_essay == 'Y'
            ? Essay::where('exam_id', $exam_group->exam->id)->inRandomOrder()->get()
            : Essay::where('exam_id', $exam_group->exam->id)->get();

        $essay_order = 1;

        foreach ($essays as $essay) {
            $options = [1];

            if ($exam_group->exam->random_answer == 'Y') {
                shuffle($options);
            }

            $answer = AnswerEssay::where('student_id', $this->studentId())
                ->where('exam_id', $exam_group->exam->id)
                ->where('exam_session_id', $exam_group->exam_session->id)
                ->where('essay_id', $essay->id)
                ->first();

            if ($answer) {
                $answer->essay_order = $essay_order;
                $answer->save();
            } else {
                AnswerEssay::create([
                    'answeressays_code' => 'answess-' . Str::ulid(),
                    'exam_id'           => $exam_group->exam->id,
                    'exam_session_id'   => $exam_group->exam_session->id,
                    'essay_id'          => $essay->id,
                    'student_id'        => $this->studentId(),
                    'essay_order'       => $essay_order,
                    'answer_order'      => implode(',', $options),
                    'answer'            => null,
                    'is_correct'        => 'N',
                ]);
            }

            $essay_order++;
        }

        return redirect()->route('student.essays.show', [
            'id'   => $exam_group->id,
            'page' => 1,
        ]);
    }

    public function show($id, $page)
    {
        $exam_group = $this->examGroup((int) $id);

        if (!$exam_group) {
            return redirect()->route('student.dashboard');
        }

        $all_essays = AnswerEssay::with('essay')
            ->where('student_id', $this->studentId())
            ->where('exam_id', $exam_group->exam->id)
            ->orderBy('essay_order', 'ASC')
            ->get();

        $essay_answered = AnswerEssay::where('student_id', $this->studentId())
            ->where('exam_id', $exam_group->exam->id)
            ->whereNotNull('answer')
            ->count();

        $essay_active = AnswerEssay::with('essay.exam')
            ->where('student_id', $this->studentId())
            ->where('exam_id', $exam_group->exam->id)
            ->where('essay_order', $page)
            ->first();

        $answer_order = $essay_active ? explode(',', $essay_active->answer_order) : [];

        return inertia('Student/Essays/Show', [
            'id'             => (int) $id,
            'page'           => (int) $page,
            'exam_group'     => $exam_group,
            'all_essays'     => $all_essays,
            'essay_answered' => $essay_answered,
            'essay_active'   => $essay_active,
            'answer_order'   => $answer_order,
            'duration'       => $this->currentGrade($exam_group->exam->id, $exam_group->exam_session->id),
        ]);
    }

    public function updateDuration(Request $request, $grade_id)
    {
        $grade = $this->ownedGrade((int) $grade_id);
        $grade->duration = $request->duration;
        $grade->save();

        return response()->json(['success' => true]);
    }

    public function answerQuestion(Request $request)
    {
        $grade = $this->currentGrade((int) $request->exam_id, (int) $request->exam_session_id);

        if (!$grade) {
            return redirect()->back();
        }

        $grade->duration = $request->duration;
        $grade->save();

        $answer = AnswerEssay::where('exam_id', $request->exam_id)
            ->where('exam_session_id', $request->exam_session_id)
            ->where('student_id', $this->studentId())
            ->where('essay_id', $request->essay_id)
            ->first();

        if ($answer) {
            $answer->answer = $request->answer;
            $answer->save();
        }

        return redirect()->back();
    }

    public function endEssay(Request $request)
    {
        $grade = $this->currentGrade((int) $request->exam_id, (int) $request->exam_session_id);

        $grade->end_time = Carbon::now();
        $grade->save();

        return redirect()->route('student.essays.resultEssay', $request->exam_group_id);
    }

    public function resultEssay($exam_group_id)
    {
        $exam_group = $this->examGroup((int) $exam_group_id);

        return inertia('Student/Essays/Result', [
            'exam_group' => $exam_group,
            'grade'      => $this->currentGrade($exam_group->exam->id, $exam_group->exam_session->id),
        ]);
    }
}
