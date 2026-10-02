<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\AsesorAssignment;
use App\Models\User;
use App\Models\UserRoleAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Feature\Concerns\CreatesExamFixtures;
use Tests\TestCase;

class AsesorAssignmentPageTest extends TestCase
{
    use RefreshDatabase, CreatesExamFixtures;

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

    public function test_page_lists_only_asesors_with_id_and_name_and_saves_assignments(): void
    {
        $exam    = $this->makeExam($this->makeClassroom());
        $session = $this->makeSession($exam);
        $student = $this->makeStudent($exam->classroom);
        $this->enroll($student, $exam, $session);

        $admin = $this->user('Admin', UserRole::Admin);
        $budi  = $this->user('Budi Asesor', UserRole::Asesor);
        $ani   = $this->user('Ani Asesor', UserRole::Asesor);

        $this->actingAs($admin)->get("/admin/penilaian/{$session->id}")
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Penilaian/Show')
                ->has('asesors', 2)
                ->has('asesors.0', fn (Assert $a) => $a->where('id', $ani->id)->where('name', 'Ani Asesor'))
                ->where('asesors.1.id', $budi->id)
            );

        $url = "/admin/penilaian/{$session->id}/penugasan";

        $this->actingAs($admin)->post($url, ['assignments' => [['student_id' => $student->id, 'user_id' => $budi->id]]])
            ->assertSessionHasNoErrors();
        $this->assertSame($budi->id, AsesorAssignment::where('student_id', $student->id)->value('user_id'));

        // Dikosongkan → penugasan dihapus.
        $this->actingAs($admin)->post($url, ['assignments' => [['student_id' => $student->id, 'user_id' => null]]]);
        $this->assertSame(0, AsesorAssignment::count());
    }
}
