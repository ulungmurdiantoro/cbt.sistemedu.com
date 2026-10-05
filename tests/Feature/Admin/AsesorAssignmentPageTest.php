<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\AsesorAssignment;
use App\Models\AssessmentApplication;
use App\Models\Participant;
use App\Models\User;
use App\Models\UserRoleAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Feature\Concerns\CreatesExamFixtures;
use Tests\TestCase;

/** Penugasan asesor kini tab "Peserta & Asesor" di detail sesi (admin.exam_sessions.show). */
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

    public function test_session_page_lists_asesors_and_saves_assignments(): void
    {
        $exam    = $this->makeExam($this->makeClassroom());
        $session = $this->makeSession($exam);
        $student = $this->makeStudent($exam->classroom);
        $this->enroll($student, $exam, $session);

        $admin = $this->user('Admin', UserRole::Admin);
        $budi  = $this->user('Budi Asesor', UserRole::Asesor);
        $ani   = $this->user('Ani Asesor', UserRole::Asesor);

        $this->actingAs($admin)->get("/admin/exam_sessions/{$session->id}")
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/ExamSessions/Show')
                ->has('asesors', 2)
                ->has('asesors.0', fn (Assert $a) => $a->where('id', $ani->id)->where('name', 'Ani Asesor'))
                ->where('asesors.1.id', $budi->id)
                ->where('students.0.id', $student->id)
                ->where('students.0.asesor_id', null)
            );

        $url = "/admin/penilaian/{$session->id}/penugasan";

        $this->actingAs($admin)->from("/admin/exam_sessions/{$session->id}")
            ->post($url, ['assignments' => [['student_id' => $student->id, 'user_id' => $budi->id]]])
            ->assertRedirect("/admin/exam_sessions/{$session->id}")
            ->assertSessionHasNoErrors();
        $this->assertSame($budi->id, AsesorAssignment::where('student_id', $student->id)->value('user_id'));

        $this->actingAs($admin)->get("/admin/exam_sessions/{$session->id}")
            ->assertInertia(fn (Assert $page) => $page->where('students.0.asesor_id', $budi->id));

        // Dikosongkan → penugasan dihapus.
        $this->actingAs($admin)->post($url, ['assignments' => [['student_id' => $student->id, 'user_id' => null]]]);
        $this->assertSame(0, AsesorAssignment::count());
    }

    public function test_session_page_hides_inactive_accounts_and_shows_application_status(): void
    {
        $exam    = $this->makeExam($this->makeClassroom());
        $session = $this->makeSession($exam);

        $active   = $this->makeStudent($exam->classroom);
        $inactive = $this->makeStudent($exam->classroom);
        $inactive->update(['is_active' => false]);
        $manual   = $this->makeStudent($exam->classroom);
        foreach ([$active, $inactive, $manual] as $s) {
            $this->enroll($s, $exam, $session);
        }

        $participant = Participant::forceCreate([
            'name'     => $active->name,
            'email'    => Str::random(8) . '@example.com',
            'password' => bcrypt('password'),
        ]);
        $app = AssessmentApplication::forceCreate([
            'code'            => 'APL-' . Str::random(8),
            'participant_id'  => $participant->id,
            'classroom_id'    => $exam->classroom_id,
            'exam_session_id' => $session->id,
            'student_id'      => $active->id,
            'kode_batch'      => '-',
            'tujuan_asesmen'  => 'Sertifikasi',
            'status'          => 'approved',
        ]);

        $this->actingAs($this->user('Admin', UserRole::Admin))->get("/admin/exam_sessions/{$session->id}")
            ->assertInertia(fn (Assert $page) => $page
                ->where('inactive_count', 1)
                ->where('students', function ($rows) use ($active, $manual, $app) {
                    $byId = collect($rows)->keyBy('id');

                    return $byId->keys()->sort()->values()->all() === collect([$active->id, $manual->id])->sort()->values()->all()
                        && $byId[$active->id]['application_id'] === $app->id
                        && $byId[$active->id]['application_status'] === 'approved'
                        && $byId[$manual->id]['application_id'] === null;
                })
            );
    }

    public function test_old_penugasan_urls_redirect_to_the_session_pages(): void
    {
        $session = $this->makeSession($this->makeExam($this->makeClassroom()));
        $admin   = $this->user('Admin', UserRole::Admin);

        $this->actingAs($admin)->get('/admin/penilaian')->assertRedirect('/admin/exam_sessions');
        $this->actingAs($admin)->get("/admin/penilaian/{$session->id}")->assertRedirect("/admin/exam_sessions/{$session->id}");
    }
}
