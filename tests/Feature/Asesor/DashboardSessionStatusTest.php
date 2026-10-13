<?php

namespace Tests\Feature\Asesor;

use App\Enums\UserRole;
use App\Models\AnswerEssay;
use App\Models\AsesorAssignment;
use App\Models\AssessmentApplication;
use App\Models\Essay;
use App\Models\ExamSession;
use App\Models\Grade;
use App\Models\InterviewAssessment;
use App\Models\Participant;
use App\Models\User;
use App\Models\UserRoleAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Feature\Concerns\CreatesExamFixtures;
use Tests\TestCase;

/** Dashboard asesor: sesi ID >= 75 baru pindah ke Sesi Selesai setelah TTD AK.01, esai & wawancara lengkap. */
class DashboardSessionStatusTest extends TestCase
{
    use RefreshDatabase;
    use CreatesExamFixtures;

    private array $ctx;

    protected function setUp(): void
    {
        parent::setUp();

        $classroom = $this->makeClassroom();
        $asesor    = User::forceCreate([
            'users_code' => 'ASR-' . Str::random(6),
            'name'       => 'Asesor Test',
            'email'      => Str::random(8) . '@example.com',
            'password'   => bcrypt('password'),
        ]);
        UserRoleAssignment::forceCreate(['user_id' => $asesor->id, 'role' => UserRole::Asesor->value]);

        $this->ctx = [
            'classroom' => $classroom,
            'asesor'    => $asesor,
            'pg'        => $this->makeExam($classroom),
            'esai'      => $this->makeExam($classroom, 'Essay'),
        ];
    }

    /** Sesi PG + esai + wawancara dengan ID tertentu (ambang aturan baru = ID sesi). */
    private function sessionWithId(int $id, array $attrs = []): ExamSession
    {
        $session = $this->makeSession($this->ctx['pg'], $this->ctx['esai']);
        ExamSession::whereKey($session->id)->update($attrs + ['id' => $id, 'has_wawancara' => true]);

        return ExamSession::findOrFail($id);
    }

    /** Peserta yang ditugaskan ke asesor; $started = sudah mulai ujian dan menjawab esai. */
    private function participant(ExamSession $session, bool $started = true): array
    {
        $student = $this->makeStudent($this->ctx['classroom']);
        $this->enroll($student, $this->ctx['pg'], $session);
        $this->enroll($student, $this->ctx['esai'], $session);
        AsesorAssignment::forceCreate(['user_id' => $this->ctx['asesor']->id, 'exam_session_id' => $session->id, 'student_id' => $student->id]);

        if (!$started) {
            return ['student' => $student];
        }

        Grade::where('exam_session_id', $session->id)->where('student_id', $student->id)->update(['start_time' => now()]);

        $essay  = Essay::forceCreate(['exam_id' => $this->ctx['esai']->id, 'essays_code' => 'E-' . Str::random(6), 'question' => 'Q', 'answer' => '-']);
        $answer = AnswerEssay::forceCreate([
            'answeressays_code' => 'answess-' . Str::ulid(),
            'exam_id'           => $this->ctx['esai']->id,
            'exam_session_id'   => $session->id,
            'essay_id'          => $essay->id,
            'student_id'        => $student->id,
            'essay_order'       => 1,
            'answer_order'      => '1',
            'answer'            => 'jawaban',
        ]);

        $participant = Participant::forceCreate([
            'name'     => $student->name,
            'email'    => Str::random(8) . '@example.com',
            'password' => bcrypt('password'),
        ]);
        $application = AssessmentApplication::forceCreate([
            'code'            => 'APL-' . Str::random(8),
            'participant_id'  => $participant->id,
            'classroom_id'    => $this->ctx['classroom']->id,
            'exam_session_id' => $session->id,
            'student_id'      => $student->id,
            'kode_batch'      => '-',
            'tujuan_asesmen'  => 'Sertifikasi',
            'status'          => 'approved',
        ]);

        return compact('student', 'answer', 'application');
    }

    private function finishTasks(ExamSession $session, array $p): void
    {
        $p['application']->forceFill(['asesor_verified_at' => now()])->save();
        $p['answer']->forceFill(['score' => 80])->save();
        InterviewAssessment::forceCreate([
            'exam_session_id' => $session->id,
            'student_id'      => $p['student']->id,
            'asesor_id'       => $this->ctx['asesor']->id,
            'total_nilai'     => 85,
        ]);
    }

    private function dashboard()
    {
        return $this->actingAs($this->ctx['asesor'])->get('/asesor/dashboard');
    }

    public function test_session_moves_to_completed_only_after_all_tasks_are_done(): void
    {
        $session = $this->sessionWithId(75);
        $p       = $this->participant($session);

        $this->dashboard()->assertInertia(fn (Assert $page) => $page
            ->has('completed_sessions', 0)
            ->where('active_sessions.0.id', 75)
            ->where('active_sessions.0.progress.items.ak01', ['done' => 0, 'total' => 1])
            ->where('active_sessions.0.progress.items.esai', ['done' => 0, 'total' => 1])
            ->where('active_sessions.0.progress.items.wawancara', ['done' => 0, 'total' => 1]));

        // Jadwal sesi belum berakhir — tetap pindah begitu semua tugas lengkap.
        $this->finishTasks($session, $p);

        $this->dashboard()->assertInertia(fn (Assert $page) => $page
            ->has('active_sessions', 0)
            ->where('completed_sessions.0.id', 75));
    }

    public function test_ended_session_with_pending_tasks_stays_active(): void
    {
        $session = $this->sessionWithId(76, ['end_time' => now()->subDay()]);
        $p       = $this->participant($session);
        $this->finishTasks($session, $p);
        $p['answer']->forceFill(['score' => null])->save();

        $this->dashboard()->assertInertia(fn (Assert $page) => $page
            ->has('completed_sessions', 0)
            ->where('active_sessions.0.progress.items.esai', ['done' => 0, 'total' => 1]));
    }

    public function test_absent_participant_does_not_block_completion(): void
    {
        $session = $this->sessionWithId(75);
        $this->finishTasks($session, $this->participant($session));
        $this->participant($session, started: false);

        $this->dashboard()->assertInertia(fn (Assert $page) => $page
            ->has('active_sessions', 0)
            ->where('completed_sessions.0.id', 75));
    }

    public function test_session_nobody_has_started_stays_active(): void
    {
        $session = $this->sessionWithId(75);
        $this->participant($session, started: false);

        $this->dashboard()->assertInertia(fn (Assert $page) => $page
            ->has('completed_sessions', 0)
            ->where('active_sessions.0.id', 75));
    }

    public function test_sessions_before_75_still_follow_the_schedule(): void
    {
        $session = $this->sessionWithId(74, ['end_time' => now()->subDay()]);
        $this->participant($session);

        $this->dashboard()->assertInertia(fn (Assert $page) => $page
            ->has('active_sessions', 0)
            ->where('completed_sessions.0.id', 74)
            ->where('completed_sessions.0.progress', null));
    }
}
