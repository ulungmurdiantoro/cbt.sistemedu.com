<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\AssessmentApplication;
use App\Models\ExamSession;
use App\Models\Participant;
use App\Models\User;
use App\Models\UserRoleAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Maatwebsite\Excel\Facades\Excel;
use Tests\Feature\Concerns\CreatesExamFixtures;
use Tests\TestCase;

/** Tab "Permohonan" di navigasi sesi = daftar Permohonan yang difilter exam_session_id. */
class ApplicationSessionFilterTest extends TestCase
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

    private function application(ExamSession $session): AssessmentApplication
    {
        $participant = Participant::forceCreate([
            'name'     => 'Pemohon ' . Str::random(4),
            'email'    => Str::random(8) . '@example.com',
            'password' => bcrypt('password'),
        ]);

        return AssessmentApplication::forceCreate([
            'code'            => 'APL-' . Str::random(8),
            'participant_id'  => $participant->id,
            'classroom_id'    => $session->referenceExam->classroom_id,
            'exam_session_id' => $session->id,
            'kode_batch'      => '-',
            'tujuan_asesmen'  => 'Sertifikasi',
            'status'          => 'submitted',
        ]);
    }

    public function test_index_filters_by_session_and_sends_session_for_the_tabs(): void
    {
        $exam  = $this->makeExam($this->makeClassroom());
        $sesiA = $this->makeSession($exam);
        $sesiB = $this->makeSession($exam);
        $sesiA->forceFill(['kode_batch' => 'B07', 'verifikasi_tuk' => true])->save();

        $appA = $this->application($sesiA);
        $this->application($sesiB);

        $this->actingAs($this->admin())
            ->get("/admin/applications?exam_session_id={$sesiA->id}")
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Applications/Index')
                ->has('applications.data', 1)
                ->where('applications.data.0.id', $appA->id)
                ->where('exam_session.id', $sesiA->id)
                ->where('exam_session.kode_batch', 'B07')
                ->where('exam_session.verifikasi_tuk', true)
                ->where('filters.exam_session_id', (string) $sesiA->id)
            );
    }

    public function test_index_without_session_lists_everything(): void
    {
        $exam = $this->makeExam($this->makeClassroom());
        $this->application($this->makeSession($exam));
        $this->application($this->makeSession($exam));

        $this->actingAs($this->admin())
            ->get('/admin/applications')
            ->assertInertia(fn (Assert $page) => $page
                ->has('applications.data', 2)
                ->where('exam_session', null)
            );
    }

    public function test_excel_export_names_file_after_the_filtered_session(): void
    {
        Excel::fake();
        $this->travelTo(now()->setDateTime(2026, 10, 5, 9, 30, 0));

        $session = $this->makeSession($this->makeExam($this->makeClassroom('LEM')));
        $session->forceFill(['kode_batch' => 'B07'])->save();
        $this->application($session);

        $this->actingAs($this->admin())->get("/admin/applications/export?exam_session_id={$session->id}");

        Excel::assertDownloaded('permohonan-lem-batchb07-20261005-093000.xlsx');
    }

    public function test_document_zip_needs_a_scheme_or_a_session(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/applications/export-dokumen')
            ->assertStatus(422);
    }
}
