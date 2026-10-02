<?php

namespace Tests\Feature\Asesor;

use App\Enums\UserRole;
use App\Models\AsesorAssignment;
use App\Models\AssessmentApplication;
use App\Models\Participant;
use App\Models\Student;
use App\Models\User;
use App\Models\UserRoleAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Feature\Concerns\CreatesExamFixtures;
use Tests\TestCase;

/** Preview FR.AK.01 di halaman TTD AK.01 asesor. */
class TtdAk01PreviewTest extends TestCase
{
    use RefreshDatabase;
    use CreatesExamFixtures;

    private array $ctx;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('private');

        $classroom = $this->makeClassroom();
        $session   = $this->makeSession($this->makeExam($classroom));
        $student   = $this->makeStudent($classroom);
        $asesor    = $this->asesor();

        AsesorAssignment::forceCreate(['user_id' => $asesor->id, 'exam_session_id' => $session->id, 'student_id' => $student->id]);

        $this->ctx = compact('classroom', 'session', 'student', 'asesor');
    }

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

    private function application(array $attrs = []): AssessmentApplication
    {
        $participant = Participant::forceCreate([
            'name'     => 'Budi Peserta',
            'email'    => Str::random(8) . '@example.com',
            'password' => bcrypt('password'),
        ]);

        return AssessmentApplication::forceCreate($attrs + [
            'code'            => 'APL-' . Str::random(8),
            'participant_id'  => $participant->id,
            'classroom_id'    => $this->ctx['classroom']->id,
            'exam_session_id' => $this->ctx['session']->id,
            'student_id'      => $this->ctx['student']->id,
            'kode_batch'      => '-',
            'tujuan_asesmen'  => 'Sertifikasi',
            'status'          => 'approved',
        ]);
    }

    private function previewUrl(?Student $student = null): string
    {
        $student ??= $this->ctx['student'];

        return "/asesor/penilaian/{$this->ctx['session']->id}/ttd-ak01/{$student->id}/preview";
    }

    public function test_preview_renders_fr_ak01_inline(): void
    {
        $this->application();

        $response = $this->actingAs($this->ctx['asesor'])
            ->get($this->previewUrl())
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertStringStartsWith('inline', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('Budi Peserta - FR.AK.01.pdf', $response->headers->get('Content-Disposition'));
    }

    public function test_stamped_version_is_shown_when_available(): void
    {
        config(['materai.enabled' => true]);
        Storage::disk('private')->put('materai/fr-ak-01/1.pdf', '%PDF-stamped');
        $this->application(['materai_status' => 'stamped', 'materai_document_path' => 'materai/fr-ak-01/1.pdf']);

        $response = $this->actingAs($this->ctx['asesor'])->get($this->previewUrl())->assertOk();

        $this->assertStringContainsString('(materai)', $response->headers->get('Content-Disposition'));
    }

    public function test_unassigned_asesor_cannot_preview(): void
    {
        $this->application();

        $this->actingAs($this->asesor())->get($this->previewUrl())->assertForbidden();
    }

    public function test_participant_without_application_has_no_preview(): void
    {
        $this->actingAs($this->ctx['asesor'])->get($this->previewUrl())->assertNotFound();
    }
}
