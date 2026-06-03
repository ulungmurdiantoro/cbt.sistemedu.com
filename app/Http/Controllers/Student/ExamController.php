<?php

namespace App\Http\Controllers\Student;

use Carbon\Carbon;
use App\Models\Grade;
use App\Models\Answer;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ExamController extends BaseExamController
{
    public function confirmation($id)
    {
        $exam_group = $this->examGroup((int) $id);

        $grade = $this->currentGrade($exam_group->exam->id, $exam_group->exam_session->id);

        return inertia('Student/Exams/Confirmation', [
            'exam_group' => $exam_group,
            'grade'      => $grade,
        ]);
    }

    public function startExam($id)
    {
        $exam_group = $this->examGroup((int) $id);

        $grade = $this->currentGrade($exam_group->exam->id, $exam_group->exam_session->id);
        $grade->start_time = Carbon::now();
        $grade->save();

        $questions = $exam_group->exam->random_question == 'Y'
            ? Question::where('exam_id', $exam_group->exam->id)->inRandomOrder()->get()
            : Question::where('exam_id', $exam_group->exam->id)->get();

        $question_order = 1;

        foreach ($questions as $question) {
            $options = [1, 2];
            if (!empty($question->option_3)) $options[] = 3;
            if (!empty($question->option_4)) $options[] = 4;
            if (!empty($question->option_5)) $options[] = 5;

            if ($exam_group->exam->random_answer == 'Y') {
                shuffle($options);
            }

            $answer = Answer::where('student_id', $this->studentId())
                ->where('exam_id', $exam_group->exam->id)
                ->where('exam_session_id', $exam_group->exam_session->id)
                ->where('question_id', $question->id)
                ->first();

            if ($answer) {
                $answer->question_order = $question_order;
                $answer->save();
            } else {
                Answer::create([
                    'answers_code'    => 'answ-' . Str::ulid(),
                    'exam_id'         => $exam_group->exam->id,
                    'exam_session_id' => $exam_group->exam_session->id,
                    'question_id'     => $question->id,
                    'student_id'      => $this->studentId(),
                    'question_order'  => $question_order,
                    'answer_order'    => implode(',', $options),
                    'answer'          => 0,
                    'is_correct'      => 'N',
                ]);
            }

            $question_order++;
        }

        return redirect()->route('student.exams.show', [
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

        $all_questions = Answer::with('question')
            ->where('student_id', $this->studentId())
            ->where('exam_id', $exam_group->exam->id)
            ->orderBy('question_order', 'ASC')
            ->get();

        $question_answered = Answer::where('student_id', $this->studentId())
            ->where('exam_id', $exam_group->exam->id)
            ->where('answer', '!=', 0)
            ->count();

        $question_active = Answer::with('question.exam')
            ->where('student_id', $this->studentId())
            ->where('exam_id', $exam_group->exam->id)
            ->where('question_order', $page)
            ->first();

        $answer_order = $question_active ? explode(',', $question_active->answer_order) : [];

        $duration = $this->currentGrade($exam_group->exam->id, $exam_group->exam_session->id);

        return inertia('Student/Exams/Show', [
            'id'                => (int) $id,
            'page'              => (int) $page,
            'exam_group'        => $exam_group,
            'all_questions'     => $all_questions,
            'question_answered' => $question_answered,
            'question_active'   => $question_active,
            'answer_order'      => $answer_order,
            'duration'          => $duration,
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

        $question = Question::find($request->question_id);
        $result   = ($question && $question->answer == $request->answer) ? 'Y' : 'N';

        $answer = Answer::where('exam_id', $request->exam_id)
            ->where('exam_session_id', $request->exam_session_id)
            ->where('student_id', $this->studentId())
            ->where('question_id', $request->question_id)
            ->first();

        if ($answer) {
            $answer->answer     = $request->answer;
            $answer->is_correct = $result;
            $answer->save();
        }

        return redirect()->back();
    }

    public function endExam(Request $request)
    {
        $count_answer   = Answer::where('exam_id', $request->exam_id)
            ->where('exam_session_id', $request->exam_session_id)
            ->where('student_id', $this->studentId())
            ->where('is_correct', 'Y')
            ->count();

        $count_question = Question::where('exam_id', $request->exam_id)->count();

        $grade = $this->currentGrade((int) $request->exam_id, (int) $request->exam_session_id);

        $grade->end_time      = Carbon::now();
        $grade->total_correct = $count_answer;
        $grade->grade         = $count_question > 0 ? round($count_answer / $count_question * 100, 2) : 0;
        $grade->save();

        return redirect()->route('student.exams.resultExam', $request->exam_group_id);
    }

    public function resultExam($exam_group_id)
    {
        $exam_group = $this->examGroup((int) $exam_group_id);

        $grade = $this->currentGrade($exam_group->exam->id, $exam_group->exam_session->id);

        return inertia('Student/Exams/Result', [
            'exam_group' => $exam_group,
            'grade'      => $grade,
        ]);
    }
}
