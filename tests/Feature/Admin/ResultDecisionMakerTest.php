<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\AsesorAssignment;
use App\Models\ParticipantResult;
use App\Models\User;
use App\Models\UserRoleAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;
use Tests\Feature\Concerns\CreatesExamFixtures;
use Tests\TestCase;

/** Rekap Hasil admin (admin/results/{sesi}) menampilkan Pengambil Keputusan yang memfinalisasi peserta. */
class ResultDecisionMakerTest extends TestCase
{
    use RefreshDatabase;
    use CreatesExamFixtures;

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

    public function test_finalizer_name_is_shown_per_participant(): void
    {
        $classroom = $this->makeClassroom();
        $exam      = $this->makeExam($classroom);
        $session   = $this->makeSession($exam);
        $final     = $this->makeStudent($classroom);
        $draft     = $this->makeStudent($classroom);
        $this->enroll($final, $exam, $session);
        $this->enroll($draft, $exam, $session);

        $manager = $this->user('Ibu Pengambil Keputusan', UserRole::ManagerSertifikasi);

        ParticipantResult::forceCreate([
            'exam_session_id' => $session->id,
            'student_id'      => $final->id,
            'nilai_akhir'     => 85,
            'keputusan'       => 'LULUS',
            'is_finalized'    => true,
            'finalized_at'    => now(),
            'finalized_by'    => $manager->id,
        ]);

        $this->actingAs($this->user('Admin Test', UserRole::Admin))
            ->get("/admin/results/{$session->id}")
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Results/Show')
                ->where('rows', fn ($rows) => collect($rows)->firstWhere('student_id', $final->id)['finalized_by_name'] === 'Ibu Pengambil Keputusan'
                    && collect($rows)->firstWhere('student_id', $draft->id)['finalized_by_name'] === null));
    }

    public function test_assigned_asesor_name_is_shown_per_participant(): void
    {
        $classroom  = $this->makeClassroom();
        $exam       = $this->makeExam($classroom);
        $session    = $this->makeSession($exam);
        $assigned   = $this->makeStudent($classroom);
        $unassigned = $this->makeStudent($classroom);
        $this->enroll($assigned, $exam, $session);
        $this->enroll($unassigned, $exam, $session);

        $asesor = $this->user('Pak Asesor', UserRole::Asesor);
        AsesorAssignment::create([
            'user_id'         => $asesor->id,
            'exam_session_id' => $session->id,
            'student_id'      => $assigned->id,
        ]);

        $this->actingAs($this->user('Admin Test', UserRole::Admin))
            ->get("/admin/results/{$session->id}")
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Results/Show')
                ->where('rows', fn ($rows) => collect($rows)->firstWhere('student_id', $assigned->id)['asesor_name'] === 'Pak Asesor'
                    && collect($rows)->firstWhere('student_id', $unassigned->id)['asesor_name'] === null));
    }
}
