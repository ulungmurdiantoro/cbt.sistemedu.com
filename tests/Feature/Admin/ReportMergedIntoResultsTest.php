<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\UserRoleAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Feature\Concerns\CreatesExamFixtures;
use Tests\TestCase;

/** Menu "Laporan Nilai" digabung ke Hasil Penilaian (admin/results). */
class ReportMergedIntoResultsTest extends TestCase
{
    use RefreshDatabase, CreatesExamFixtures;

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

    public function test_old_report_urls_redirect_to_results(): void
    {
        $session = $this->makeSession($this->makeExam($this->makeClassroom()));
        $admin   = $this->admin();

        $this->actingAs($admin)->get('/admin/reports')->assertRedirect('/admin/results');
        $this->actingAs($admin)->get("/admin/reports/filter?exam_session_id={$session->id}")
            ->assertRedirect("/admin/results/{$session->id}");
        $this->actingAs($admin)->get('/admin/reports/filter')->assertRedirect('/admin/results');
    }

    public function test_results_rows_link_to_answer_detail_per_exam(): void
    {
        $classroom = $this->makeClassroom();
        $pg        = $this->makeExam($classroom);
        $esai      = $this->makeExam($classroom, 'Essay');
        $session   = $this->makeSession($pg, $esai);
        $student   = $this->makeStudent($classroom);
        [, $gradePg]   = $this->enroll($student, $pg, $session);
        [, $gradeEsai] = $this->enroll($student, $esai, $session);

        $this->actingAs($this->admin())->get("/admin/results/{$session->id}")
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Results/Show')
                ->where('rows.0.student_id', $student->id)
                ->where('rows.0.grade_id_pg', $gradePg->id)
                ->where('rows.0.grade_id_esai', $gradeEsai->id)
            );

        // Detail jawaban (eks Laporan Nilai) tetap bisa dibuka
        $this->actingAs($this->admin())->get("/admin/reports/{$gradeEsai->id}")
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Reports/Show'));
    }
}
