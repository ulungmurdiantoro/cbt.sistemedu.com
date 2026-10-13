<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\ParticipantResult;
use App\Models\User;
use App\Models\UserRoleAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;
use Tests\Feature\Concerns\CreatesExamFixtures;
use Tests\TestCase;

/** Status materai FR.AK.14 di Rekap Hasil admin (admin/results/{sesi}) + buka PDF-nya. */
class ResultFrAk14Test extends TestCase
{
    use RefreshDatabase;
    use CreatesExamFixtures;

    private array $ctx;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('private');
        config(['materai.enabled' => true, 'materai.first_session_id' => null]);

        $classroom = $this->makeClassroom();
        $exam      = $this->makeExam($classroom);
        $session   = $this->makeSession($exam);
        $student   = $this->makeStudent($classroom);
        $this->enroll($student, $exam, $session);

        $this->ctx = ['session' => $session, 'student' => $student, 'admin' => $this->admin()];
    }

    private function admin(): User
    {
        $user = User::forceCreate([
            'users_code' => 'U-' . Str::random(6),
            'name'       => 'Admin Test',
            'email'      => Str::random(8) . '@example.com',
            'password'   => bcrypt('password'),
        ]);

        UserRoleAssignment::forceCreate(['user_id' => $user->id, 'role' => UserRole::Admin->value]);

        return $user;
    }

    private function participantResult(array $attrs = []): ParticipantResult
    {
        return ParticipantResult::forceCreate($attrs + [
            'exam_session_id' => $this->ctx['session']->id,
            'student_id'      => $this->ctx['student']->id,
            'nilai_akhir'     => 85,
            'keputusan'       => 'LULUS',
            'is_finalized'    => true,
        ]);
    }

    private function pageUrl(): string
    {
        return "/admin/results/{$this->ctx['session']->id}";
    }

    private function pdfUrl(): string
    {
        return "/admin/results/{$this->ctx['session']->id}/fr-ak-14/{$this->ctx['student']->id}";
    }

    public function test_failed_materai_shows_its_reason(): void
    {
        $this->participantResult([
            'fr_ak_14_signed_at'     => now(),
            'materai_status'         => 'failed',
            'materai_failure_reason' => 'Generate Serial Number Peruri gagal: Saldo Tidak Cukup',
        ]);

        $this->actingAs($this->ctx['admin'])
            ->get($this->pageUrl())
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Results/Show')
                ->where('fr_ak_14_requires_materai', true)
                ->where('rows.0.fr_ak_14.state', 'failed')
                ->where('rows.0.fr_ak_14.failure_reason', 'Generate Serial Number Peruri gagal: Saldo Tidak Cukup'));
    }

    public function test_signed_but_not_yet_stamped_is_pending(): void
    {
        $this->participantResult(['fr_ak_14_signed_at' => now()]);

        $this->actingAs($this->ctx['admin'])
            ->get($this->pageUrl())
            ->assertInertia(fn (AssertableInertia $page) => $page->where('rows.0.fr_ak_14.state', 'pending'));
    }

    public function test_exempt_session_needs_signature_only(): void
    {
        config(['materai.first_session_id' => $this->ctx['session']->id + 1]);
        $this->participantResult(['fr_ak_14_signed_at' => now()]);

        $this->actingAs($this->ctx['admin'])
            ->get($this->pageUrl())
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('fr_ak_14_requires_materai', false)
                ->where('rows.0.fr_ak_14.state', 'no_materai'));
    }

    public function test_unsigned_and_not_passed_participants(): void
    {
        $this->participantResult();

        $this->actingAs($this->ctx['admin'])
            ->get($this->pageUrl())
            ->assertInertia(fn (AssertableInertia $page) => $page->where('rows.0.fr_ak_14.state', 'unsigned'));

        ParticipantResult::query()->update(['keputusan' => 'TIDAK_LULUS']);

        $this->actingAs($this->ctx['admin'])
            ->get($this->pageUrl())
            ->assertInertia(fn (AssertableInertia $page) => $page->where('rows.0.fr_ak_14', null));
    }

    public function test_stamped_pdf_is_served(): void
    {
        Storage::disk('private')->put('materai/fr-ak-14/1.pdf', '%PDF-stamped');
        $this->participantResult([
            'fr_ak_14_signed_at'    => now(),
            'materai_status'        => 'stamped',
            'materai_document_path' => 'materai/fr-ak-14/1.pdf',
        ]);

        $response = $this->actingAs($this->ctx['admin'])->get($this->pdfUrl())->assertOk();

        $this->assertStringContainsString('_materai.pdf', $response->headers->get('Content-Disposition'));
    }

    public function test_signed_without_materai_is_generated(): void
    {
        $this->participantResult(['fr_ak_14_signed_at' => now()]);

        $response = $this->actingAs($this->ctx['admin'])
            ->get($this->pdfUrl())
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertStringNotContainsString('_materai', $response->headers->get('Content-Disposition'));
    }

    public function test_unsigned_fr_ak14_has_no_pdf(): void
    {
        $this->participantResult();

        $this->actingAs($this->ctx['admin'])->get($this->pdfUrl())->assertNotFound();
    }
}
