<?php

namespace App\Http\Controllers\Student;

use Carbon\Carbon;
use App\Models\Grade;
use App\Models\AnswerEssay;
use App\Models\Essay;
use App\Models\ExamGroup;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EssayController extends Controller
{
    public function confirmation($id)
    {
        $exam_group = ExamGroup::with('exam', 'exam_session', 'student.classroom')
            ->where('student_id', auth()->guard('student')->user()->id)
            ->where('id', $id)
            ->first();

        $grade = Grade::where('exam_id', $exam_group->exam->id)
            ->where('exam_session_id', $exam_group->exam_session->id)
            ->where('student_id', auth()->guard('student')->user()->id)
            ->first();
        return inertia('Student/Essays/Confirmation', [
            'exam_group' => $exam_group,
            'grade' => $grade,
        ]);
    }

    public function startEssay($id)
    {
        $exam_group = ExamGroup::with('exam', 'exam_session', 'student.classroom')
            ->where('student_id', auth()->guard('student')->user()->id)
            ->where('id', $id)
            ->first();

        $grade = Grade::where('exam_id', $exam_group->exam->id)
            ->where('exam_session_id', $exam_group->exam_session->id)
            ->where('student_id', auth()->guard('student')->user()->id)
            ->first();

        $grade->start_time = Carbon::now();
        $grade->update();

        if ($exam_group->exam->random_essay == 'Y') {

            $essays = Essay::where('exam_id', $exam_group->exam->id)->inRandomOrder()->get();
        } else {

            $essays = Essay::where('exam_id', $exam_group->exam->id)->get();
        }

        $essay_order = 1;

        foreach ($essays as $essay) {

            $options = [1];

            if ($exam_group->exam->random_answer == 'Y') {
                shuffle($options);
            }

            $answer = AnswerEssay::where('student_id', auth()->guard('student')->user()->id)
                ->where('exam_id', $exam_group->exam->id)
                ->where('exam_session_id', $exam_group->exam_session->id)
                ->where('essay_id', $essay->id)
                ->first();

            if ($answer) {

                $answer->essay_order = $essay_order;
                $answer->update();
            } else {

                AnswerEssay::create([
                    'answeressays_code' => 'answess-' . rand(11, 99) . uniqid(),
                    'exam_id'           => $exam_group->exam->id,
                    'exam_session_id'   => $exam_group->exam_session->id,
                    'essay_id'          => $essay->id,
                    'student_id'        => auth()->guard('student')->user()->id,
                    'essay_order'       => $essay_order,
                    'answer_order'      => implode(",", $options),
                    'answer'            => NULL,
                    'is_correct'        => 'N'
                ]);
            }
            $essay_order++;
        }

        return redirect()->route('student.essays.show', [
            'id'    => $exam_group->id,
            'page'  => 1
        ]);
    }

    public function show($id, $page)
    {
        $exam_group = ExamGroup::with('exam', 'exam_session', 'student.classroom')
            ->where('student_id', auth()->guard('student')->user()->id)
            ->where('id', $id)
            ->first();

        if (!$exam_group) {
            return redirect()->route('student.dashboard');
        }

        $all_essays = AnswerEssay::with('essay')
            ->where('student_id', auth()->guard('student')->user()->id)
            ->where('exam_id', $exam_group->exam->id)
            ->orderBy('essay_order', 'ASC')
            ->get();

        $essay_answered = AnswerEssay::with('essay')
            ->where('student_id', auth()->guard('student')->user()->id)
            ->where('exam_id', $exam_group->exam->id)
            ->where('answer', '!=', NULL)
            ->count();

        $essay_active = AnswerEssay::with('essay.exam')
            ->where('student_id', auth()->guard('student')->user()->id)
            ->where('exam_id', $exam_group->exam->id)
            ->where('essay_order', $page)
            ->first();

        if ($essay_active) {
            $answer_order = explode(",", $essay_active->answer_order);
        } else {
            $answer_order = [];
        }

        $duration = Grade::where('exam_id', $exam_group->exam->id)
            ->where('exam_session_id', $exam_group->exam_session->id)
            ->where('student_id', auth()->guard('student')->user()->id)
            ->first();

        return inertia('Student/Essays/Show', [
            'id'                => (int) $id,
            'page'              => (int) $page,
            'exam_group'        => $exam_group,
            'all_essays'        => $all_essays,
            'essay_answered'    => $essay_answered,
            'essay_active'      => $essay_active,
            'answer_order'      => $answer_order,
            'duration'          => $duration,
        ]);
    }

    public function updateDuration(Request $request, $grade_id)
    {
        $grade = Grade::find($grade_id);
        $grade->duration = $request->duration;
        $grade->update();

        return response()->json([
            'success'  => true,
            'message' => 'Duration updated successfully.'
        ]);
    }

    public function answerQuestion(Request $request)
    {
        Log::info('answerQuestion called', [
            'student_id' => auth()->guard('student')->user()->id,
            'exam_id' => $request->exam_id,
            'exam_session_id' => $request->exam_session_id,
            'essay_id' => $request->essay_id,
            'answer' => $request->answer,
            'duration' => $request->duration
        ]);

        $grade = Grade::where('exam_id', $request->exam_id)
            ->where('exam_session_id', $request->exam_session_id)
            ->where('student_id', auth()->guard('student')->user()->id)
            ->first();

        if (!$grade) {
            Log::warning('Grade not found for answerQuestion', [
                'exam_id' => $request->exam_id,
                'exam_session_id' => $request->exam_session_id,
            ]);
            return redirect()->back();
        }

        $grade->duration = $request->duration;
        $grade->update();
        Log::info('Grade duration updated', ['grade_id' => $grade->id]);

        $essay = Essay::find($request->essay_id);

        if (!$essay) {
            Log::error('Essay not found', ['essay_id' => $request->essay_id]);
            return redirect()->back();
        }

        // Essay checking (optional for essay grading, typically skipped)
        $result = $essay->answer == $request->answer ? 'Y' : 'N';
        Log::info('Answer checked', ['is_correct' => $result]);

        $answer = AnswerEssay::where('exam_id', $request->exam_id)
            ->where('exam_session_id', $request->exam_session_id)
            ->where('student_id', auth()->guard('student')->user()->id)
            ->where('essay_id', $request->essay_id)
            ->first();

        if ($answer) {
            $answer->answer = $request->answer;
            $answer->is_correct = $result;
            $answer->update();

            Log::info('Answer updated', ['answeressay_id' => $answer->id]);
        } else {
            Log::warning('AnswerEssay not found to update', [
                'essay_id' => $request->essay_id,
                'student_id' => auth()->guard('student')->user()->id,
            ]);
        }

        return redirect()->back();
    }

    public function endEssay(Request $request)
    {
        $count_answer = AnswerEssay::where('exam_id', $request->exam_id)
            ->where('exam_session_id', $request->exam_session_id)
            ->where('student_id', auth()->guard('student')->user()->id)
            ->where('is_correct', 'Y')
            ->count();

        $count_essay = Essay::where('exam_id', $request->exam_id)->count();

        $grade = Grade::where('exam_id', $request->exam_id)
            ->where('exam_session_id', $request->exam_session_id)
            ->where('student_id', auth()->guard('student')->user()->id)
            ->first();

        $grade->end_time        = Carbon::now();
        $grade->total_correct   = $count_answer;
        $grade->update();

        return redirect()->route('student.essays.resultEssay', $request->exam_group_id);
    }

    public function resultEssay($exam_group_id)
    {
        $exam_group = ExamGroup::with('exam', 'exam_session', 'student.classroom')
            ->where('student_id', auth()->guard('student')->user()->id)
            ->where('id', $exam_group_id)
            ->first();

        $grade = Grade::where('exam_id', $exam_group->exam->id)
            ->where('exam_session_id', $exam_group->exam_session->id)
            ->where('student_id', auth()->guard('student')->user()->id)
            ->first();

        return inertia('Student/Essays/Result', [
            'exam_group' => $exam_group,
            'grade'      => $grade,
        ]);
    }
}
