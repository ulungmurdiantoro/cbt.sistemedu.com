<?php

namespace Tests\Feature\Asesor;

use App\Enums\UserRole;
use App\Models\AnswerEssay;
use App\Models\AsesorAssignment;
use App\Models\Essay;
use App\Models\User;
use App\Models\UserRoleAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Feature\Concerns\CreatesExamFixtures;
use Tests\TestCase;

class EssayAssessmentTest extends TestCase
{
    use RefreshDatabase;
    use CreatesExamFixtures;

    private function asesor(): User
    {
        $user = User::forceCreate([
            'users_code' => 'ASR-' . Str::random(6),
            'name'       => 'Asesor Test',
            'email'      => Str::random(8) . '@example.com',
            'password'   => bcrypt('password'),
        ]);

        UserRoleAssignment::forceCreate(['user_id' => $user->id, 'role' => UserRole::Asesor->value]);

        return $user;
    }

    /** Sesi esai dengan dua soal & satu peserta yang sudah menjawab keduanya. */
    private function essaySession(): array
    {
        $classroom = $this->makeClassroom();
        $exam      = $this->makeExam($classroom, 'Essay');
        $session   = $this->makeSession($exam);
        $student   = $this->makeStudent($classroom);
        [, $grade] = $this->enroll($student, $exam, $session);

        $answers = collect([1, 2])->map(function ($n) use ($exam, $session, $student) {
            $essay = Essay::forceCreate(['exam_id' => $exam->id, 'essays_code' => "E{$n}", 'question' => "Q{$n}", 'answer' => '-']);

            return AnswerEssay::forceCreate([
                'answeressays_code' => 'answess-' . Str::ulid(),
                'exam_id'           => $exam->id,
                'exam_session_id'   => $session->id,
                'essay_id'          => $essay->id,
                'student_id'        => $student->id,
                'essay_order'       => $n,
                'answer_order'      => '1',
                'answer'            => "jawaban {$n}",
            ]);
        });

        return compact('session', 'student', 'grade', 'answers');
    }

    private function payload(array $ctx, array $scores): array
    {
        return ['scores' => [[
            'student_id' => $ctx['student']->id,
            'answers'    => $ctx['answers']->values()->map(fn ($a, $i) => [
                'answer_essay_id' => $a->id,
                'score'           => $scores[$i],
            ])->all(),
        ]]];
    }

    public function test_asesor_cannot_score_unassigned_participant(): void
    {
        $ctx = $this->essaySession();

        $this->actingAs($this->asesor())
            ->post("/asesor/penilaian/{$ctx['session']->id}/esai", $this->payload($ctx, [90, 90]))
            ->assertForbidden();

        $this->assertNull($ctx['answers'][0]->fresh()->score);
    }

    public function test_answer_key_is_not_sent_while_switched_off(): void
    {
        $ctx    = $this->essaySession();
        $asesor = $this->asesor();
        AsesorAssignment::forceCreate(['user_id' => $asesor->id, 'exam_session_id' => $ctx['session']->id, 'student_id' => $ctx['student']->id]);
        Essay::find($ctx['answers'][0]->essay_id)->forceFill(['answer' => '<p>kisi 1</p>'])->save();

        config(['lsp.asesor_show_answer_key' => false]);

        $this->actingAs($asesor)
            ->get("/asesor/penilaian/{$ctx['session']->id}/esai")
            ->assertOk()
            ->assertDontSee('kisi 1')
            ->assertInertia(fn ($page) => $page->where('essays.0.guide', null));
    }

    public function test_show_sends_guide_and_hides_migas_file_path(): void
    {
        config(['lsp.asesor_show_answer_key' => true]);

        $ctx    = $this->essaySession();
        $asesor = $this->asesor();
        AsesorAssignment::forceCreate(['user_id' => $asesor->id, 'exam_session_id' => $ctx['session']->id, 'student_id' => $ctx['student']->id]);

        Essay::find($ctx['answers'][0]->essay_id)->forceFill(['answer' => '<p>kisi 1</p>'])->save();
        $ctx['answers'][1]->forceFill(['answer' => 'essay_migas_answers/berkas.pdf'])->save();

        $this->actingAs($asesor)
            ->get("/asesor/penilaian/{$ctx['session']->id}/esai")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('essays.0.guide', '<p>kisi 1</p>')
                ->where('essays.1.guide', null)          // "-" = kunci tidak diisi
                ->where('students_data.0.answers.0.answer', 'jawaban 1')
                ->where('students_data.0.answers.1.answer', null)
                ->where('students_data.0.answers.1.by_file', true)
                ->where('students_data.0.files.0.name', 'berkas.pdf'));
    }

    public function test_unassigned_asesor_cannot_download_answer_file(): void
    {
        $ctx = $this->essaySession();
        $ctx['answers'][0]->forceFill(['answer' => 'essay_migas_answers/berkas.pdf'])->save();

        $this->actingAs($this->asesor())
            ->get("/asesor/penilaian/{$ctx['session']->id}/esai/berkas/{$ctx['answers'][0]->id}")
            ->assertForbidden();
    }

    public function test_zero_score_is_included_in_the_average(): void
    {
        $ctx    = $this->essaySession();
        $asesor = $this->asesor();
        AsesorAssignment::forceCreate(['user_id' => $asesor->id, 'exam_session_id' => $ctx['session']->id, 'student_id' => $ctx['student']->id]);

        $this->actingAs($asesor)
            ->post("/asesor/penilaian/{$ctx['session']->id}/esai", $this->payload($ctx, [80, 0]))
            ->assertSessionHasNoErrors();

        $this->assertEquals(40, (float) $ctx['grade']->fresh()->grade);
    }
}
