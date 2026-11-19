<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AssessmentApplication;
use App\Models\Classroom;
use App\Models\Exam;
use App\Models\ExamGroup;
use App\Models\ExamSession;
use App\Models\Participant;
use App\Models\StudentPraktikAttempt;
use App\Models\User;
use App\Models\UserRoleAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Feature\Concerns\CreatesExamFixtures;
use Tests\TestCase;

/**
 * Ujian Praktik = jenis ujian (admin/exams) berisi panduan + template, dipilih per sesi lewat
 * exam_id_praktik. Sesi tanpa ujian praktik = Upload Tugas biasa. Hasil diunggah lewat alur tugas.
 */
class UjianPraktikTest extends TestCase
{
    use RefreshDatabase, CreatesExamFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('private');
    }

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

    /** Skema wajib tugas + sesi PG + peserta terdaftar. */
    private function enrolledStudent(string $code = 'GLP'): array
    {
        $classroom = $this->makeClassroom($code);
        $exam      = $this->makeExam($classroom);
        $session   = $this->makeSession($exam);
        $student   = $this->makeStudent($classroom);
        $this->enroll($student, $exam, $session);

        return [$classroom, $session, $student];
    }

    private function examPayload(Classroom $classroom, array $over = []): array
    {
        return array_merge([
            'title'           => 'Praktik GLP V2',
            'type'            => Exam::TYPE_PRAKTIK,
            'classroom_id'    => $classroom->id,
            'duration'        => 0,
            'description'     => 'Ujian praktik',
            'random_question' => 'N',
            'random_answer'   => 'N',
            'show_answer'     => 'N',
            'praktik_panduan' => 'Isi lembar kerja.',
            'template'        => UploadedFile::fake()->create('Lembar Kerja GLP.docx', 40, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
        ], $over);
    }

    /** Buat ujian praktik lewat form admin lalu pasang di sesi. */
    private function attachPraktik(ExamSession $session, Classroom $classroom, int $durasi = 0): Exam
    {
        $this->actingAs($this->admin())
            ->post('/admin/exams', $this->examPayload($classroom, ['duration' => $durasi]))
            ->assertSessionHasNoErrors();

        $exam = Exam::where('type', Exam::TYPE_PRAKTIK)->latest('id')->firstOrFail();
        $session->update(['exam_id_praktik' => $exam->id]);

        return $exam;
    }

    private function uploadHasil(ExamSession $session)
    {
        return $this->postJson('/student/tugas', [
            'exam_session_id' => $session->id,
            'file'            => UploadedFile::fake()->create('hasil.pdf', 20, 'application/pdf'),
        ]);
    }

    private function participantApp(Classroom $classroom, ExamSession $session, $student): AssessmentApplication
    {
        $participant = Participant::forceCreate(['name' => $student->name, 'email' => Str::random(8) . '@example.com', 'password' => bcrypt('password')]);

        $app = AssessmentApplication::forceCreate([
            'code' => 'APL-' . Str::random(8), 'participant_id' => $participant->id, 'classroom_id' => $classroom->id,
            'exam_session_id' => $session->id, 'student_id' => $student->id, 'kode_batch' => '-',
            'tujuan_asesmen' => 'Sertifikasi', 'status' => 'approved',
        ]);

        $this->actingAs($participant, 'participant');
        $this->app['auth']->shouldUse('web');

        return $app;
    }

    // ── Admin: ujian praktik ───────────────────────────────────────────────

    public function test_admin_creates_ujian_praktik_with_template(): void
    {
        [$classroom] = $this->enrolledStudent();

        $this->actingAs($this->admin())->post('/admin/exams', $this->examPayload($classroom))
            ->assertSessionHasNoErrors()->assertRedirect('/admin/exams');

        $exam = Exam::where('type', Exam::TYPE_PRAKTIK)->firstOrFail();
        $this->assertSame('Isi lembar kerja.', $exam->praktik_panduan);
        $this->assertSame('Lembar Kerja GLP.docx', $exam->praktik_template_name);
        Storage::disk('private')->assertExists($exam->praktik_template_path);
        $this->assertArrayNotHasKey('praktik_template_path', $exam->toArray());

        $this->get("/admin/exams/{$exam->id}/praktik-template")->assertOk()->assertDownload('Lembar Kerja GLP.docx');
        $this->get("/admin/exams/{$exam->id}/edit")->assertInertia(fn (Assert $page) => $page
            ->where('exam.has_template', true)
            ->where('exam.praktik_template_name', 'Lembar Kerja GLP.docx')
            ->missing('exam.praktik_template_path')
        );
    }

    public function test_ujian_praktik_requires_template_but_other_types_do_not(): void
    {
        [$classroom] = $this->enrolledStudent();
        $this->actingAs($this->admin());

        $this->post('/admin/exams', $this->examPayload($classroom, ['template' => null]))->assertSessionHasErrors('template');
        $this->post('/admin/exams', $this->examPayload($classroom, ['duration' => -1]))->assertSessionHasErrors('duration');

        $this->post('/admin/exams', $this->examPayload($classroom, ['type' => 'Essay', 'template' => null, 'duration' => 60]))
            ->assertSessionHasNoErrors();
    }

    public function test_updating_keeps_or_replaces_template(): void
    {
        [$classroom, $session] = $this->enrolledStudent();
        $exam    = $this->attachPraktik($session, $classroom);
        $oldPath = $exam->praktik_template_path;

        // Simpan ulang tanpa file (multipart POST + _method seperti form Edit): template lama tetap
        $this->post("/admin/exams/{$exam->id}", array_merge($this->examPayload($classroom, ['template' => null, 'praktik_panduan' => 'Baru']), ['_method' => 'put']))
            ->assertSessionHasNoErrors();
        $this->assertSame($oldPath, $exam->refresh()->praktik_template_path);
        $this->assertSame('Baru', $exam->praktik_panduan);

        $this->post("/admin/exams/{$exam->id}", array_merge($this->examPayload($classroom, [
            'template' => UploadedFile::fake()->create('v2.pdf', 10, 'application/pdf'),
        ]), ['_method' => 'put']))->assertSessionHasNoErrors();

        Storage::disk('private')->assertMissing($oldPath);
        Storage::disk('private')->assertExists($exam->refresh()->praktik_template_path);
        $this->assertSame('v2.pdf', $exam->praktik_template_name);
    }

    public function test_session_form_only_accepts_ujian_praktik_exams(): void
    {
        [$classroom, $session] = $this->enrolledStudent();
        $pg      = Exam::findOrFail($session->exam_id_pg);
        $praktik = $this->attachPraktik($session, $classroom);

        $base = [
            'title' => 'Sesi', 'start_time' => now()->format('Y-m-d H:i'), 'end_time' => now()->addDay()->format('Y-m-d H:i'),
            'konteks_asesmen' => 'Sertifikasi', 'tempat_ujian' => 'Online', 'kode_batch' => 'B1',
        ];

        $this->post('/admin/exam_sessions', $base + ['exam_id_praktik' => $pg->id])->assertSessionHasErrors('exam_id_praktik');

        // Sesi hanya ujian praktik tetap sah
        $this->post('/admin/exam_sessions', $base + ['exam_id_praktik' => $praktik->id])->assertSessionHasNoErrors();
        $this->assertSame($praktik->id, ExamSession::latest('id')->firstOrFail()->exam_id_praktik);

        // Mengubah sesi tidak membuat ExamGroup untuk ujian praktik
        $this->put("/admin/exam_sessions/{$session->id}", $base + ['exam_id_pg' => $pg->id, 'exam_id_praktik' => $praktik->id])
            ->assertSessionHasNoErrors();
        $this->assertSame(0, ExamGroup::where('exam_id', $praktik->id)->count());
        $this->assertSame($praktik->id, $session->refresh()->exam_id_praktik);
    }

    // Seperti sesi Lifting V2: hanya PG + wawancara, Ujian Esai & Ujian Praktik "— Tidak ada —".
    public function test_session_without_esai_and_praktik_saves(): void
    {
        [$classroom, $session] = $this->enrolledStudent();
        $pg = Exam::findOrFail($session->exam_id_pg);
        $this->attachPraktik($session, $classroom);
        $session->update(['exam_id_esai' => $this->makeExam($classroom, 'Essay', 60)->id]);

        $this->actingAs($this->admin());
        $data = [
            'title' => 'Sesi Lifting', 'start_time' => now()->format('Y-m-d H:i'), 'end_time' => now()->addDay()->format('Y-m-d H:i'),
            'konteks_asesmen' => 'Sertifikasi', 'tempat_ujian' => 'Online', 'kode_batch' => 'B1',
            'exam_id_pg' => $pg->id, 'exam_id_esai' => '', 'exam_id_praktik' => '', 'has_wawancara' => true,
        ];

        $this->post('/admin/exam_sessions', $data)->assertSessionHasNoErrors()->assertRedirect(route('admin.exam_sessions.index'));
        $created = ExamSession::latest('id')->firstOrFail();
        $this->assertNotSame($session->id, $created->id);
        $this->assertSame([$pg->id, null, null], [$created->exam_id_pg, $created->exam_id_esai, $created->exam_id_praktik]);

        // Sesi yang tadinya punya esai & praktik dikosongkan
        $this->put("/admin/exam_sessions/{$session->id}", $data)->assertSessionHasNoErrors()->assertRedirect(route('admin.exam_sessions.index'));
        $session->refresh();
        $this->assertNull($session->exam_id_esai);
        $this->assertNull($session->exam_id_praktik);

        $this->get("/admin/exam_sessions/{$session->id}/edit")->assertOk();
        $this->get("/admin/exam_sessions/{$session->id}")->assertOk();
    }

    // Daftar sesi (admin/exam_sessions) memakai NOW() khusus MySQL, jadi hanya detail sesi yang diuji di SQLite.
    public function test_session_detail_shows_praktik_exam(): void
    {
        [$classroom, $session] = $this->enrolledStudent();
        $this->attachPraktik($session, $classroom, 120);

        $this->get("/admin/exam_sessions/{$session->id}")->assertInertia(fn (Assert $page) => $page
            ->where('exam_session.exam_praktik.title', 'Praktik GLP V2')
            ->where('exam_session.exam_praktik.duration', 120)
            ->missing('exam_session.exam_praktik.praktik_template_path')
        );
    }

    // ── Peserta: tanpa batas waktu ─────────────────────────────────────────

    public function test_session_without_praktik_stays_upload_tugas(): void
    {
        [, $session, $student] = $this->enrolledStudent();

        $this->actingAsStudent($student);
        $this->get("/student/tugas/{$session->id}")->assertInertia(fn (Assert $page) => $page
            ->where('tugas.praktik', false)
            ->where('tugas.label', 'Tugas')
            ->where('timer.timed', false)
        );
        $this->get("/student/tugas/{$session->id}/template")->assertNotFound();
        $this->get('/student/dashboard')->assertInertia(fn (Assert $page) => $page->where("tugas_label_by_session.{$session->id}", 'Tugas'));
    }

    public function test_student_sees_panduan_and_downloads_template(): void
    {
        [$classroom, $session, $student] = $this->enrolledStudent();
        $this->attachPraktik($session, $classroom);

        $this->actingAsStudent($student);

        $this->get("/student/tugas/{$session->id}")->assertInertia(fn (Assert $page) => $page
            ->component('Student/Tugas/Show')
            ->where('tugas.praktik', true)
            ->where('tugas.title', 'Praktik GLP V2')
            ->where('tugas.panduan', 'Isi lembar kerja.')
            ->where('tugas.template_name', 'Lembar Kerja GLP.docx')
            ->where('timer.timed', false)
        );
        $this->get('/student/dashboard')->assertInertia(fn (Assert $page) => $page->where("tugas_label_by_session.{$session->id}", 'Ujian Praktik'));
        $this->get("/student/tugas/{$session->id}/template")->assertOk()->assertDownload('Lembar Kerja GLP.docx');
        $this->post("/student/tugas/{$session->id}/mulai")->assertNotFound();

        $this->uploadHasil($session)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('student_tasks', ['student_id' => $student->id, 'exam_session_id' => $session->id, 'original_filename' => 'hasil.pdf']);
    }

    public function test_template_follows_the_session_version(): void
    {
        [$classroom, $session, $student] = $this->enrolledStudent();
        $v1 = $this->attachPraktik($session, $classroom);

        // Sesi lain memakai versi baru; sesi lama tetap versi lama
        $other = $this->makeSession($this->makeExam($classroom));
        $this->actingAs($this->admin())->post('/admin/exams', $this->examPayload($classroom, [
            'title' => 'Praktik GLP V3', 'template' => UploadedFile::fake()->create('V3.docx', 10, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
        ]));
        $other->update(['exam_id_praktik' => Exam::where('title', 'Praktik GLP V3')->value('id')]);

        $this->actingAsStudent($student)->get("/student/tugas/{$session->id}/template")->assertDownload('Lembar Kerja GLP.docx');
        $this->assertSame($v1->id, $session->refresh()->exam_id_praktik);

        // Tidak terdaftar di sesi lain
        $this->get("/student/tugas/{$other->id}/template")->assertForbidden();
    }

    public function test_participant_portal_shows_praktik_and_downloads_template(): void
    {
        [$classroom, $session, $student] = $this->enrolledStudent();
        $this->attachPraktik($session, $classroom);
        $app = $this->participantApp($classroom, $session, $student);

        $this->get('/peserta/dashboard')->assertInertia(fn (Assert $page) => $page
            ->where('applications.0.tugas.praktik', true)
            ->where('applications.0.tugas.template_name', 'Lembar Kerja GLP.docx')
            ->where('applications.0.tugas.durasi', null)
            ->where('applications.0.tugas.file', null)
        );
        $this->get("/peserta/aplikasi/{$app->id}/tugas/template")->assertOk()->assertDownload('Lembar Kerja GLP.docx');
    }

    // ── Peserta: berwaktu ──────────────────────────────────────────────────

    public function test_timed_materials_are_hidden_until_start(): void
    {
        [$classroom, $session, $student] = $this->enrolledStudent();
        $this->attachPraktik($session, $classroom, 30);

        $this->actingAsStudent($student);
        $this->get("/student/tugas/{$session->id}")->assertInertia(fn (Assert $page) => $page
            ->where('timer.timed', true)
            ->where('timer.durasi', 30)
            ->where('timer.started', false)
            ->where('tugas.panduan', null)
            ->where('tugas.template_name', null)
        );
        $this->get("/student/tugas/{$session->id}/template")->assertForbidden();
        $this->uploadHasil($session)->assertForbidden()->assertJson(['message' => 'Mulai Ujian Praktik terlebih dahulu.']);

        $this->post("/student/tugas/{$session->id}/mulai")->assertRedirect("/student/tugas/{$session->id}");

        $this->get("/student/tugas/{$session->id}")->assertInertia(fn (Assert $page) => $page
            ->where('timer.started', true)
            ->where('timer.expired', false)
            ->where('timer.remaining_seconds', fn ($s) => $s > 1790 && $s <= 1800)
            ->where('tugas.panduan', 'Isi lembar kerja.')
            ->where('tugas.template_name', 'Lembar Kerja GLP.docx')
        );
        $this->get("/student/tugas/{$session->id}/template")->assertOk();
        $this->uploadHasil($session)->assertRedirect()->assertSessionHasNoErrors();
    }

    public function test_starting_again_or_changing_duration_does_not_move_deadline(): void
    {
        [$classroom, $session, $student] = $this->enrolledStudent();
        $exam = $this->attachPraktik($session, $classroom, 30);

        $this->actingAsStudent($student)->post("/student/tugas/{$session->id}/mulai");
        $first = StudentPraktikAttempt::firstOrFail()->started_at;

        $this->travel(10)->minutes();
        $this->actingAsStudent($student)->post("/student/tugas/{$session->id}/mulai");
        $exam->update(['duration' => 120]);

        $attempt = StudentPraktikAttempt::firstOrFail();
        $this->assertEquals($first, $attempt->started_at);
        $this->assertSame(30, $attempt->durasi);
        $this->assertSame($exam->id, $attempt->exam_id);
        $this->assertSame(1, StudentPraktikAttempt::count());
    }

    public function test_upload_closes_after_deadline_with_short_grace_and_exam_unlocks(): void
    {
        [$classroom, $session, $student] = $this->enrolledStudent();
        $this->attachPraktik($session, $classroom, 30);
        $group = ExamGroup::where('student_id', $student->id)->firstOrFail();

        $this->actingAsStudent($student)->post("/student/tugas/{$session->id}/mulai");

        // Belum unggah & waktu berjalan: ujian lain masih terkunci
        $this->get("/student/exam-confirmation/{$group->id}")->assertRedirect("/student/tugas/{$session->id}");

        // Lewat batas waktu tapi masih dalam toleransi: template sudah ditutup
        $this->travel(31)->minutes();
        $this->get("/student/tugas/{$session->id}/template")->assertForbidden();

        // Lewat toleransi: unggah ditutup, ujian lain terbuka walau tanpa unggahan
        $this->travel(2)->minutes();
        $this->uploadHasil($session)->assertForbidden()
            ->assertJson(['message' => 'Waktu Ujian Praktik sudah habis. Hasil tidak dapat diunggah lagi.']);
        $this->get("/student/tugas/{$session->id}")->assertInertia(fn (Assert $page) => $page
            ->where('timer.expired', true)
            ->where('timer.remaining_seconds', 0)
            ->where('tugas.template_name', null)
        );
        $this->get("/student/exam-confirmation/{$group->id}")->assertOk();
        $this->get('/student/dashboard')->assertInertia(fn (Assert $page) => $page
            ->where("tugas_closed_by_session.{$session->id}", true)
        );
        $this->assertDatabaseCount('student_tasks', 0);
    }

    public function test_upload_within_grace_is_accepted(): void
    {
        [$classroom, $session, $student] = $this->enrolledStudent();
        $this->attachPraktik($session, $classroom, 30);

        $this->actingAsStudent($student)->post("/student/tugas/{$session->id}/mulai");
        $this->travel(31)->minutes();

        $this->uploadHasil($session)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseCount('student_tasks', 1);
    }

    public function test_participant_portal_defers_timed_praktik_to_exam_portal(): void
    {
        [$classroom, $session, $student] = $this->enrolledStudent();
        $this->attachPraktik($session, $classroom, 30);
        $app = $this->participantApp($classroom, $session, $student);

        $this->get('/peserta/dashboard')->assertInertia(fn (Assert $page) => $page
            ->where('applications.0.tugas.durasi', 30)
            ->where('applications.0.tugas.panduan', null)
            ->where('applications.0.tugas.template_name', null)
        );
        $this->get("/peserta/aplikasi/{$app->id}/tugas/template")->assertForbidden();
        $this->post("/peserta/aplikasi/{$app->id}/tugas", ['file' => UploadedFile::fake()->create('h.pdf', 10, 'application/pdf')])
            ->assertSessionHasErrors('file');
        $this->assertDatabaseCount('student_tasks', 0);
    }
}
