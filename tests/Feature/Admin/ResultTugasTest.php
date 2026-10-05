<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\StudentTask;
use App\Models\User;
use App\Models\UserRoleAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;
use Tests\Feature\Concerns\CreatesExamFixtures;
use Tests\TestCase;

/** Tugas peserta di Rekap Hasil admin (admin/results/{sesi}): kolom Tugas + pratinjau di tab baru + unduh. */
class ResultTugasTest extends TestCase
{
    use RefreshDatabase;
    use CreatesExamFixtures;

    private array $ctx;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('private');

        $classroom = $this->makeClassroom('FMO');
        $exam      = $this->makeExam($classroom);
        $session   = $this->makeSession($exam);
        $student   = $this->makeStudent($classroom);
        $this->enroll($student, $exam, $session);

        $this->ctx = ['session' => $session, 'student' => $student, 'admin' => $this->user(UserRole::Admin)];
    }

    private function user(UserRole $role): User
    {
        $user = User::forceCreate([
            'users_code' => 'U-' . Str::random(6),
            'name'       => 'User Test',
            'email'      => Str::random(8) . '@example.com',
            'password'   => bcrypt('password'),
        ]);

        UserRoleAssignment::forceCreate(['user_id' => $user->id, 'role' => $role->value]);

        return $user;
    }

    private function task(string $filename): StudentTask
    {
        $ext  = pathinfo($filename, PATHINFO_EXTENSION);
        $path = "student_tasks/{$this->ctx['session']->id}/{$this->ctx['student']->id}/tugas-" . Str::random(6) . ".{$ext}";
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

    private function fileUrl(): string
    {
        return "/admin/results/{$this->ctx['session']->id}/tugas/{$this->ctx['student']->id}";
    }

    public function test_results_page_lists_tasks(): void
    {
        $this->task('Tugas Akhir.DOCX');

        $this->actingAs($this->ctx['admin'])
            ->get("/admin/results/{$this->ctx['session']->id}")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Results/Show')
                ->where('show_tugas', true)
                ->where("tugas.{$this->ctx['student']->id}", ['original_filename' => 'Tugas Akhir.DOCX']));
    }

    public function test_tugas_column_hidden_for_exempt_scheme_without_uploads(): void
    {
        $classroom = $this->makeClassroom('LEM');
        $exam      = $this->makeExam($classroom);
        $session   = $this->makeSession($exam);
        $this->enroll($this->makeStudent($classroom), $exam, $session);

        $this->actingAs($this->ctx['admin'])
            ->get("/admin/results/{$session->id}")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('show_tugas', false));
    }

    public function test_tugas_column_shown_for_required_scheme_before_upload(): void
    {
        $this->actingAs($this->ctx['admin'])
            ->get("/admin/results/{$this->ctx['session']->id}")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('show_tugas', true));
    }

    public function test_preview_page_shows_task_info(): void
    {
        $this->task('Tugas Akhir.DOCX');

        $this->actingAs($this->ctx['admin'])
            ->get($this->fileUrl())
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Results/Tugas')
                ->where('student.no_participant', $this->ctx['student']->no_participant)
                ->where('tugas.original_filename', 'Tugas Akhir.DOCX')
                ->where('tugas.type', 'docx')
                ->where('tugas.previewable', true));
    }

    public function test_file_is_served_inline_and_downloadable(): void
    {
        $this->task('Tugas Akhir.pdf');

        $inline = $this->actingAs($this->ctx['admin'])
            ->get($this->fileUrl() . '/file')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('inline', $inline->headers->get('Content-Disposition'));

        $download = $this->actingAs($this->ctx['admin'])
            ->get($this->fileUrl() . '/unduh')
            ->assertOk();
        $this->assertStringStartsWith('attachment', $download->headers->get('Content-Disposition'));
        $this->assertStringContainsString('Tugas Akhir.pdf', $download->headers->get('Content-Disposition'));
    }

    public function test_missing_task_returns_404(): void
    {
        $this->actingAs($this->ctx['admin'])->get($this->fileUrl())->assertNotFound();
        $this->actingAs($this->ctx['admin'])->get($this->fileUrl() . '/unduh')->assertNotFound();
    }

    public function test_non_admin_cannot_open_task(): void
    {
        $this->task('tugas.pdf');
        $asesor = $this->user(UserRole::Asesor);

        $this->actingAs($asesor)->get($this->fileUrl())->assertRedirect('/login');
        $this->actingAs($asesor)->get($this->fileUrl() . '/unduh')->assertRedirect('/login');
    }
}
