<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\AnswerEssay;
use App\Models\Essay;
use App\Models\AssessmentApplication;
use App\Models\AsesorAssignment;
use App\Models\ExamSession;
use App\Models\Grade;
use App\Models\Participant;
use App\Models\ParticipantResult;
use App\Models\User;
use App\Models\UserRoleAssignment;
use App\Services\AdminDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;
use Tests\Feature\Concerns\CreatesExamFixtures;
use Tests\TestCase;

/** Dashboard admin: perlu tindakan, sesi aktif, pipeline sertifikasi, statistik. */
class DashboardTest extends TestCase
{
    use RefreshDatabase;
    use CreatesExamFixtures;

    private function user(UserRole $role): User
    {
        $user = User::forceCreate([
            'users_code' => 'U-' . Str::random(6),
            'name'       => 'User ' . Str::random(4),
            'email'      => Str::random(8) . '@example.com',
            'password'   => bcrypt('password'),
        ]);
        UserRoleAssignment::forceCreate(['user_id' => $user->id, 'role' => $role->value]);

        return $user;
    }

    public function test_empty_dashboard_renders(): void
    {
        $this->actingAs($this->user(UserRole::Admin))
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Dashboard/Index')
                ->where('actions.applications', 0)
                ->where('actions.send', 0)
                ->has('activeSessions', 0)
                ->has('pipeline', 0)
                ->has('monthly', 12)
                ->where('totals.exam_sessions', 0));
    }

    public function test_pipeline_and_action_counts_for_ended_session(): void
    {
        $classroom = $this->makeClassroom();
        $pg        = $this->makeExam($classroom);
        $esai      = $this->makeExam($classroom, 'Essay');
        $session   = $this->makeSession($pg, $esai);
        // Pipeline hanya melacak sesi baru; sesi sudah berakhir.
        $session->forceFill([
            'id'         => AdminDashboardService::TRACK_FROM_SESSION_ID + 1,
            'start_time' => now()->subDays(2),
            'end_time'   => now()->subDay(),
        ])->save();

        $asesor = $this->user(UserRole::Asesor);
        $pk     = $this->user(UserRole::ManagerSertifikasi);
        $session->update(['pengambil_keputusan_id' => $pk->id]);

        [$a, $b, $c] = [$this->makeStudent($classroom), $this->makeStudent($classroom), $this->makeStudent($classroom)];
        foreach ([$a, $b, $c] as $s) {
            $this->enroll($s, $pg, $session);
            $this->enroll($s, $esai, $session);
            AsesorAssignment::forceCreate(['user_id' => $asesor->id, 'exam_session_id' => $session->id, 'student_id' => $s->id]);
        }

        // a & b mulai ujian; jawaban esai b belum dinilai, c tidak hadir.
        Grade::where('exam_session_id', $session->id)->whereIn('student_id', [$a->id, $b->id])
            ->update(['start_time' => now()->subDays(2), 'end_time' => now()->subDays(2)]);
        $soal = Essay::forceCreate(['exam_id' => $esai->id, 'essays_code' => 'E1', 'question' => 'Q', 'answer' => '<p></p>']);
        foreach ([[$a, 80], [$b, null]] as [$s, $score]) {
            AnswerEssay::forceCreate([
                'answeressays_code' => 'AE-' . Str::random(6), 'exam_id' => $esai->id, 'exam_session_id' => $session->id,
                'essay_id' => $soal->id, 'student_id' => $s->id, 'essay_order' => 1, 'answer_order' => '1', 'is_correct' => 'N', 'answer' => 'x', 'score' => $score,
            ]);
        }

        // a sudah final, b diverifikasi PK saja.
        ParticipantResult::forceCreate(['exam_session_id' => $session->id, 'student_id' => $a->id, 'keputusan' => 'LULUS',
            'manager_verified_at' => now(), 'cert_manager_verified_at' => now(), 'is_finalized' => true, 'finalized_at' => now()]);
        ParticipantResult::forceCreate(['exam_session_id' => $session->id, 'student_id' => $b->id, 'keputusan' => 'LULUS',
            'manager_verified_at' => now()]);

        $participant = Participant::forceCreate(['name' => 'Calon Asesi', 'email' => 'c@example.com', 'password' => bcrypt('x')]);
        AssessmentApplication::forceCreate(['code' => 'APP-1', 'participant_id' => $participant->id, 'classroom_id' => $classroom->id, 'exam_session_id' => $session->id, 'kode_batch' => '-', 'tujuan_asesmen' => 'Sertifikasi',
            'status' => 'submitted', 'submitted_at' => now()]);

        $this->actingAs($this->user(UserRole::Admin))
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('actions.applications', 1)
                ->where('actions.assignment.no_asesor', 0)
                ->where('actions.assignment.no_pk', 0)
                ->where('actions.assessment', 1)   // b belum dinilai
                ->where('actions.pk', 1)            // 3 peserta, a & b sudah diverifikasi PK → c
                ->has('pipeline', 1)
                ->where('pipeline.0.total', 3)
                ->where('pipeline.0.progress.started', 2)
                ->where('pipeline.0.progress.assessed', 1)
                ->where('pipeline.0.progress.pk', 2)
                ->where('pipeline.0.progress.final', 1)
                ->where('actions.ms', 1)
                ->where('actions.send', 1)
                ->has('activities', 2));
    }

    public function test_active_session_shows_missing_assignments(): void
    {
        $classroom = $this->makeClassroom();
        $esai      = $this->makeExam($classroom, 'Essay');
        $session   = $this->makeSession($esai);
        $session->forceFill(['id' => AdminDashboardService::TRACK_FROM_SESSION_ID + 5])->save();
        $this->enroll($this->makeStudent($classroom), $esai, $session);

        $this->actingAs($this->user(UserRole::Admin))
            ->get('/admin/dashboard')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('activeSessions', 1)
                ->where('activeSessions.0.state', 'berlangsung')
                ->where('activeSessions.0.assigned', 0)
                ->where('actions.assignment.no_asesor', 1)
                ->where('actions.assignment.no_pk', 1));
    }

    public function test_finished_session_fully_sent_leaves_pipeline(): void
    {
        $classroom = $this->makeClassroom();
        $pg        = $this->makeExam($classroom);
        $session   = $this->makeSession($pg);
        $session->forceFill(['id' => AdminDashboardService::TRACK_FROM_SESSION_ID, 'end_time' => now()->subHour()])->save();
        $student = $this->makeStudent($classroom);
        $this->enroll($student, $pg, $session);
        ParticipantResult::forceCreate(['exam_session_id' => $session->id, 'student_id' => $student->id, 'keputusan' => 'LULUS',
            'is_finalized' => true, 'finalized_at' => now(), 'distributed_at' => now()]);

        $this->actingAs($this->user(UserRole::Admin))
            ->get('/admin/dashboard')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('pipeline', 0)
                ->where('stats.current.certified', 1)
                ->where('passRates.0.rate', 100));
    }

    public function test_non_admin_cannot_open(): void
    {
        $this->actingAs($this->user(UserRole::Asesor))
            ->get('/admin/dashboard')
            ->assertRedirect();
    }
}
