<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\ParticipantResult;
use App\Models\User;
use App\Models\UserRoleAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;
use Tests\Feature\Concerns\CreatesExamFixtures;
use Tests\TestCase;

/**
 * Jumlah peserta di daftar Hasil Penilaian (admin/results) & Dashboard Pengambil Keputusan harus
 * sama dengan baris di Rekap Hasil / Tinjau Sertifikasi: peserta terdaftar di sesi berakun aktif —
 * bukan jumlah baris participant_results (dibuat saat Rekap dibuka, tertinggal saat dipindah batch).
 */
class SessionParticipantCountTest extends TestCase
{
    use RefreshDatabase;
    use CreatesExamFixtures;

    private array $ctx;

    protected function setUp(): void
    {
        parent::setUp();

        $classroom = $this->makeClassroom();
        $exam      = $this->makeExam($classroom);
        $session   = $this->makeSession($exam);
        $other     = $this->makeSession($exam);

        // 2 peserta terdaftar, belum ada participant_results (Rekap belum pernah dibuka)
        $enrolled = [$this->makeStudent($classroom), $this->makeStudent($classroom)];
        foreach ($enrolled as $student) {
            $this->enroll($student, $exam, $session);
        }

        // Akun lama hasil re-issue: masih terdaftar tapi nonaktif
        $inactive = $this->makeStudent($classroom);
        $inactive->update(['is_active' => false]);
        $this->enroll($inactive, $exam, $session);

        // Peserta yang sudah dipindah ke batch lain: baris hasil di sesi ini tertinggal
        $moved = $this->makeStudent($classroom);
        $this->enroll($moved, $exam, $other);
        ParticipantResult::forceCreate([
            'exam_session_id'     => $session->id,
            'student_id'          => $moved->id,
            'manager_verified_at' => now(),
            'is_finalized'        => false,
        ]);

        $this->ctx = compact('session', 'enrolled');
    }

    private function staff(UserRole $role): User
    {
        $user = User::forceCreate([
            'users_code' => 'U-' . Str::random(6),
            'name'       => 'Staf Test',
            'email'      => Str::random(8) . '@example.com',
            'password'   => bcrypt('password'),
        ]);

        UserRoleAssignment::forceCreate(['user_id' => $user->id, 'role' => $role->value]);

        return $user;
    }

    public function test_results_index_counts_enrolled_active_participants(): void
    {
        // Urutan daftar memakai NOW() milik MySQL; test berjalan di SQLite.
        if (DB::getDriverName() === 'sqlite') {
            DB::connection()->getPdo()->sqliteCreateFunction('NOW', fn () => now()->toDateTimeString(), 0);
        }

        $this->actingAs($this->staff(UserRole::Admin))
            ->get(route('admin.results.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Results/Index')
                ->where('sessions.data.0.id', $this->ctx['session']->id)
                ->where('sessions.data.0.students_count', 2)
                ->where('sessions.data.0.reviewed_count', 0));
    }

    /** Pengambil Keputusan yang ditunjuk untuk sesi uji (dashboard PK hanya memuat sesi yang ditunjuk). */
    private function appointedPengambilKeputusan(): User
    {
        $pk = $this->staff(UserRole::ManagerSertifikasi);
        $this->ctx['session']->update(['pengambil_keputusan_id' => $pk->id]);

        return $pk;
    }

    public function test_manager_dashboard_counts_enrolled_active_participants(): void
    {
        $this->actingAs($this->appointedPengambilKeputusan())
            ->get(route('manager.dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Manager/Dashboard')
                ->where('active_sessions.0.id', $this->ctx['session']->id)
                ->where('active_sessions.0.students_count', 2)
                ->where('active_sessions.0.reviewed_count', 0));
    }

    public function test_count_matches_rows_on_rekap_hasil(): void
    {
        $this->actingAs($this->staff(UserRole::Admin))
            ->get(route('admin.results.show', $this->ctx['session']))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('rows', 2));
    }

    public function test_reviewed_count_follows_enrolled_participants(): void
    {
        ParticipantResult::forceCreate([
            'exam_session_id'     => $this->ctx['session']->id,
            'student_id'          => $this->ctx['enrolled'][0]->id,
            'manager_verified_at' => now(),
        ]);

        $this->actingAs($this->appointedPengambilKeputusan())
            ->get(route('manager.dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('active_sessions.0.students_count', 2)
                ->where('active_sessions.0.reviewed_count', 1));
    }

    public function test_leftover_result_of_moved_participant_does_not_block_sending_sp(): void
    {
        Queue::fake();

        foreach ($this->ctx['enrolled'] as $student) {
            ParticipantResult::forceCreate([
                'exam_session_id' => $this->ctx['session']->id,
                'student_id'      => $student->id,
                'is_finalized'    => true,
            ]);
        }

        $this->actingAs($this->staff(UserRole::Admin))
            ->post(route('admin.results.distribute-sp', $this->ctx['session']))
            ->assertSessionHasNoErrors();
    }
}
