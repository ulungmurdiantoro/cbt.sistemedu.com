<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\ApplicationDocument;
use App\Models\AssessmentApplication;
use App\Models\ClassroomDocumentRequirement;
use App\Models\Participant;
use App\Models\User;
use App\Models\UserRoleAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Feature\Concerns\CreatesExamFixtures;
use Tests\TestCase;

/** Admin mengganti dokumen persyaratan peserta yang keliru, juga setelah permohonan disetujui. */
class AdminReplaceDocumentTest extends TestCase
{
    use RefreshDatabase, CreatesExamFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
        config(['lsp.admin_replace_documents' => true]);
    }

    private function user(UserRole $role): User
    {
        $user = User::forceCreate([
            'users_code' => 'U-' . Str::random(6), 'name' => 'User ' . $role->value,
            'email' => Str::random(8) . '@example.com', 'password' => bcrypt('password'),
        ]);
        UserRoleAssignment::forceCreate(['user_id' => $user->id, 'role' => $role->value]);

        return $user;
    }

    private function application(string $status = 'approved'): AssessmentApplication
    {
        $classroom = $this->makeClassroom('C' . Str::random(6));

        return AssessmentApplication::forceCreate([
            'exam_session_id' => $this->makeSession($this->makeExam($classroom))->id,
            'code'           => 'APL-' . Str::random(8),
            'participant_id' => Participant::forceCreate([
                'name' => 'Pemohon', 'email' => Str::random(8) . '@example.com', 'password' => bcrypt('password'),
            ])->id,
            'classroom_id'   => $classroom->id,
            'kode_batch'     => '-',
            'tujuan_asesmen' => 'Sertifikasi',
            'status'         => $status,
        ]);
    }

    private function requirement(AssessmentApplication $app, string $code = 'KTP'): ClassroomDocumentRequirement
    {
        return ClassroomDocumentRequirement::forceCreate([
            'classroom_id' => $app->classroom_id, 'code' => $code, 'label' => "Dokumen {$code}", 'order' => 1, 'is_required' => true,
        ]);
    }

    private function pdf(string $name = 'benar.pdf'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, "%PDF-1.4\n%benar\n");
    }

    public function test_admin_replaces_participant_document_and_it_becomes_verified(): void
    {
        $app = $this->application('approved');
        $req = $this->requirement($app);
        Storage::disk('private')->put("applications/{$app->id}/salah.pdf", '%PDF-1.4 salah');
        $old = ApplicationDocument::forceCreate([
            'assessment_application_id' => $app->id, 'classroom_document_requirement_id' => $req->id,
            'file_path' => "applications/{$app->id}/salah.pdf", 'original_filename' => 'salah.pdf',
            'mime_type' => 'application/pdf', 'status' => 'verified',
        ]);

        $this->actingAs($this->user(UserRole::Admin))
            ->post("/admin/applications/{$app->id}/requirements/{$req->id}/replace", ['file' => $this->pdf()])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $docs = ApplicationDocument::where('assessment_application_id', $app->id)->get();
        $this->assertCount(1, $docs);
        $this->assertSame('benar.pdf', $docs[0]->original_filename);
        $this->assertSame('verified', $docs[0]->status);
        Storage::disk('private')->assertMissing($old->file_path);
        Storage::disk('private')->assertExists($docs[0]->file_path);
    }

    public function test_admin_can_upload_missing_document_and_bad_files_are_rejected(): void
    {
        $app = $this->application('submitted');
        $req = $this->requirement($app);
        $admin = $this->user(UserRole::Admin);

        $this->actingAs($admin)
            ->post("/admin/applications/{$app->id}/requirements/{$req->id}/replace", [
                'file' => UploadedFile::fake()->createWithContent('palsu.pdf', 'bukan pdf'),
            ])
            ->assertSessionHasErrors('file');
        $this->assertSame(0, ApplicationDocument::count());

        $this->actingAs($admin)
            ->post("/admin/applications/{$app->id}/requirements/{$req->id}/replace", ['file' => $this->pdf()])
            ->assertSessionHasNoErrors();
        $this->assertSame(1, ApplicationDocument::count());

        // Persyaratan skema lain tidak bisa dipakai
        $other = $this->requirement($this->application(), 'CV');
        $this->actingAs($admin)
            ->post("/admin/applications/{$app->id}/requirements/{$other->id}/replace", ['file' => $this->pdf()])
            ->assertNotFound();
    }

    public function test_switched_off_hides_button_and_rejects_upload(): void
    {
        config(['lsp.admin_replace_documents' => false]);
        $app   = $this->application('submitted');
        $req   = $this->requirement($app);
        $admin = $this->user(UserRole::Admin);

        $this->actingAs($admin)->get("/admin/applications/{$app->id}")
            ->assertInertia(fn (\Inertia\Testing\AssertableInertia $page) => $page->where('can_replace_documents', false));

        $this->actingAs($admin)
            ->post("/admin/applications/{$app->id}/requirements/{$req->id}/replace", ['file' => $this->pdf()])
            ->assertForbidden();
        $this->assertSame(0, ApplicationDocument::count());
    }

    public function test_only_admin_can_replace_documents(): void
    {
        $app = $this->application();
        $req = $this->requirement($app);

        $this->actingAs($this->user(UserRole::Asesor))
            ->post("/admin/applications/{$app->id}/requirements/{$req->id}/replace", ['file' => $this->pdf()])
            ->assertRedirect();   // middleware admin mengalihkan non-admin ke dashboard-nya
        $this->assertSame(0, ApplicationDocument::count());
    }
}
