<?php

namespace App\Http\Controllers\Student;

use Carbon\Carbon;
use App\Models\Grade;
use App\Models\AnswerEssay;
use App\Models\Essay;
use App\Models\ExamGroup;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EssayController extends Controller
{
    /**
     * confirmation
     *
     * @param  mixed $id
     * @return void
     */
    public function confirmation($id)
    {
        //get exam group
        $exam_group = ExamGroup::with('exam', 'exam_session', 'student.classroom')
                    ->where('student_id', auth()->guard('student')->user()->id)
                    ->where('id', $id)
                    ->first();

        //get grade / nilai
        $grade = Grade::where('exam_id', $exam_group->exam->id)
                    ->where('exam_session_id', $exam_group->exam_session->id)
                    ->where('student_id', auth()->guard('student')->user()->id)
                    ->first();
        // dd($exam_group->exam);
        //return with inertia
        return inertia('Student/Essays/Confirmation', [
            'exam_group' => $exam_group,
            'grade' => $grade,
        ]);
    }

    /**
     * startExam
     *
     * @param  mixed $id
     * @return void
     */
    public function startEssay($id)
    {
        //get exam group
        $exam_group = ExamGroup::with('exam', 'exam_session', 'student.classroom')
                    ->where('student_id', auth()->guard('student')->user()->id)
                    ->where('id', $id)
                    ->first();

        //get grade / nilai
        $grade = Grade::where('exam_id', $exam_group->exam->id)
                    ->where('exam_session_id', $exam_group->exam_session->id)
                    ->where('student_id', auth()->guard('student')->user()->id)
                    ->first();

        //update start time di table grades
        $grade->start_time = Carbon::now();
        $grade->update();

        //cek apakah essays / soal ujian di random
        if($exam_group->exam->random_essay == 'Y') {

            //get essays / soal ujian
            $essays = Essay::where('exam_id', $exam_group->exam->id)->inRandomOrder()->get();

        } else {

            //get essays / soal ujian
            $essays = Essay::where('exam_id', $exam_group->exam->id)->get();

        }

        //define pilihan jawaban default
        $essay_order = 1;

        foreach ($essays as $essay) {

            //buat array jawaban / answer
            $options = [1];
            // if(!empty($essay->option_3)) $options[] = 3;
            // if(!empty($essay->option_4)) $options[] = 4;
            // if(!empty($essay->option_5)) $options[] = 5;

            //acak jawaban / answer
            if($exam_group->exam->random_answer == 'Y') {
                shuffle($options);
            }

            //cek apakah sudah ada data jawaban
            $answer = AnswerEssay::where('student_id', auth()->guard('student')->user()->id)
                    ->where('exam_id', $exam_group->exam->id)
                    ->where('exam_session_id', $exam_group->exam_session->id)
                    ->where('essay_id', $essay->id)
                    ->first();

            //jika sudah ada jawaban / answer
            if($answer) {

                //update urutan essay / soal
                $answer->essay_order = $essay_order;
                $answer->update();

            } else {

                //buat jawaban default baru
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

        //redirect ke ujian halaman 1
        return redirect()->route('student.essays.show', [
            'id'    => $exam_group->id, 
            'page'  => 1
        ]);   
    }
    
    /**
     * show
     *
     * @param  mixed $id
     * @param  mixed $page
     * @return void
     */
    public function show($id, $page)
    {
        //get exam group
        $exam_group = ExamGroup::with('exam', 'exam_session', 'student.classroom')
                    ->where('student_id', auth()->guard('student')->user()->id)
                    ->where('id', $id)
                    ->first();

        if(!$exam_group) {
            return redirect()->route('student.dashboard');
        }

        //get all essays
        $all_essays = AnswerEssay::with('essay')
                        ->where('student_id', auth()->guard('student')->user()->id)
                        ->where('exam_id', $exam_group->exam->id)
                        ->orderBy('essay_order', 'ASC')
                        ->get();

        //count all essay answered
        $essay_answered = AnswerEssay::with('essay')
                        ->where('student_id', auth()->guard('student')->user()->id)
                        ->where('exam_id', $exam_group->exam->id)
                        ->where('answer', '!=', NULL)
                        ->count();


        //get essay active
        $essay_active = AnswerEssay::with('essay.exam')
                        ->where('student_id', auth()->guard('student')->user()->id)
                        ->where('exam_id', $exam_group->exam->id)
                        ->where('essay_order', $page)
                        ->first();
        
        //explode atau pecah jawaban
        if ($essay_active) {
            $answer_order = explode(",", $essay_active->answer_order);
        } else  {
            $answer_order = [];
        }

        //get duration
        $duration = Grade::where('exam_id', $exam_group->exam->id)
                    ->where('exam_session_id', $exam_group->exam_session->id)
                    ->where('student_id', auth()->guard('student')->user()->id)
                    ->first();

        //return with inertia
        // dd($essay_active);
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

    /**
     * updateDuration
     *
     * @param  mixed $request
     * @param  mixed $grade_id
     * @return void
     */
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

    /**
     * answerEssay
     *
     * @param  mixed $request
     * @return void
     */
    public function answerEssay(Request $request)
    {
        //update duration
        $grade = Grade::where('exam_id', $request->exam_id)
                ->where('exam_session_id', $request->exam_session_id)
                ->where('student_id', auth()->guard('student')->user()->id)
                ->first();

        $grade->duration = $request->duration;
        $grade->update();

        //get essay
        $essay = Essay::find($request->essay_id);
        
        //cek apakah jawaban sudah benar
        if($essay->answer == $request->answer) {

            //jawaban benar
            $result = 'Y';
        } else {

            //jawaban salah
            $result = 'N';
        }

        //get answer
        $answer   = AnswerEssay::where('exam_id', $request->exam_id)
                    ->where('exam_session_id', $request->exam_session_id)
                    ->where('student_id', auth()->guard('student')->user()->id)
                    ->where('essay_id', $request->essay_id)
                    ->first();

        //update jawaban
        if($answer) {
            $answer->answer     = $request->answer;
            $answer->is_correct = $result;
            $answer->update();
        }

        return redirect()->back();
    }

    /**
     * endExam
     *
     * @param  mixed $request
     * @return void
     */
    public function endEssay(Request $request)
    {
        //count jawaban benar
        $count_answer = AnswerEssay::where('exam_id', $request->exam_id)
                            ->where('exam_session_id', $request->exam_session_id)
                            ->where('student_id', auth()->guard('student')->user()->id)
                            ->where('is_correct', 'Y')
                            ->count();

        //count jumlah soal
        $count_essay = Essay::where('exam_id', $request->exam_id)->count();

        //hitung nilai
        // $grade_exam = round($count_answer/$count_essay*100, 2);

        //update nilai di table grades
        $grade = Grade::where('exam_id', $request->exam_id)
                ->where('exam_session_id', $request->exam_session_id)
                ->where('student_id', auth()->guard('student')->user()->id)
                ->first();
        
        $grade->end_time        = Carbon::now();
        $grade->total_correct   = $count_answer;
        // $grade->grade           = $grade_exam;
        $grade->update();

        //redirect hasil
        return redirect()->route('student.essays.resultEssay', $request->exam_group_id);
    }

    /**
     * resultExam
     *
     * @param  mixed $id
     * @return void
     */
    public function resultEssay($exam_group_id)
    {
        //get exam group
        $exam_group = ExamGroup::with('exam', 'exam_session', 'student.classroom')
                ->where('student_id', auth()->guard('student')->user()->id)
                ->where('id', $exam_group_id)
                ->first();

        //get grade / nilai
        $grade = Grade::where('exam_id', $exam_group->exam->id)
                ->where('exam_session_id', $exam_group->exam_session->id)
                ->where('student_id', auth()->guard('student')->user()->id)
                ->first();

        //return with inertia
        return inertia('Student/Essays/Result', [
            'exam_group' => $exam_group,
            'grade'      => $grade,
        ]);
    }
}
