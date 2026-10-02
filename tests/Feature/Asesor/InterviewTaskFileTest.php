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

/** Tugas peserta di halaman penilaian wawancara: halaman pratinjau di tab baru + unduh. */
class InterviewTaskFileTest extends TestCase
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
        return "/asesor/penilaian/{$this->ctx['session']->id}/wawancara/tugas/{$this->ctx['student']->id}";
    }

    public function test_preview_page_shows_task_info(): void
    {
        $this->task('Tugas Akhir.DOCX');

        $this->actingAs($this->ctx['asesor'])
            ->get($this->fileUrl())
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Asesor/Wawancara/Tugas')
                ->where('student.no_participant', $this->ctx['student']->no_participant)
                ->where('tugas.original_filename', 'Tugas Akhir.DOCX')
                ->where('tugas.type', 'docx')
                ->where('tugas.previewable', true));
    }

    public function test_file_is_served_inline_with_original_filename(): void
    {
        $this->task('Tugas Akhir.pdf');

        $response = $this->actingAs($this->ctx['asesor'])
            ->get($this->fileUrl() . '/file')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->assertStringStartsWith('inline', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('Tugas Akhir.pdf', $response->headers->get('Content-Disposition'));
    }

    public function test_download_sends_attachment_with_original_filename(): void
    {
        $this->task('Tugas Akhir.zip');

        $response = $this->actingAs($this->ctx['asesor'])
            ->get($this->fileUrl() . '/unduh')
            ->assertOk();

        $this->assertStringStartsWith('attachment', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('Tugas Akhir.zip', $response->headers->get('Content-Disposition'));
    }

    public function test_unassigned_asesor_cannot_open_or_download(): void
    {
        $this->task('tugas.pdf');
        $other = $this->asesor();

        $this->actingAs($other)->get($this->fileUrl())->assertForbidden();
        $this->actingAs($other)->get($this->fileUrl() . '/file')->assertForbidden();
        $this->actingAs($other)->get($this->fileUrl() . '/unduh')->assertForbidden();
    }

    public function test_format_that_cannot_be_previewed_only_offers_download(): void
    {
        $this->task('tugas.xlsx');

        $this->actingAs($this->ctx['asesor'])
            ->get($this->fileUrl())
            ->assertInertia(fn (AssertableInertia $page) => $page->where('tugas.previewable', false));

        $this->actingAs($this->ctx['asesor'])->get($this->fileUrl() . '/file')->assertStatus(415);
        $this->actingAs($this->ctx['asesor'])->get($this->fileUrl() . '/unduh')->assertOk();
    }

    public function test_interview_page_lists_tasks(): void
    {
        $this->task('Tugas Akhir.DOCX');

        $this->actingAs($this->ctx['asesor'])
            ->get("/asesor/penilaian/{$this->ctx['session']->id}/wawancara")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Asesor/Wawancara/Show')
                ->where("tugas.{$this->ctx['student']->id}", ['original_filename' => 'Tugas Akhir.DOCX']));
    }
}
