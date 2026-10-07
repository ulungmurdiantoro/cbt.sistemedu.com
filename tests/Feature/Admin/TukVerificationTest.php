<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\ApplicationDocument;
use App\Models\AssessmentApplication;
use App\Models\ClassroomDocumentRequirement;
use App\Models\ExamSession;
use App\Models\Participant;
use App\Models\Student;
use App\Models\TukVerification;
use App\Models\User;
use App\Models\UserRoleAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Feature\Concerns\CreatesExamFixtures;
use Tests\TestCase;

class TukVerificationTest extends TestCase
{
    use RefreshDatabase, CreatesExamFixtures;

    private ExamSession $session;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->session = $this->makeSession($this->makeExam($this->makeClassroom()));
        $this->session->forceFill(['verifikasi_tuk' => true])->save();

        $this->admin = $this->user('Admin Pengawas');
    }

    private function user(string $name, UserRole $role = UserRole::Admin): User
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

    private function participant(string $noParticipant): Student
    {
        $exam    = $this->session->referenceExam;
        $student = $this->makeStudent($exam->classroom);
        $student->forceFill(['no_participant' => $noParticipant])->save();
        $this->enroll($student, $exam, $this->session);

        return $student;
    }

    private function url(Student $student, string $suffix = ''): string
    {
        return "/admin/penilaian/{$this->session->id}/verifikasi-tuk/{$student->id}{$suffix}";
    }

    public function test_session_toggle_is_saved(): void
    {
        $exam = $this->session->referenceExam;

        $this->actingAs($this->admin)->post('/admin/exam_sessions', [
            'title'           => 'Sesi TUK',
            'exam_id_pg'      => $exam->id,
            'start_time'      => now()->toDateTimeString(),
            'end_time'        => now()->addDay()->toDateTimeString(),
            'konteks_asesmen' => 'Sertifikasi Person',
            'tempat_ujian'    => 'Online (Zoom Meeting)',
            'kode_batch'      => '012',
            'verifikasi_tuk'  => true,
        ])->assertSessionHasNoErrors();

        $this->assertTrue(ExamSession::where('title', 'Sesi TUK')->firstOrFail()->verifikasi_tuk);
    }

    public function test_admin_fills_checklist_as_pengawas(): void
    {
        $student = $this->participant('NP-001');

        $this->actingAs($this->admin)->post($this->url($student), [
            'tanggal_asesmen' => '2026-10-05',
            'waktu_asesmen'   => '08.30 – 10.00 WIB',
            'lokasi_peserta'  => 'Rumah, Semarang',
            'items'           => [
                'B1'    => ['status' => 'sesuai', 'catatan' => ''],
                'C2'    => ['status' => 'tidak_sesuai', 'catatan' => 'Lampu redup, sudah diperbaiki'],
                'D1'    => ['status' => null, 'catatan' => ''],      // kosong → tidak disimpan
                'Z9'    => ['status' => 'sesuai', 'catatan' => ''],  // bukan butir FR.TUK.06 → dibuang
            ],
            'kesimpulan_awal' => 'layak_perbaikan',
            'catatan_awal'    => 'Pencahayaan diperbaiki sebelum ujian.',
            'pengawas_id'     => $this->admin->id,
        ])->assertSessionHasNoErrors()->assertSessionHas('success');

        $v = TukVerification::firstOrFail();
        $this->assertSame(['B1', 'C2'], array_keys($v->items));
        $this->assertSame('tidak_sesuai', $v->items['C2']['status']);
        $this->assertSame('layak_perbaikan', $v->kesimpulan_awal);
        $this->assertSame($this->admin->id, $v->pengawas_id);
        $this->assertSame('Admin Pengawas', $v->pengawas_name);
        $this->assertNotNull($v->verified_at);

        // Melengkapi bagian F/H/I setelah ujian tidak menggeser waktu verifikasi awal.
        $verifiedAt = $v->verified_at;
        $this->travel(2)->hours();
        $this->actingAs($this->admin)->post($this->url($student), [
            'items'            => $v->items + ['F1' => ['status' => 'sesuai', 'catatan' => '']],
            'kesimpulan_awal'  => 'layak_perbaikan',
            'hasil_pemantauan' => 'tidak_ada',
            'kesimpulan_akhir' => 'layak',
            'pengawas_id'      => $this->admin->id,
        ])->assertSessionHasNoErrors();

        $v->refresh();
        $this->assertSame('layak', $v->kesimpulan_akhir);
        $this->assertArrayHasKey('F1', $v->items);
        $this->assertTrue($verifiedAt->equalTo($v->verified_at));
    }

    public function test_pengawas_signature_is_the_admins_saved_signature(): void
    {
        $student = $this->participant('NP-001');

        $this->actingAs($this->admin)->get($this->url($student))
            ->assertInertia(fn (Assert $page) => $page
                ->where('default_pengawas_id', $this->admin->id)
                ->where('pengawas_options.0.has_signature', false)
            );

        // TTD default admin — disimpan saat menyetujui permohonan atau di Kelola User.
        Storage::fake('private');
        Storage::disk('private')->put('admin-signatures/1/admin_1.png', 'png');
        User::whereKey($this->admin->id)->update(['signature_path' => 'admin-signatures/1/admin_1.png', 'signature_name' => 'Nama TTD Admin']);
        $this->admin->refresh();

        $this->actingAs($this->admin)->get($this->url($student))
            ->assertInertia(fn (Assert $page) => $page
                ->where('pengawas_options.0.has_signature', true)
                ->where('pengawas_options.0.name', 'Nama TTD Admin')
            );

        $this->actingAs($this->admin)->post($this->url($student), ['kesimpulan_awal' => 'layak', 'pengawas_id' => $this->admin->id]);

        $v = TukVerification::firstOrFail();
        $this->assertSame('admin-signatures/1/admin_1.png', $v->pengawas_signature_path);
        $this->assertSame('Nama TTD Admin', $v->pengawas_name);
    }

    public function test_admin_can_record_another_admin_as_pengawas(): void
    {
        $first  = $this->participant('NP-001');
        $second = $this->participant('NP-002');

        Storage::fake('private');
        Storage::disk('private')->put('user-signatures/9/sig.png', 'png');
        $pengawas = $this->user('Budi Pengawas');
        $pengawas->forceFill(['signature_path' => 'user-signatures/9/sig.png', 'signature_name' => 'Budi Santoso'])->save();
        $asesor = $this->user('Asesor Saja', UserRole::Asesor);

        // Hanya user ber-role admin yang bisa dipilih.
        $this->actingAs($this->admin)->get($this->url($first))
            ->assertInertia(fn (Assert $page) => $page
                ->has('pengawas_options', 2)
                ->where('pengawas_options.1.id', $pengawas->id)
                ->where('pengawas_options.1.name', 'Budi Santoso')
                ->where('pengawas_options.1.has_signature', true)
            );

        $this->actingAs($this->admin)->post($this->url($first), ['kesimpulan_awal' => 'layak', 'pengawas_id' => $asesor->id])
            ->assertSessionHasErrors('pengawas_id');
        $this->actingAs($this->admin)->post($this->url($first), ['kesimpulan_awal' => 'layak'])
            ->assertSessionHasErrors('pengawas_id');
        $this->assertSame(0, TukVerification::count());

        $this->actingAs($this->admin)
            ->post($this->url($first), ['kesimpulan_awal' => 'layak', 'pengawas_id' => $pengawas->id, 'next' => true])
            ->assertRedirect($this->url($second));

        $v = TukVerification::firstOrFail();
        $this->assertSame($pengawas->id, $v->pengawas_id);
        $this->assertSame('Budi Santoso', $v->pengawas_name);
        $this->assertSame('user-signatures/9/sig.png', $v->pengawas_signature_path);

        // Peserta berikutnya otomatis memakai pengawas yang terakhir dipilih; checklist yang
        // sudah ada tetap menampilkan pengawasnya sendiri.
        $this->actingAs($this->admin)->get($this->url($second))
            ->assertInertia(fn (Assert $page) => $page->where('default_pengawas_id', $pengawas->id));
        $this->actingAs($this->admin)->post($this->url($second), ['kesimpulan_awal' => 'layak', 'pengawas_id' => $this->admin->id]);
        $this->actingAs($this->admin)->get($this->url($first))
            ->assertInertia(fn (Assert $page) => $page->where('default_pengawas_id', $pengawas->id));
    }

    public function test_invalid_answers_are_rejected(): void
    {
        $student = $this->participant('NP-001');

        $this->actingAs($this->admin)->post($this->url($student), [
            'items'           => ['B1' => ['status' => 'mungkin']],
            'kesimpulan_awal' => 'lulus',
            'pengawas_id'     => $this->admin->id,
        ])->assertSessionHasErrors(['items.B1.status', 'kesimpulan_awal']);

        $this->assertSame(0, TukVerification::count());
    }

    public function test_participant_outside_session_is_not_found(): void
    {
        $outsider = $this->makeStudent($this->makeClassroom('OTH'));

        $this->actingAs($this->admin)->get($this->url($outsider))->assertNotFound();
        $this->actingAs($this->admin)->post($this->url($outsider), [])->assertNotFound();
    }

    public function test_save_and_next_goes_to_next_participant_by_number(): void
    {
        $second = $this->participant('NP-002');
        $first  = $this->participant('NP-001');

        $this->actingAs($this->admin)
            ->post($this->url($first), ['kesimpulan_awal' => 'layak', 'pengawas_id' => $this->admin->id, 'next' => true])
            ->assertRedirect($this->url($second));

        $this->actingAs($this->admin)->get($this->url($second))->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Penilaian/VerifikasiTuk/Show')
            ->where('verification', null)
            ->where('next_student_id', null)
            ->has('sections', 5)
        );
    }

    public function test_show_includes_identity_document_from_application(): void
    {
        Storage::fake('private');

        $classroom = $this->session->referenceExam->classroom;
        $cv        = ClassroomDocumentRequirement::forceCreate(['classroom_id' => $classroom->id, 'code' => 'CV', 'label' => 'CV', 'order' => 1]);
        $ktp       = ClassroomDocumentRequirement::forceCreate([
            'classroom_id' => $classroom->id, 'code' => 'Identitas', 'label' => 'Dokumen Identitas Diri (KTP/SIM/Paspor)', 'order' => 2,
        ]);

        $uploaded    = $this->participant('NP-001');
        $notUploaded = $this->participant('NP-002');
        $noApp       = $this->participant('NP-003');

        $application = function (Student $student) use ($classroom) {
            $participant = Participant::forceCreate([
                'name'     => $student->name,
                'email'    => Str::random(8) . '@example.com',
                'password' => bcrypt('password'),
            ]);

            return AssessmentApplication::forceCreate([
                'code'            => 'APL-' . Str::random(8),
                'participant_id'  => $participant->id,
                'classroom_id'    => $classroom->id,
                'exam_session_id' => $this->session->id,
                'student_id'      => $student->id,
                'kode_batch'      => '-',
                'tujuan_asesmen'  => 'Sertifikasi',
                'status'          => 'approved',
            ]);
        };

        $app = $application($uploaded);
        $application($notUploaded);

        Storage::disk('private')->put('documents/ktp.jpg', 'jpg');
        ApplicationDocument::forceCreate([
            'assessment_application_id' => $app->id, 'classroom_document_requirement_id' => $cv->id,
            'file_path' => 'documents/cv.pdf', 'original_filename' => 'cv.pdf', 'mime_type' => 'application/pdf',
        ]);
        $doc = ApplicationDocument::forceCreate([
            'assessment_application_id' => $app->id, 'classroom_document_requirement_id' => $ktp->id,
            'file_path' => 'documents/ktp.jpg', 'original_filename' => 'ktp.jpg', 'mime_type' => 'image/jpeg', 'status' => 'verified',
        ]);

        $this->actingAs($this->admin)->get($this->url($uploaded))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Penilaian/VerifikasiTuk/Show')
                ->where('identity_document.label', 'Dokumen Identitas Diri (KTP/SIM/Paspor)')
                ->where('identity_document.application_id', $app->id)
                ->where('identity_document.document.id', $doc->id)
                ->where('identity_document.document.status', 'verified')
                ->where('identity_document.document.is_image', true)
            );

        $this->actingAs($this->admin)->get("/admin/applications/{$app->id}/documents/{$doc->id}/preview")->assertOk();

        $this->actingAs($this->admin)->get($this->url($notUploaded))
            ->assertInertia(fn (Assert $page) => $page
                ->where('identity_document.has_requirement', true)
                ->where('identity_document.document', null)
            );

        $this->actingAs($this->admin)->get($this->url($noApp))
            ->assertInertia(fn (Assert $page) => $page
                ->where('identity_document.application_id', null)
                ->where('identity_document.document', null)
            );
    }

    public function test_index_and_pdf(): void
    {
        $done    = $this->participant('NP-001');
        $pending = $this->participant('NP-002');

        $this->actingAs($this->admin)->post($this->url($done), [
            'items'            => ['B1' => ['status' => 'sesuai', 'catatan' => 'KTP dicek']],
            'kesimpulan_awal'  => 'tidak_layak',
            'catatan_awal'     => 'Ada orang lain di ruangan <script>alert(1)</script>',
            'kesimpulan_akhir' => 'tidak_layak',
            'pengawas_id'      => $this->admin->id,
        ]);

        $this->actingAs($this->admin)->get("/admin/penilaian/{$this->session->id}/verifikasi-tuk")
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Penilaian/VerifikasiTuk/Index')
                ->has('rows', 2)
                ->where('rows.0.student_id', $done->id)
                ->where('rows.0.kesimpulan_awal', 'tidak_layak')
                ->where('rows.1.student_id', $pending->id)
                ->where('rows.1.has_record', false)
            );

        $response = $this->actingAs($this->admin)->get($this->url($done, '/pdf'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());

        // Peserta yang belum punya checklist belum bisa diunduh.
        $this->actingAs($this->admin)->get($this->url($pending, '/pdf'))->assertNotFound();
    }
}
