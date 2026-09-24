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
