<?php

namespace Tests\Feature\Student;

use App\Models\Answer;
use App\Models\AnswerEssay;
use App\Models\Essay;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Feature\Concerns\CreatesExamFixtures;
use Tests\TestCase;

class ExamSecurityTest extends TestCase
{
    use RefreshDatabase;
    use CreatesExamFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    /** Siapkan ujian PG dengan satu soal (kunci = 3) dan peserta yang sudah login. */
    private function pgExam(): array
    {
        $classroom = $this->makeClassroom();
        $exam      = $this->makeExam($classroom);
        $session   = $this->makeSession($exam);
        $student   = $this->makeStudent($classroom);
        [$group, $grade] = $this->enroll($student, $exam, $session);

        $question = Question::forceCreate([
            'exam_id'  => $exam->id,
            'question' => 'Soal 1',
            'option_1' => 'A', 'option_2' => 'B', 'option_3' => 'C', 'option_4' => 'D',
            'answer'   => 3,
        ]);

        $this->actingAsStudent($student);

        return compact('exam', 'session', 'student', 'group', 'grade', 'question');
    }

    private function answer(array $ctx, int $option, int $duration = 1_000_000)
    {
        return $this->post('/student/exam-answer', [
            'exam_id'         => $ctx['exam']->id,
            'exam_session_id' => $ctx['session']->id,
            'question_id'     => $ctx['question']->id,
            'answer'          => $option,
            'duration'        => $duration,
        ]);
    }

    public function test_exam_page_does_not_expose_answer_key_or_correctness(): void
    {
        $ctx = $this->pgExam();
        $this->get("/student/exam-start/{$ctx['group']->id}");
        $this->answer($ctx, 3);

        $this->get("/student/exam/{$ctx['group']->id}/1")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Student/Exams/Show')
                ->where('all_questions.0.question.question', 'Soal 1')
                ->missing('all_questions.0.question.answer')
                ->missing('all_questions.0.is_correct')
                ->missing('question_active.question.answer')
                ->missing('question_active.is_correct')
            );
    }

    public function test_essay_page_does_not_expose_model_answer(): void
    {
        $classroom = $this->makeClassroom();
        $exam      = $this->makeExam($classroom, 'Essay');
        $session   = $this->makeSession($exam);
        $student   = $this->makeStudent($classroom);
        [$group] = $this->enroll($student, $exam, $session);

        Essay::forceCreate([
            'exam_id'     => $exam->id,
            'essays_code' => 'ESS-1',
            'question'    => 'Jelaskan K3',
            'answer'      => 'KUNCI RAHASIA',
        ]);

        $this->actingAsStudent($student);
        $this->get("/student/essay-start/{$group->id}");

        $this->get("/student/essay/{$group->id}/1")
            ->assertOk()
            ->assertDontSee('KUNCI RAHASIA')
            ->assertInertia(fn (Assert $page) => $page
                ->missing('all_essays.0.essay.answer')
                ->missing('essay_active.essay.answer')
            );
    }

    public function test_client_cannot_extend_remaining_time(): void
    {
        $ctx = $this->pgExam();
        $this->get("/student/exam-start/{$ctx['group']->id}");

        $this->putJson("/student/exam-duration/update/{$ctx['grade']->id}", ['duration' => 1_000])->assertOk();
        $this->putJson("/student/exam-duration/update/{$ctx['grade']->id}", ['duration' => 99_999_999])->assertOk();

        $this->assertSame(1_000, (int) $ctx['grade']->fresh()->duration);
    }

    public function test_starting_again_does_not_reset_start_time(): void
    {
        $ctx = $this->pgExam();

        $this->get("/student/exam-start/{$ctx['group']->id}");
        $firstStart = $ctx['grade']->fresh()->start_time;

        $this->travel(10)->minutes();
        $this->get("/student/exam-start/{$ctx['group']->id}")
            ->assertRedirect(route('student.exams.show', ['id' => $ctx['group']->id, 'page' => 1]));

        $this->assertEquals($firstStart, $ctx['grade']->fresh()->start_time);
    }

    public function test_answers_are_rejected_after_exam_ended(): void
    {
        $ctx = $this->pgExam();
        $this->get("/student/exam-start/{$ctx['group']->id}");
        $this->answer($ctx, 1);

        $this->post('/student/exam-end', [
            'exam_id'         => $ctx['exam']->id,
            'exam_session_id' => $ctx['session']->id,
            'exam_group_id'   => $ctx['group']->id,
        ]);

        $this->answer($ctx, 3);

        $answer = Answer::where('student_id', $ctx['student']->id)->first();
        $this->assertSame(1, (int) $answer->answer);
        $this->assertSame('N', $answer->is_correct);
        $this->assertEquals(0, (float) $ctx['grade']->fresh()->grade);
    }

    public function test_answers_are_rejected_after_server_deadline(): void
    {
        $ctx = $this->pgExam();
        $this->get("/student/exam-start/{$ctx['group']->id}");

        // Durasi 60 menit + toleransi 30 menit; klien "membekukan" timernya.
        $this->travel(91)->minutes();
        $this->answer($ctx, 3, duration: 3_600_000);

        $this->assertSame(0, (int) Answer::where('student_id', $ctx['student']->id)->first()->answer);
    }

    public function test_ending_twice_does_not_overwrite_result(): void
    {
        $ctx = $this->pgExam();
        $this->get("/student/exam-start/{$ctx['group']->id}");
        $this->answer($ctx, 3);

        $end = fn () => $this->post('/student/exam-end', [
            'exam_id'         => $ctx['exam']->id,
            'exam_session_id' => $ctx['session']->id,
            'exam_group_id'   => $ctx['group']->id,
        ]);

        $end();
        $grade = $ctx['grade']->fresh();
        $this->assertEquals(100, (float) $grade->grade);

        $this->travel(5)->minutes();
        $end();

        $this->assertEquals($grade->end_time, $ctx['grade']->fresh()->end_time);
    }

    public function test_student_cannot_update_another_students_grade(): void
    {
        $ctx   = $this->pgExam();
        $other = $this->makeStudent($ctx['exam']->classroom);
        [, $otherGrade] = $this->enroll($other, $ctx['exam'], $ctx['session']);

        $this->putJson("/student/exam-duration/update/{$otherGrade->id}", ['duration' => 1])->assertNotFound();
        $this->assertSame(3_600_000, (int) $otherGrade->fresh()->duration);
    }

    public function test_essay_answer_is_saved_while_exam_is_running(): void
    {
        $classroom = $this->makeClassroom();
        $exam      = $this->makeExam($classroom, 'Essay');
        $session   = $this->makeSession($exam);
        $student   = $this->makeStudent($classroom);
        [$group] = $this->enroll($student, $exam, $session);
        $essay = Essay::forceCreate(['exam_id' => $exam->id, 'essays_code' => 'E1', 'question' => 'Q', 'answer' => 'K']);

        $this->actingAsStudent($student);

        $payload = ['exam_id' => $exam->id, 'exam_session_id' => $session->id, 'essay_id' => $essay->id, 'answer' => 'jawaban', 'duration' => 1000];

        $this->get("/student/essay-start/{$group->id}");
        $this->post('/student/essay-answer', $payload);

        $this->assertSame('jawaban', AnswerEssay::where('student_id', $student->id)->value('answer'));
    }
}
