<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Grade;
use App\Models\InterviewAssessment;
use App\Models\ParticipantResult;
use App\Models\User;
use App\Models\UserRoleAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Feature\Concerns\CreatesExamFixtures;
use Tests\TestCase;

/** Rekap Hasil & daftar Hasil Penilaian menampilkan progres setiap peserta setelah ujian. */
class ResultProgressTest extends TestCase
{
    use RefreshDatabase;
    use CreatesExamFixtures;

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

    public function test_progress_steps_waiting_stage_and_timeline_per_participant(): void
    {
        $classroom = $this->makeClassroom();
        $exam      = $this->makeExam($classroom);
        $session   = $this->makeSession($exam);
        $session->forceFill(['has_wawancara' => true, 'start_time' => now()->subDays(2), 'end_time' => now()->subDay()])->save();

        $done    = $this->makeStudent($classroom);
        $partial = $this->makeStudent($classroom);
        $absent  = $this->makeStudent($classroom);
        $done->forceFill(['no_participant' => 'NP-001'])->save();
        $partial->forceFill(['no_participant' => 'NP-002'])->save();
        $absent->forceFill(['no_participant' => 'NP-003'])->save();
        [, $gDone]    = $this->enroll($done, $exam, $session);
        [, $gPartial] = $this->enroll($partial, $exam, $session);
        $this->enroll($absent, $exam, $session);

        $gDone->forceFill(['start_time' => now()->subDays(2), 'end_time' => now()->subDays(2)->addHour()])->save();
        $gPartial->forceFill(['start_time' => now()->subDays(2)])->save();

        $asesor = $this->user('Bu Asesor', UserRole::Asesor);
        $pk     = $this->user('Pak PK', UserRole::ManagerSertifikasi);
        InterviewAssessment::create([
            'exam_session_id' => $session->id, 'student_id' => $done->id, 'asesor_id' => $asesor->id,
            'gaya_wawancara' => 80, 'penguasaan_materi' => 80, 'kemampuan_hadapi_pertanyaan' => 80,
            'hasil_worksheet' => 80, 'total_nilai' => 80,
        ])->forceFill(['updated_at' => now()->subHours(22)])->saveQuietly();

        // Sesi lama: difinalisasi tanpa centang Manajer Sertifikasi → MS "tidak tercatat", bukan ditunggu.
        ParticipantResult::forceCreate([
            'exam_session_id'     => $session->id,
            'student_id'          => $done->id,
            'keputusan'           => 'LULUS',
            'manager_verified_at' => now()->subHours(20),
            'manager_verified_by' => $pk->id,
            'is_finalized'        => true,
            'finalized_at'        => now()->subHours(19),
            'finalized_by'        => $pk->id,
            'sp_number'           => 'SP-1',
            'sp_distributed_at'   => now()->subHours(18),
        ]);

        $this->actingAs($this->user('Admin', UserRole::Admin))
            ->get("/admin/results/{$session->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Results/Show')
                ->has('steps.pk')
                // NP-001: semua sampai SP selesai → menunggu kirim SK
                ->where('rows.0.student_id', $done->id)
                ->where('rows.0.progress.steps.ujian.state', 'done')
                ->where('rows.0.progress.steps.nilai.state', 'done')
                ->where('rows.0.progress.steps.ak01.state', 'na')
                ->where('rows.0.progress.steps.ms.state', 'skipped')
                ->where('rows.0.progress.steps.pk.by', 'Pak PK')
                ->where('rows.0.progress.stage', 'sk')
                ->where('rows.0.progress.waiting', 'Siap kirim SK & Sertifikat')
                ->where('rows.0.progress.timeline', fn ($events) => collect($events)->pluck('title')->values()->all() === [
                    'Mulai ujian PG', 'Selesai ujian PG', 'Wawancara dinilai',
                    'Diverifikasi Pengambil Keputusan', 'Difinalisasi', 'SP terkirim ke peserta',
                ])
                // NP-002: mulai tapi belum selesai ujian
                ->where('rows.1.progress.steps.ujian.state', 'progress')
                ->where('rows.1.progress.stage', 'ujian')
                ->where('rows.1.progress.waiting', 'Belum menyelesaikan ujian')
                // NP-003: tidak ikut ujian → penilaian tidak berlaku
                ->where('rows.2.progress.absent', true)
                ->where('rows.2.progress.steps.ujian.state', 'absent')
                ->where('rows.2.progress.steps.nilai.state', 'na')
                ->where('rows.2.progress.stage', 'pk')
            );
    }

    public function test_session_list_shows_pipeline_per_session(): void
    {
        $classroom = $this->makeClassroom();
        $exam      = $this->makeExam($classroom);
        $session   = $this->makeSession($exam);
        $student   = $this->makeStudent($classroom);
        [, $grade] = $this->enroll($student, $exam, $session);
        $grade->forceFill(['start_time' => now()->subMinutes(30)])->save();

        $this->actingAs($this->user('Admin', UserRole::Admin))
            ->get('/admin/results')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Results/Index')
                ->where('sessions.data.0.pipeline.total', 1)
                ->where('sessions.data.0.pipeline.state', 'berlangsung')
                ->where('sessions.data.0.pipeline.progress.started', 1)
                ->where('sessions.data.0.pipeline.progress.final', 0)
            );
    }
}
