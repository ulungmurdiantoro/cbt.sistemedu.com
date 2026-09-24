<?php

namespace Tests\Feature;

use App\Models\AsesorAssignment;
use App\Models\AssessmentApplication;
use App\Models\ExamGroup;
use App\Models\Participant;
use App\Models\User;
use App\Services\StudentEnrollmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Feature\Concerns\CreatesExamFixtures;
use Tests\TestCase;

class StudentEnrollmentServiceTest extends TestCase
{
    use RefreshDatabase;
    use CreatesExamFixtures;

    private StudentEnrollmentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(StudentEnrollmentService::class);
    }

    private function application(): AssessmentApplication
    {
        $classroom = $this->makeClassroom();
        $classroom->forceFill(['kode_skema' => 'SKM-01-K3U-2024'])->save();

        $pg      = $this->makeExam($classroom);
        $esai    = $this->makeExam($classroom, 'Essay');
        $session = $this->makeSession($pg, $esai);

        $participant = Participant::forceCreate([
            'name'     => 'Budi',
            'email'    => Str::random(8) . '@example.com',
            'password' => bcrypt('x'),
        ]);

        return AssessmentApplication::forceCreate([
            'code'            => 'APP-' . Str::random(6),
            'participant_id'  => $participant->id,
            'classroom_id'    => $classroom->id,
            'exam_session_id' => $session->id,
            'kode_batch'      => '07',
        ]);
    }

    public function test_approve_flow_creates_sequential_student_and_enrolls_in_both_exams(): void
    {
        $app = $this->application();

        $student = $this->service->findOrCreateStudent($app);
        $group   = $this->service->enroll($student->id, $app->examSession);

        $this->assertSame('K3U.07.' . now()->year . '.00001', $student->no_participant);
        $this->assertSame(2, ExamGroup::where('student_id', $student->id)->count());
        $this->assertSame($app->examSession->exam_id_pg, $group->exam_id);

        // Permohonan kedua untuk skema yang sama memakai akun yang sama.
        $this->assertTrue($this->service->findOrCreateStudent($app)->is($student));
    }

    public function test_reissue_moves_asesor_assignment_to_new_student(): void
    {
        $app     = $this->application();
        $old     = $this->service->findOrCreateStudent($app);
        $app->update(['student_id' => $old->id, 'exam_group_id' => $this->service->enroll($old->id, $app->examSession)->id]);

        $asesor = User::forceCreate(['users_code' => 'A1', 'name' => 'A', 'email' => 'a@example.com', 'password' => 'x']);
        AsesorAssignment::forceCreate(['user_id' => $asesor->id, 'exam_session_id' => $app->exam_session_id, 'student_id' => $old->id]);

        $new = $this->service->reissue($app->fresh(), 'kartu hilang', $asesor->id);

        $this->assertFalse((bool) $old->fresh()->is_active);
        $this->assertSame('K3U.07.' . now()->year . '.00002', $new->no_participant);
        $this->assertSame($new->id, $app->fresh()->student_id);
        $this->assertSame(0, ExamGroup::where('student_id', $old->id)->count());
        $this->assertSame($new->id, AsesorAssignment::first()->student_id);
    }

    public function test_move_to_session_is_blocked_once_exam_activity_exists(): void
    {
        $app     = $this->application();
        $student = $this->service->findOrCreateStudent($app);
        $app->update(['student_id' => $student->id]);
        $this->service->enroll($student->id, $app->examSession);

        $this->assertFalse($this->service->hasExamActivity($app));

        $this->enroll($student, $app->examSession->examPg, $app->examSession); // membuat grade

        $this->assertTrue($this->service->hasExamActivity($app->fresh()));
    }
}
