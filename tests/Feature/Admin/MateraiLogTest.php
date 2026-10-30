<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\ParticipantResult;
use App\Models\User;
use App\Models\UserRoleAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;
use Tests\Feature\Concerns\CreatesExamFixtures;
use Tests\TestCase;

/** Log Materai admin (admin/materai): riwayat pembubuhan FR.AK.14 & FR.AK.01. */
class MateraiLogTest extends TestCase
{
    use RefreshDatabase;
    use CreatesExamFixtures;

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

    private function participantResult($session, $student, array $attrs): ParticipantResult
    {
        return ParticipantResult::forceCreate($attrs + [
            'exam_session_id' => $session->id,
            'student_id'      => $student->id,
            'keputusan'       => 'LULUS',
            'is_finalized'    => true,
        ]);
    }

    public function test_lists_stamping_newest_first_with_summary_and_filters(): void
    {
        $classroom = $this->makeClassroom();
        $exam      = $this->makeExam($classroom);
        $session   = $this->makeSession($exam);
        $a = $this->makeStudent($classroom);
        $b = $this->makeStudent($classroom);
        $c = $this->makeStudent($classroom);
        $d = $this->makeStudent($classroom);

        $this->participantResult($session, $a, ['materai_status' => 'stamped', 'materai_stamped_at' => now()->subDay()]);
        $this->participantResult($session, $b, ['materai_status' => 'stamped', 'materai_stamped_at' => now()]);
        $this->participantResult($session, $c, ['materai_status' => 'failed', 'materai_failure_reason' => 'Saldo Tidak Cukup', 'updated_at' => now()->addMinute()]);
        $this->participantResult($session, $d, ['materai_status' => 'none']); // belum diproses → tidak tampil

        $admin = $this->admin();

        $this->actingAs($admin)->get('/admin/materai')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Materai/Index')
                ->where('rows.total', 3)
                ->where('summary.stamped', 2)
                ->where('summary.today', 1)
                ->where('summary.failed', 1)
                ->where('summary.proses', 0)
                ->where('rows.data.0.status', 'failed') // updated_at terbaru → paling atas
            );

        $this->actingAs($admin)->get('/admin/materai?status=stamped')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('rows.total', 2)
                ->where('rows.data.0.nama', $b->name)
                ->where('rows.data.1.nama', $a->name)
            );

        $this->actingAs($admin)->get('/admin/materai?dokumen=FR.AK.01')
            ->assertInertia(fn (AssertableInertia $page) => $page->where('rows.total', 0));
    }

    public function test_saldo_is_read_from_peruri_and_cached(): void
    {
        config([
            'materai.enabled'         => true,
            'materai.peruri.fake'     => false,
            'materai.peruri.login_url' => 'https://backendservicestg.example.test/api/users/login',
        ]);
        Http::fake([
            '*/api/users/login'  => Http::response(['statusCode' => '00', 'token' => 'tok']),
            '*/function/saldopos' => Http::response(['result' => ['status' => '00', 'saldo' => 125, 'notstamp' => 3]]),
        ]);

        $admin = $this->admin();

        $this->actingAs($admin)->getJson('/admin/materai/saldo')
            ->assertOk()->assertJson(['saldo' => 125, 'notstamp' => 3]);
        $this->actingAs($admin)->getJson('/admin/materai/saldo')->assertJson(['saldo' => 125]);
        Http::assertSentCount(2); // login + saldo sekali; panggilan kedua dari cache

        $this->actingAs($admin)->getJson('/admin/materai/saldo?refresh=1')->assertJson(['saldo' => 125]);
        Http::assertSentCount(3);
    }

    public function test_saldo_reports_error_instead_of_failing(): void
    {
        config(['materai.enabled' => true, 'materai.peruri.fake' => true]);

        $this->actingAs($this->admin())->getJson('/admin/materai/saldo')
            ->assertOk()->assertJsonStructure(['error']);
    }
}
