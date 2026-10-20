<?php

namespace Tests\Feature\Peserta;

use App\Jobs\StampFrAk14Job;
use App\Models\ExamSession;
use App\Models\Participant;
use App\Models\ParticipantResult;
use App\Services\DocumentGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\Feature\Concerns\CreatesExamFixtures;
use Tests\TestCase;

/**
 * Materai FR.AK.14 berlaku mulai sesi tertentu (MATERAI_ENABLED=true + MATERAI_FIRST_SESSION_ID):
 * peserta LULUS sesi sebelumnya cukup TTD untuk mengunduh SK & Sertifikat, sesi itu dan sesudahnya
 * harus sudah bermaterai.
 */
class FrAk14MateraiFromSessionTest extends TestCase
{
    use RefreshDatabase, CreatesExamFixtures;

    private Participant $participant;
    private ExamSession $oldSession;
    private ExamSession $newSession;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');

        $exam             = $this->makeExam($this->makeClassroom());
        $this->oldSession = $this->makeSession($exam);
        $this->newSession = $this->makeSession($exam);

        config(['materai.enabled' => true, 'materai.first_session_id' => $this->newSession->id]);

        $this->participant = Participant::forceCreate([
            'name'     => 'Asesi Test',
            'email'    => 'asesi@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->actingAs($this->participant, 'participant');
        $this->app['auth']->shouldUse('web');

        // PDF-nya sendiri tidak diuji di sini — hanya gerbang unduhnya.
        $this->mock(DocumentGeneratorService::class, function ($mock) {
            $mock->shouldReceive('skPdf')->andReturn('%PDF-SK');
            $mock->shouldReceive('sertifikatPdf')->andReturn('%PDF-SERT');
        });
    }

    /** Peserta LULUS, SK & Sertifikat sudah dikirim admin. */
    private function passedResult(ExamSession $session, array $attrs = []): ParticipantResult
    {
        $student = $this->makeStudent($this->makeClassroom('C' . $session->id));
        $student->forceFill(['participant_id' => $this->participant->id])->save();

        return ParticipantResult::forceCreate($attrs + [
            'exam_session_id'   => $session->id,
            'student_id'        => $student->id,
            'keputusan'         => 'LULUS',
            'is_finalized'      => true,
            'sk_number'         => '001/SK-SP/LSP-EDUKIA/X/2026',
            'sertifikat_number' => '003-010-10-2026-' . str_pad((string) $session->id, 5, '0', STR_PAD_LEFT),
            'distributed_at'    => now(),
        ]);
    }

    private function download(ParticipantResult $r, string $doc)
    {
        return $this->get("/peserta/hasil/{$r->exam_session_id}/{$r->student_id}/{$doc}");
    }

    public function test_session_before_first_needs_signature_only(): void
    {
        $result = $this->passedResult($this->oldSession, ['fr_ak_14_signed_at' => now()]);

        $this->download($result, 'sk')->assertOk();
        $this->download($result, 'sertifikat')->assertOk();
    }

    public function test_from_first_session_signed_but_not_stamped_is_locked(): void
    {
        // Mis. sudah TTD waktu materai masih dimatikan.
        $result = $this->passedResult($this->newSession, ['fr_ak_14_signed_at' => now(), 'materai_status' => 'none']);

        $this->download($result, 'sk')->assertStatus(422);
        $this->download($result, 'sertifikat')->assertStatus(422);
    }

    public function test_from_first_session_stamped_can_download(): void
    {
        $result = $this->passedResult($this->newSession, ['fr_ak_14_signed_at' => now(), 'materai_status' => 'stamped']);

        $this->download($result, 'sk')->assertOk();
        $this->download($result, 'sertifikat')->assertOk();
    }

    #[RequiresPhpExtension('gd')] // SignatureImageProcessor
    public function test_signing_queues_materai_only_from_first_session(): void
    {
        Queue::fake();
        // PNG 1×1 dari kanvas tanda tangan
        $signature = ['signature_data' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='];

        $old = $this->passedResult($this->oldSession);
        $this->post("/peserta/hasil/{$old->exam_session_id}/{$old->student_id}/fr-ak-14", $signature);
        Queue::assertNothingPushed();
        $this->assertSame('none', $old->fresh()->materai_status);

        $new = $this->passedResult($this->newSession);
        $this->post("/peserta/hasil/{$new->exam_session_id}/{$new->student_id}/fr-ak-14", $signature);
        Queue::assertPushed(StampFrAk14Job::class, fn ($job) => $job->participantResultId === $new->id);
        $this->assertSame('processing', $new->fresh()->materai_status);
    }
}
