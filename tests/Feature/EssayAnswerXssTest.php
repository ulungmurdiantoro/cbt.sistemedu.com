<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AnswerEssay;
use App\Models\AsesorAssignment;
use App\Models\Essay;
use App\Models\User;
use App\Models\UserRoleAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Feature\Concerns\CreatesExamFixtures;
use Tests\TestCase;

class EssayAnswerXssTest extends TestCase
{
    use CreatesExamFixtures;
    use RefreshDatabase;

    private const PAYLOAD = '<p>Jawaban saya</p><img src=x onerror="fetch(\'/admin/users\')"><script>alert(1)</script>';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function essayExam(string $type = 'Essay'): array
    {
        $classroom = $this->makeClassroom();
        $exam      = $this->makeExam($classroom, $type);
        $session   = $this->makeSession($exam);
        $student   = $this->makeStudent($classroom);
        [$group] = $this->enroll($student, $exam, $session);
        $essay = Essay::forceCreate(['exam_id' => $exam->id, 'essays_code' => 'E-' . Str::random(4), 'question' => 'Q', 'answer' => '-']);

        return compact('exam', 'session', 'student', 'group', 'essay');
    }

    private function assertClean(?string $html): void
    {
        $this->assertStringContainsString('Jawaban saya', $html);
        $this->assertStringNotContainsStringIgnoringCase('onerror', $html);
        $this->assertStringNotContainsStringIgnoringCase('<script', $html);
    }

    public function test_essay_answer_is_sanitized_when_saved_and_shown_to_asesor(): void
    {
        $ctx = $this->essayExam();

        $this->actingAsStudent($ctx['student']);
        $this->get("/student/essay-start/{$ctx['group']->id}");
        $this->post('/student/essay-answer', [
            'exam_id'         => $ctx['exam']->id,
            'exam_session_id' => $ctx['session']->id,
            'essay_id'        => $ctx['essay']->id,
            'answer'          => self::PAYLOAD,
            'duration'        => 1000,
        ]);

        $this->assertClean(AnswerEssay::where('student_id', $ctx['student']->id)->value('answer'));

        $asesor = User::forceCreate(['users_code' => 'A1', 'name' => 'A', 'email' => 'a@example.com', 'password' => 'x']);
        UserRoleAssignment::forceCreate(['user_id' => $asesor->id, 'role' => UserRole::Asesor->value]);
        AsesorAssignment::forceCreate(['user_id' => $asesor->id, 'exam_session_id' => $ctx['session']->id, 'student_id' => $ctx['student']->id]);

        $this->actingAs($asesor)
            ->get("/asesor/penilaian/{$ctx['session']->id}/esai")
            ->assertOk()
            ->assertDontSee('onerror', false)
            ->assertInertia(fn (Assert $page) => $page->component('Asesor/Esai/Show'));
    }

    public function test_essay_migas_text_answer_is_sanitized(): void
    {
        $ctx = $this->essayExam('Essay Migas');

        $this->actingAsStudent($ctx['student']);
        $this->get("/student/essay-migas-start/{$ctx['group']->id}");
        $this->postJson('/student/essay-migas-answer-text', [
            'exam_id'         => $ctx['exam']->id,
            'exam_session_id' => $ctx['session']->id,
            'exam_group_id'   => $ctx['group']->id,
            'essay_id'        => $ctx['essay']->id,
            'answer'          => self::PAYLOAD,
        ])->assertOk();

        $this->assertClean(AnswerEssay::where('student_id', $ctx['student']->id)->value('answer'));
    }

    public function test_migration_cleans_answers_stored_before_the_fix(): void
    {
        $ctx = $this->essayExam();

        // Tulis mentah lewat Query Builder, seperti data lama sebelum cast ada.
        $id = DB::table('answer_essays')->insertGetId([
            'answeressays_code' => 'old-1',
            'exam_id'           => $ctx['exam']->id,
            'exam_session_id'   => $ctx['session']->id,
            'essay_id'          => $ctx['essay']->id,
            'student_id'        => $ctx['student']->id,
            'essay_order'       => 1,
            'answer_order'      => '1',
            'answer'            => self::PAYLOAD,
        ]);

        (require database_path('migrations/2026_09_24_000001_sanitize_existing_answer_essays_html.php'))->up();

        $this->assertClean(DB::table('answer_essays')->where('id', $id)->value('answer'));
    }
}
