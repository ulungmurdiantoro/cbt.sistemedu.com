<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Jobs\StampFrAk01Job;
use App\Models\AssessmentApplication;
use App\Models\ExamSession;
use App\Models\Participant;
use App\Models\User;
use App\Models\UserRoleAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Feature\Concerns\CreatesExamFixtures;
use Tests\TestCase;

class PenilaianDokumenMateraiTest extends TestCase
{
    use RefreshDatabase, CreatesExamFixtures;

    private ExamSession $session;

    protected function setUp(): void
    {
        parent::setUp();

        config(['materai.enabled' => true]);
        Queue::fake();

        $classroom     = $this->makeClassroom();
        $exam          = $this->makeExam($classroom);
        $this->session = $this->makeSession($exam);
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

    /** Peserta di sesi ini beserta permohonannya. */
    private function applicant(bool $ttdLengkap = true, string $materai = 'none'): AssessmentApplication
    {
        $exam    = $this->session->referenceExam;
        $student = $this->makeStudent($exam->classroom);
        $this->enroll($student, $exam, $this->session);

        $participant = Participant::forceCreate([
            'name'     => $student->name,
            'email'    => Str::random(8) . '@example.com',
            'password' => bcrypt('password'),
        ]);

        return AssessmentApplication::forceCreate([
            'code'                  => 'APL-' . Str::random(8),
            'participant_id'        => $participant->id,
            'classroom_id'          => $exam->classroom_id,
            'exam_session_id'       => $this->session->id,
            'student_id'            => $student->id,
            'kode_batch'            => '-',
            'tujuan_asesmen'        => 'Sertifikasi',
            'status'                => 'approved',
            'signature_path'        => 'ttd/asesi.png',
            'admin_signature_path'  => 'ttd/lsp.png',
            'asesor_signature_path' => $ttdLengkap ? 'ttd/asesor.png' : null,
            'materai_status'        => $materai,
        ]);
    }

    public function test_index_shows_materai_status_per_participant(): void
    {
        $app = $this->applicant(materai: 'failed');

        $this->actingAs($this->admin())
            ->get("/admin/penilaian/{$this->session->id}/dokumen")
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Penilaian/Dokumen/Index')
                ->where('rows.0.app_id', $app->id)
                ->where('rows.0.ttd_lengkap', true)
                ->where('rows.0.materai_status', 'failed')
            );
    }

    public function test_bulk_stamps_only_applications_ready_for_materai(): void
    {
        $ready   = $this->applicant();
        $failed  = $this->applicant(materai: 'failed');
        $noTtd   = $this->applicant(ttdLengkap: false);
        $stamped = $this->applicant(materai: 'stamped');
        $running = $this->applicant(materai: 'pending_payment');

        $this->actingAs($this->admin())
            ->post("/admin/penilaian/{$this->session->id}/dokumen-materai")
            ->assertSessionHas('success');

        Queue::assertPushed(StampFrAk01Job::class, 2);
        $this->assertSame('pending_payment', $ready->fresh()->materai_status);
        $this->assertSame('pending_payment', $failed->fresh()->materai_status);
        $this->assertSame('none', $noTtd->fresh()->materai_status);
        $this->assertSame('stamped', $stamped->fresh()->materai_status);
        $this->assertSame('pending_payment', $running->fresh()->materai_status);
    }

    public function test_clicking_bulk_twice_does_not_stamp_twice(): void
    {
        $this->applicant();
        $admin = $this->admin();

        $this->actingAs($admin)->post("/admin/penilaian/{$this->session->id}/dokumen-materai");
        $this->actingAs($admin)->post("/admin/penilaian/{$this->session->id}/dokumen-materai")
            ->assertSessionHas('error');

        Queue::assertPushed(StampFrAk01Job::class, 1);
    }

    public function test_single_participant_can_be_stamped(): void
    {
        $app = $this->applicant();

        $this->actingAs($this->admin())
            ->post("/admin/penilaian/{$this->session->id}/dokumen/{$app->student_id}/materai")
            ->assertSessionHas('success');

        Queue::assertPushed(StampFrAk01Job::class, fn ($job) => $job->applicationId === $app->id);
        $this->assertSame('pending_payment', $app->fresh()->materai_status);
    }

    public function test_participant_from_another_session_is_rejected(): void
    {
        $other = $this->makeStudent($this->makeClassroom('OTH'));

        $this->actingAs($this->admin())
            ->post("/admin/penilaian/{$this->session->id}/dokumen/{$other->id}/materai")
            ->assertNotFound();
    }

    public function test_hidden_when_materai_is_disabled(): void
    {
        config(['materai.enabled' => false]);
        $this->applicant();

        $this->actingAs($this->admin())
            ->post("/admin/penilaian/{$this->session->id}/dokumen-materai")
            ->assertNotFound();

        Queue::assertNothingPushed();
    }
}
