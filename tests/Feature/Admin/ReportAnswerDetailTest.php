<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Answer;
use App\Models\AnswerEssay;
use App\Models\Essay;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\Question;
use App\Models\Student;
use App\Models\User;
use App\Models\UserRoleAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Feature\Concerns\CreatesExamFixtures;
use Tests\TestCase;

/** Detail jawaban peserta (admin/reports/{grade}) — dibuka dari Rekap Hasil. */
class ReportAnswerDetailTest extends TestCase
{
    use RefreshDatabase, CreatesExamFixtures;

    private function user(string $name, UserRole $role): User
    {
        $user = User::forceCreate([
            'users_code' => 'U-' . Str::random(6),
            'name'       => $name,
            'email'      => Str::random(8) . '@example.com',
            'password'   => bcrypt('password'),
        ]);
        UserRoleAssignment::forceCreate(['user_id' => $user->id, 'role' => $role->value]);

        return $user;
    }

    private function question(Exam $exam, int $key, bool $fiveOptions = true): Question
    {
        return Question::forceCreate([
            'exam_id'  => $exam->id,
            'question' => 'Soal ' . Str::random(4),
            'option_1' => 'Satu',
            'option_2' => 'Dua',
            'option_3' => $fiveOptions ? 'Tiga' : null,
            'option_4' => $fiveOptions ? 'Empat' : null,
            'option_5' => $fiveOptions ? 'Lima' : null,
            'answer'   => $key,
        ]);
    }

    private function answer(Question $q, ExamSession $session, Student $student, int $chosen, string $correct): void
    {
        Answer::forceCreate([
            'answers_code'    => 'answ-' . Str::ulid(),
            'exam_id'         => $q->exam_id,
            'exam_session_id' => $session->id,
            'question_id'     => $q->id,
            'student_id'      => $student->id,
            'question_order'  => 1,
            'answer_order'    => '1,2,3,4,5',
            'answer'          => $chosen,
            'is_correct'      => $correct,
        ]);
    }

    private function essayAnswer(Essay $essay, ExamSession $session, Student $student, array $attrs): void
    {
        AnswerEssay::forceCreate(array_merge([
            'answeressays_code' => 'answess-' . Str::ulid(),
            'exam_id'           => $essay->exam_id,
            'exam_session_id'   => $session->id,
            'essay_id'          => $essay->id,
            'student_id'        => $student->id,
            'essay_order'       => 1,
            'answer_order'      => '1',
        ], $attrs));
    }

    public function test_pg_detail_matches_answers_by_question_and_session(): void
    {
        $classroom = $this->makeClassroom();
        $exam      = $this->makeExam($classroom);
        $session   = $this->makeSession($exam);
        $other     = $this->makeSession($exam);
        $student   = $this->makeStudent($classroom);
        [, $grade] = $this->enroll($student, $exam, $session);

        // questions() diurutkan id DESC → soal terakhir dibuat = nomor 1
        $kosong = $this->question($exam, 1, fiveOptions: false);
        $salah  = $this->question($exam, 3);
        $benar  = $this->question($exam, 2);

        $this->answer($benar, $session, $student, 2, 'Y');
        $this->answer($salah, $session, $student, 4, 'N');
        $this->answer($kosong, $session, $student, 0, 'N');
        // Jawaban di sesi lain tidak boleh ikut
        $this->answer($kosong, $other, $student, 1, 'Y');

        $this->actingAs($this->user('Admin', UserRole::Admin))->get("/admin/reports/{$grade->id}")
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Reports/Show')
                ->where('grade.student.name', $student->name)
                ->where('essays', null)
                ->has('questions', 3)
                ->where('questions.0.status', 'benar')
                ->where('questions.0.chosen', 2)
                ->where('questions.0.correct', 2)
                ->has('questions.0.options', 5)
                ->where('questions.1.status', 'salah')
                ->where('questions.1.chosen', 4)
                ->where('questions.1.correct', 3)
                ->where('questions.2.status', 'kosong')
                ->where('questions.2.chosen', null)
                ->has('questions.2.options', 2)
            );
    }

    public function test_essay_detail_shows_score_assessor_and_guide(): void
    {
        $classroom = $this->makeClassroom();
        $exam      = $this->makeExam($classroom, 'Essay');
        $session   = $this->makeSession($exam);
        $student   = $this->makeStudent($classroom);
        [, $grade] = $this->enroll($student, $exam, $session);
        $asesor    = $this->user('Budi Asesor', UserRole::Asesor);

        // Kolom essays.is_essay tidak ada di migrasi (hanya di DB produksi) → label default "Jawaban benar"
        Essay::forceCreate(['exam_id' => $exam->id, 'essays_code' => 'E1', 'question' => 'Q2', 'answer' => '<p></p>']);
        $answered = Essay::forceCreate(['exam_id' => $exam->id, 'essays_code' => 'E2', 'question' => 'Q1', 'answer' => '<p>Kunci</p>']);

        $this->essayAnswer($answered, $session, $student, [
            'answer' => '<p>Jawaban saya</p>', 'score' => 85, 'assessed_by' => $asesor->id, 'assessed_at' => '2026-10-05 09:00:00',
        ]);

        $this->actingAs($this->user('Admin', UserRole::Admin))->get("/admin/reports/{$grade->id}")
            ->assertInertia(fn (Assert $page) => $page
                ->where('questions', null)
                ->has('essays', 2)
                ->where('essays.0.question', 'Q1')
                ->where('essays.0.answer', '<p>Jawaban saya</p>')
                ->where('essays.0.assessor', 'Budi Asesor')
                ->where('essays.0.guide', '<p>Kunci</p>')
                ->where('essays.0.guide_label', 'Jawaban benar')
                ->where('essays.0.score', fn ($s) => (float) $s === 85.0)
                ->where('essays.1.answer', null)
                ->where('essays.1.guide', null)
                ->where('essays.1.score', null)
                ->where('files', [])
            );
    }

    public function test_essay_migas_detail_lists_the_uploaded_file_once(): void
    {
        $classroom = $this->makeClassroom();
        $exam      = $this->makeExam($classroom, 'Essay Migas');
        $session   = $this->makeSession($exam);
        $student   = $this->makeStudent($classroom);
        [, $grade] = $this->enroll($student, $exam, $session);

        $path = "essay_migas_answers/{$exam->id}/{$session->id}/{$student->id}/jawaban-akhir.pdf";
        foreach (['E1', 'E2'] as $code) {
            $essay = Essay::forceCreate(['exam_id' => $exam->id, 'essays_code' => $code, 'question' => $code, 'answer' => '-']);
            $this->essayAnswer($essay, $session, $student, ['answer' => $path]);
        }

        $this->actingAs($this->user('Admin', UserRole::Admin))->get("/admin/reports/{$grade->id}")
            ->assertInertia(fn (Assert $page) => $page
                ->has('files', 1)
                ->where('files.0.name', 'jawaban-akhir.pdf')
                ->where('files.0.download_url', fn ($u) => str_starts_with($u, '/admin/essay-migas/') && str_ends_with($u, '/download'))
                ->where('files.0.preview_url', fn ($u) => str_ends_with($u, '/preview'))
                ->where('essays.0.by_file', true)
                ->where('essays.0.answer', null)
                // kunci jawaban "-" = pengisi kosong, tidak ditampilkan
                ->where('essays.0.guide', null)
            );
    }
}
