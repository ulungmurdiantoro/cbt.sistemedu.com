<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\ExamSession;
use App\Models\ParticipantResult;
use App\Models\Student;
use App\Models\User;
use App\Models\UserRoleAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;
use Tests\Feature\Concerns\CreatesExamFixtures;
use Tests\TestCase;

/**
 * Alur kelulusan dua tahap: Pengambil Keputusan (ditunjuk admin per sesi) mencentang Verifikasi,
 * lalu Manager Sertifikasi mencentang Verifikasi & memfinalisasi.
 */
class SertifikasiTwoStageVerificationTest extends TestCase
{
    use RefreshDatabase;
    use CreatesExamFixtures;

    private ExamSession $session;
    /** @var Student[] */
    private array $students;
    private User $pk;
    private User $certManager;

    protected function setUp(): void
    {
        parent::setUp();

        $classroom = $this->makeClassroom();
        $exam      = $this->makeExam($classroom);
        $this->session = $this->makeSession($exam);

        foreach (['NP-001', 'NP-002', 'NP-003'] as $no) {
            $student = $this->makeStudent($classroom);
            $student->forceFill(['no_participant' => $no])->save();
            $this->enroll($student, $exam, $this->session);
            $this->students[] = $student;
        }

        $this->pk          = $this->user('Ibu Pengambil Keputusan', UserRole::ManagerSertifikasi);
        $this->certManager = $this->user('Pak Manager Sertifikasi', UserRole::CertificationManager);
        $this->session->update(['pengambil_keputusan_id' => $this->pk->id]);
    }

    private function user(string $name, UserRole $role): User
    {
        $user = User::forceCreate([
            'users_code' => 'U-' . Str::random(6),
            'name'       => $name,
            'email'      => Str::random(8) . '@example.com',
            'password'   => bcrypt('password'),
        ]);
        UserRoleAssignment::forceCreate(['user_id' => $user->id, 'role' => $role->value]);

        return $user;
    }

    private function participantResult(Student $student, array $attributes = []): ParticipantResult
    {
        return ParticipantResult::forceCreate($attributes + [
            'exam_session_id' => $this->session->id,
            'student_id'      => $student->id,
            'nilai_akhir'     => 80,
            'keputusan'       => 'LULUS',
        ]);
    }

    public function test_pk_dashboard_only_lists_appointed_sessions(): void
    {
        $other = $this->makeSession($this->makeExam($this->makeClassroom('OTH')));

        $this->actingAs($this->pk)
            ->get(route('manager.dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Manager/Dashboard')
                ->where('active_sessions', fn ($sessions) => collect($sessions)->pluck('id')->all() === [$this->session->id]));

        $this->actingAs($this->certManager)
            ->get(route('cert_manager.dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('CertificationManager/Dashboard')
                ->where('active_sessions', fn ($sessions) => collect($sessions)->pluck('id')->sort()->values()->all() === [$this->session->id, $other->id]));
    }

    public function test_pk_cannot_open_session_not_appointed_to_them(): void
    {
        $this->participantResult($this->students[0]);
        $otherPk = $this->user('PK Lain', UserRole::ManagerSertifikasi);

        $this->actingAs($otherPk)->get("/manager/sertifikasi/{$this->session->id}")->assertForbidden();
        $this->actingAs($otherPk)->post("/manager/sertifikasi/{$this->session->id}/verifikasi/{$this->students[0]->id}")->assertForbidden();
        $this->actingAs($otherPk)->get("/manager/dokumen/{$this->session->id}/{$this->students[0]->id}/data")->assertForbidden();
        $this->actingAs($otherPk)->get("/dokumen/laporan-asesmen/{$this->session->id}/{$this->pk->id}/download")->assertForbidden();

        $this->actingAs($this->pk)
            ->get("/manager/sertifikasi/{$this->session->id}")
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Manager/Sertifikasi/Show')
                ->where('pengambil_keputusan.name', 'Ibu Pengambil Keputusan'));
    }

    public function test_pk_can_no_longer_finalize(): void
    {
        $this->participantResult($this->students[0], ['manager_verified_at' => now(), 'cert_manager_verified_at' => now()]);

        $this->actingAs($this->pk)->post("/manager/sertifikasi/{$this->session->id}/finalize");

        $this->assertFalse(ParticipantResult::first()->is_finalized);
    }

    public function test_cert_manager_verifies_only_after_pk(): void
    {
        $result = $this->participantResult($this->students[0]);
        $url    = "/manager-sertifikasi/sertifikasi/{$this->session->id}/verifikasi/{$this->students[0]->id}";

        $this->actingAs($this->certManager)->post($url)->assertStatus(422);
        $this->assertNull($result->fresh()->cert_manager_verified_at);

        $this->actingAs($this->pk)->post("/manager/sertifikasi/{$this->session->id}/verifikasi/{$this->students[0]->id}")->assertRedirect();
        $this->actingAs($this->certManager)->post($url)->assertRedirect();

        $result->refresh();
        $this->assertSame($this->pk->id, $result->manager_verified_by);
        $this->assertSame($this->certManager->id, $result->cert_manager_verified_by);
        $this->assertNotNull($result->cert_manager_verified_at);
    }

    public function test_pk_cannot_unverify_after_cert_manager_verified(): void
    {
        $result = $this->participantResult($this->students[0], [
            'manager_verified_at'      => now(),
            'manager_verified_by'      => $this->pk->id,
            'cert_manager_verified_at' => now(),
        ]);

        $this->actingAs($this->pk)
            ->post("/manager/sertifikasi/{$this->session->id}/verifikasi/{$this->students[0]->id}")
            ->assertStatus(422);

        $this->assertNotNull($result->fresh()->manager_verified_at);
    }

    public function test_finalize_only_participants_verified_by_both(): void
    {
        [$both, $pkOnly, $none] = $this->students;
        $this->participantResult($both, ['manager_verified_at' => now(), 'manager_verified_by' => $this->pk->id, 'cert_manager_verified_at' => now()]);
        $this->participantResult($pkOnly, ['manager_verified_at' => now(), 'manager_verified_by' => $this->pk->id]);
        $this->participantResult($none);

        $this->actingAs($this->certManager)
            ->post("/manager-sertifikasi/sertifikasi/{$this->session->id}/finalize")
            ->assertRedirect();

        $final = ParticipantResult::where('student_id', $both->id)->first();
        $this->assertTrue($final->is_finalized);
        $this->assertSame($this->certManager->id, $final->finalized_by);
        $this->assertNotNull($final->sk_number);
        $this->assertFalse(ParticipantResult::where('student_id', $pkOnly->id)->value('is_finalized'));
        $this->assertFalse(ParticipantResult::where('student_id', $none->id)->value('is_finalized'));

        // Keputusan Sertifikasi atas nama Pengambil Keputusan sesi, bukan Manager Sertifikasi.
        $this->session->refresh();
        $this->assertNotNull($this->session->keputusan_number);
        $this->assertSame($this->pk->id, $this->session->keputusan_issued_by);
    }

    public function test_finalize_without_doubly_verified_participants_is_rejected(): void
    {
        $this->participantResult($this->students[0], ['manager_verified_at' => now()]);

        $this->actingAs($this->certManager)
            ->post("/manager-sertifikasi/sertifikasi/{$this->session->id}/finalize")
            ->assertStatus(422);

        $this->assertNull($this->session->fresh()->keputusan_number);
    }

    public function test_admin_appoints_pengambil_keputusan_per_session(): void
    {
        $admin = $this->user('Admin', UserRole::Admin);
        $newPk = $this->user('PK Baru', UserRole::ManagerSertifikasi);
        $url   = "/admin/exam_sessions/{$this->session->id}/pengambil-keputusan";

        $this->actingAs($admin)->post($url, ['pengambil_keputusan_id' => $newPk->id])->assertRedirect();
        $this->assertSame($newPk->id, $this->session->fresh()->pengambil_keputusan_id);

        // Hanya user ber-role Pengambil Keputusan yang bisa ditunjuk.
        $this->actingAs($admin)->post($url, ['pengambil_keputusan_id' => $this->certManager->id])
            ->assertSessionHasErrors('pengambil_keputusan_id');
        $this->assertSame($newPk->id, $this->session->fresh()->pengambil_keputusan_id);

        $this->actingAs($admin)->post($url, ['pengambil_keputusan_id' => null])->assertRedirect();
        $this->assertNull($this->session->fresh()->pengambil_keputusan_id);
    }

    public function test_admin_session_detail_offers_pengambil_keputusan_options(): void
    {
        $this->actingAs($this->user('Admin', UserRole::Admin))
            ->get(route('admin.exam_sessions.show', $this->session->id))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/ExamSessions/Show')
                ->where('exam_session.pengambil_keputusan_id', $this->pk->id)
                ->where('pengambil_keputusan_options', fn ($users) => collect($users)->pluck('id')->all() === [$this->pk->id]));
    }
}
