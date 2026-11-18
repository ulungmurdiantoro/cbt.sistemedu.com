<?php

namespace Tests\Feature\Peserta;

use App\Models\ApplicationDocument;
use App\Models\AssessmentApplication;
use App\Models\ClassroomDocumentRequirement;
use App\Models\Participant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Feature\Concerns\CreatesExamFixtures;
use Tests\TestCase;

/** Setelah submit, peserta masih bisa mengganti dokumen yang belum diverifikasi admin. */
class ReplaceDocumentTest extends TestCase
{
    use RefreshDatabase, CreatesExamFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('private');
    }

    private function application(string $status): AssessmentApplication
    {
        $classroom = $this->makeClassroom('C' . Str::random(6));
        $participant = Participant::forceCreate([
            'name' => 'Pemohon', 'email' => Str::random(8) . '@example.com', 'password' => bcrypt('password'),
        ]);

        return AssessmentApplication::forceCreate([
            'exam_session_id' => $this->makeSession($this->makeExam($classroom))->id,
            'code'           => 'APL-' . Str::random(8),
            'participant_id' => $participant->id,
            'classroom_id'   => $classroom->id,
            'kode_batch'     => '-',
            'tujuan_asesmen' => 'Sertifikasi',
            'status'         => $status,
        ]);
    }

    private function requirement(AssessmentApplication $app, string $code): ClassroomDocumentRequirement
    {
        return ClassroomDocumentRequirement::forceCreate([
            'classroom_id' => $app->classroom_id, 'code' => $code, 'label' => "Dokumen {$code}", 'order' => 1, 'is_required' => true,
        ]);
    }

    private function document(AssessmentApplication $app, ClassroomDocumentRequirement $req, string $status): ApplicationDocument
    {
        $path = "applications/{$app->id}/{$req->code}_lama.pdf";
        Storage::disk('private')->put($path, '%PDF-1.4 lama');

        return ApplicationDocument::forceCreate([
            'assessment_application_id' => $app->id, 'classroom_document_requirement_id' => $req->id,
            'file_path' => $path, 'original_filename' => 'lama.pdf', 'mime_type' => 'application/pdf', 'status' => $status,
        ]);
    }

    private function upload(AssessmentApplication $app, ClassroomDocumentRequirement $req)
    {
        $this->actingAs($app->participant, 'participant');
        $this->app['auth']->shouldUse('web');

        return $this->post("/peserta/aplikasi/{$app->id}/dokumen", [
            'requirement_id' => $req->id,
            'file'           => UploadedFile::fake()->createWithContent('baru.pdf', "%PDF-1.4\n%baru\n"),
        ]);
    }

    public function test_submitted_application_can_replace_pending_document(): void
    {
        $app     = $this->application('submitted');
        $req     = $this->requirement($app, 'KTP');
        $old     = $this->document($app, $req, 'pending');

        $this->upload($app, $req)->assertSessionHasNoErrors()->assertSessionHas('success');

        $docs = ApplicationDocument::where('assessment_application_id', $app->id)->get();
        $this->assertCount(1, $docs);
        $this->assertSame('baru.pdf', $docs[0]->original_filename);
        $this->assertSame('pending', $docs[0]->status);
        Storage::disk('private')->assertMissing($old->file_path);
    }

    public function test_verified_document_and_decided_application_stay_locked(): void
    {
        $app      = $this->application('submitted');
        $verified = $this->requirement($app, 'IJAZAH');
        $pending  = $this->requirement($app, 'CV');
        $missing  = $this->requirement($app, 'FOTO');
        $this->document($app, $verified, 'verified');
        $this->document($app, $pending, 'pending');

        $this->upload($app, $verified)->assertForbidden();

        $this->get("/peserta/aplikasi/{$app->id}/dokumen")->assertInertia(fn (Assert $page) => $page
            ->where('requirements', fn ($reqs) => collect($reqs)->pluck('can_upload', 'code')->all() === [
                'IJAZAH' => false, 'CV' => true, 'FOTO' => true,
            ]));

        // Permohonan yang sudah disetujui: dokumen Menunggu pun tidak bisa diganti lagi.
        $approved = $this->application('approved');
        $req = $this->requirement($approved, 'KTP');
        $this->document($approved, $req, 'pending');
        $this->upload($approved, $req)->assertForbidden();
    }
}
