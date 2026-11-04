<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\InterviewAssessment;
use App\Models\User;
use App\Models\UserRoleAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;
use Tests\Feature\Concerns\CreatesExamFixtures;
use Tests\TestCase;

/** Rekap Hasil admin (admin/results/{sesi}) mengirim rincian 4 kriteria wawancara per peserta. */
class ResultWawancaraBreakdownTest extends TestCase
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

    public function test_interview_breakdown_is_sent_per_participant(): void
    {
        $classroom = $this->makeClassroom();
        $exam      = $this->makeExam($classroom);
        $session   = $this->makeSession($exam);
        $session->forceFill(['has_wawancara' => true])->save();
        $assessed  = $this->makeStudent($classroom);
        $pending   = $this->makeStudent($classroom);
        $this->enroll($assessed, $exam, $session);
        $this->enroll($pending, $exam, $session);

        $asesor = $this->user('Pak Asesor', UserRole::Asesor);
        InterviewAssessment::create([
            'exam_session_id'             => $session->id,
            'student_id'                  => $assessed->id,
            'asesor_id'                   => $asesor->id,
            'gaya_wawancara'              => 88,
            'penguasaan_materi'           => 86,
            'kemampuan_hadapi_pertanyaan' => 86,
            'hasil_worksheet'             => 88,
            'total_nilai'                 => 87,
            'catatan'                     => 'Menguasai prosedur.',
        ]);

        $this->actingAs($this->user('Admin Test', UserRole::Admin))
            ->get("/admin/results/{$session->id}")
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Results/Show')
                ->where('rows', function ($rows) use ($assessed, $pending) {
                    $w = collect($rows)->firstWhere('student_id', $assessed->id)['wawancara'];

                    return (float) $w['gaya_wawancara'] === 88.0
                        && (float) $w['penguasaan_materi'] === 86.0
                        && (float) $w['kemampuan_hadapi_pertanyaan'] === 86.0
                        && (float) $w['hasil_worksheet'] === 88.0
                        && (float) $w['total_nilai'] === 87.0
                        && $w['catatan'] === 'Menguasai prosedur.'
                        && $w['asesor_name'] === 'Pak Asesor'
                        && collect($rows)->firstWhere('student_id', $pending->id)['wawancara'] === null;
                }));
    }
}
