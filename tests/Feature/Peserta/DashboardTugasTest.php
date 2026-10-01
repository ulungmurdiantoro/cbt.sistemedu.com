<?php

namespace Tests\Feature\Peserta;

use App\Models\AssessmentApplication;
use App\Models\ExamGroup;
use App\Models\Participant;
use App\Models\Student;
use App\Models\StudentTask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Feature\Concerns\CreatesExamFixtures;
use Tests\TestCase;

class DashboardTugasTest extends TestCase
{
    use RefreshDatabase, CreatesExamFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('private');
    }

    /** Permohonan peserta sertifikasi + akun ujiannya, terdaftar di sesi. */
    private function application(string $classroomCode = 'SPMI', string $status = 'approved'): AssessmentApplication
    {
        $classroom = $this->makeClassroom($classroomCode);
        $exam      = $this->makeExam($classroom);
        $session   = $this->makeSession($exam);
        $student   = $this->makeStudent($classroom);
        $this->enroll($student, $exam, $session);

        $participant = Participant::forceCreate([
            'name'     => $student->name,
            'email'    => Str::random(8) . '@example.com',
            'password' => bcrypt('password'),
        ]);

        return AssessmentApplication::forceCreate([
            'code'            => 'APL-' . Str::random(8),
            'participant_id'  => $participant->id,
            'classroom_id'    => $classroom->id,
            'exam_session_id' => $session->id,
            'student_id'      => $student->id,
            'kode_batch'      => '-',
            'tujuan_asesmen'  => 'Sertifikasi',
            'status'          => $status,
        ]);
    }

    private function actingAsParticipant(AssessmentApplication $app): static
    {
        $this->actingAs($app->participant, 'participant');
        $this->app['auth']->shouldUse('web');

        return $this;
    }

    private function upload(AssessmentApplication $app, UploadedFile $file)
    {
        return $this->post("/peserta/aplikasi/{$app->id}/tugas", ['file' => $file]);
    }

    public function test_participant_uploads_tugas_from_dashboard(): void
    {
        $app = $this->application();
        $this->actingAsParticipant($app);

        $this->get('/peserta/dashboard')->assertInertia(fn (Assert $page) => $page
            ->where('applications.0.tugas.file', null)
            ->has('tugas_accept')
        );

        $this->upload($app, UploadedFile::fake()->create('tugas akhir.pdf', 100, 'application/pdf'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $task = StudentTask::firstOrFail();
        $this->assertSame($app->student_id, $task->student_id);
        $this->assertSame($app->exam_session_id, $task->exam_session_id);
        Storage::disk('private')->assertExists($task->file_path);

        $this->get('/peserta/dashboard')->assertInertia(fn (Assert $page) => $page
            ->where('applications.0.tugas.file.name', 'tugas akhir.pdf')
        );
    }

    public function test_dashboard_upload_unlocks_exam_like_student_portal(): void
    {
        $app   = $this->application();
        $group = ExamGroup::where('student_id', $app->student_id)->firstOrFail();

        $this->actingAsStudent(Student::findOrFail($app->student_id));
        $this->get("/student/exam-confirmation/{$group->id}")
            ->assertRedirect("/student/tugas/{$app->exam_session_id}");

        $this->actingAsParticipant($app)
            ->upload($app, UploadedFile::fake()->create('tugas.pdf', 50, 'application/pdf'));

        $this->actingAsStudent(Student::findOrFail($app->student_id));
        $this->get("/student/exam-confirmation/{$group->id}")->assertOk();
    }

    public function test_replacing_tugas_removes_old_file(): void
    {
        $app = $this->application();
        $this->actingAsParticipant($app);

        $this->upload($app, UploadedFile::fake()->create('v1.pdf', 50, 'application/pdf'));
        $oldPath = StudentTask::firstOrFail()->file_path;

        $this->upload($app, UploadedFile::fake()->create('v2.pdf', 50, 'application/pdf'));
        $task = StudentTask::firstOrFail();

        $this->assertSame('v2.pdf', $task->original_filename);
        Storage::disk('private')->assertExists($task->file_path);
        Storage::disk('private')->assertMissing($oldPath);
        $this->assertSame(1, StudentTask::count());
    }

    public function test_disallowed_file_type_is_rejected(): void
    {
        $app = $this->application();

        $this->actingAsParticipant($app)
            ->upload($app, UploadedFile::fake()->createWithContent('shell.php', '<?php echo 1;'))
            ->assertSessionHasErrors('file');

        $this->assertSame(0, StudentTask::count());
    }

    public function test_cannot_upload_to_another_participants_application(): void
    {
        $mine  = $this->application();
        $other = $this->application('TOT');

        $this->actingAsParticipant($mine)
            ->upload($other, UploadedFile::fake()->create('tugas.pdf', 50, 'application/pdf'))
            ->assertForbidden();

        $this->assertSame(0, StudentTask::count());
    }

    public function test_not_shown_for_unapproved_or_exempt_skema(): void
    {
        $submitted = $this->application(status: 'submitted');
        $this->actingAsParticipant($submitted)
            ->upload($submitted, UploadedFile::fake()->create('tugas.pdf', 50, 'application/pdf'))
            ->assertForbidden();

        // LEM termasuk skema yang dikecualikan dari wajib tugas (Student::requiresTugas).
        $exempt = $this->application('LEM');
        $this->actingAsParticipant($exempt);
        $this->get('/peserta/dashboard')->assertInertia(fn (Assert $page) => $page
            ->where('applications.0.tugas', null)
        );
        $this->upload($exempt, UploadedFile::fake()->create('tugas.pdf', 50, 'application/pdf'))
            ->assertNotFound();

        $this->assertSame(0, StudentTask::count());
    }
}
