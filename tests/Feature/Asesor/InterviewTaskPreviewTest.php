<?php

namespace Tests\Feature\Asesor;

use App\Enums\UserRole;
use App\Models\AsesorAssignment;
use App\Models\StudentTask;
use App\Models\User;
use App\Models\UserRoleAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;
use Tests\Feature\Concerns\CreatesExamFixtures;
use Tests\TestCase;

/** Tugas peserta di halaman penilaian wawancara hanya bisa dipratinjau, tidak diunduh. */
class InterviewTaskPreviewTest extends TestCase
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

        $this->ctx = compact('session', 'student', 'asesor');
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

    private function task(string $filename): StudentTask
    {
        $path = "student_tasks/{$this->ctx['session']->id}/{$this->ctx['student']->id}/{$filename}";
        Storage::disk('private')->put($path, 'isi file');

        return StudentTask::forceCreate([
            'student_id'        => $this->ctx['student']->id,
            'exam_session_id'   => $this->ctx['session']->id,
            'file_path'         => $path,
            'original_filename' => $filename,
            'file_size'         => 8,
            'uploaded_at'       => now(),
        ]);
    }

    private function previewUrl(): string
    {
        return "/asesor/penilaian/{$this->ctx['session']->id}/wawancara/tugas/{$this->ctx['student']->id}";
    }

    public function test_preview_serves_file_inline_without_caching(): void
    {
        $this->task('tugas.pdf');

        $response = $this->actingAs($this->ctx['asesor'])
            ->get($this->previewUrl(), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->assertStringStartsWith('inline', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_opening_preview_url_directly_does_not_serve_the_file(): void
    {
        $this->task('tugas.pdf');

        $this->actingAs($this->ctx['asesor'])
            ->get($this->previewUrl())
            ->assertRedirect("/asesor/penilaian/{$this->ctx['session']->id}/wawancara");
    }

    public function test_unassigned_asesor_cannot_preview(): void
    {
        $this->task('tugas.pdf');

        $this->actingAs($this->asesor())
            ->get($this->previewUrl(), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertForbidden();
    }

    public function test_format_that_cannot_be_previewed_is_not_served(): void
    {
        $this->task('tugas.zip');

        $this->actingAs($this->ctx['asesor'])
            ->get($this->previewUrl(), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertStatus(415);
    }

    public function test_page_marks_which_tasks_can_be_previewed(): void
    {
        $this->task('Tugas Akhir.DOCX');

        $this->actingAs($this->ctx['asesor'])
            ->get("/asesor/penilaian/{$this->ctx['session']->id}/wawancara")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Asesor/Wawancara/Show')
                ->where("tugas.{$this->ctx['student']->id}", [
                    'original_filename' => 'Tugas Akhir.DOCX',
                    'type'              => 'docx',
                    'previewable'       => true,
                ]));
    }
}
