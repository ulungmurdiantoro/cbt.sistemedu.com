<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Exports\StudentsCertificationExport;
use App\Models\ParticipantResult;
use App\Models\User;
use App\Models\UserRoleAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Tests\Feature\Concerns\CreatesExamFixtures;
use Tests\TestCase;

/**
 * Export admin/students: hanya peserta yang pernah terdaftar di sesi ujian (akun aktif),
 * satu baris per peserta per sesi walau ada 2 baris exam_groups (PG + Esai).
 */
class StudentCertificationExportTest extends TestCase
{
    use RefreshDatabase;
    use CreatesExamFixtures;

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

    public function test_exports_one_row_per_participant_per_session(): void
    {
        Excel::fake();
        $this->travelTo(now()->startOfSecond()); // nama file memuat jam

        $classroom = $this->makeClassroom();
        $pg        = $this->makeExam($classroom);
        $esai      = $this->makeExam($classroom, 'Essay');
        $session   = $this->makeSession($pg, $esai);
        $other     = $this->makeSession($pg);

        $twice = $this->makeStudent($classroom);
        $this->enroll($twice, $pg, $session);
        $this->enroll($twice, $esai, $session);
        $this->enroll($twice, $pg, $other);

        $inactive = $this->makeStudent($classroom);
        $inactive->forceFill(['is_active' => false])->save();
        $this->enroll($inactive, $pg, $session);

        $this->makeStudent($classroom); // tidak pernah ikut sesi

        ParticipantResult::forceCreate([
            'exam_session_id'   => $session->id,
            'student_id'        => $twice->id,
            'nilai_akhir'       => 80,
            'keputusan'         => 'LULUS',
            'is_finalized'      => true,
            'sertifikat_number' => '003-010-10-2026-00001',
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.students.export'))
            ->assertOk();

        Excel::assertDownloaded('peserta_sertifikasi_' . now()->format('Ymd_His') . '.xlsx',
            function (StudentsCertificationExport $export) use ($twice, $session) {
                $rows = $export->collection();
                if ($rows->count() !== 2 || $rows->pluck('student_id')->unique()->all() !== [$twice->id]) {
                    return false;
                }

                $row = $export->map($rows->firstWhere('exam_session_id', $session->id));

                return $row[14] === 'LULUS' && $row[15] === 'Final' && $row[16] === '003-010-10-2026-00001';
            });
    }
}
