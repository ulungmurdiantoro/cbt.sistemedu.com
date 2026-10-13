<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\AssessmentApplication;
use App\Models\Classroom;
use App\Models\InitialAssessment;
use App\Models\Participant;
use App\Models\User;
use App\Models\UserRoleAssignment;
use App\Support\InitialAssessmentRubric;
use App\Support\InitialAssessmentRubric\Rev04;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Feature\Concerns\CreatesExamFixtures;
use Tests\TestCase;

/** Penilaian Awal Kelayakan (FR.APL.03): Rev.04 untuk penilaian baru, Rev.03 tetap untuk yang lama. */
class InitialAssessmentTest extends TestCase
{
    use RefreshDatabase, CreatesExamFixtures;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::forceCreate([
            'users_code' => 'U-' . Str::random(6),
            'name'       => 'Admin Test',
            'email'      => Str::random(8) . '@example.com',
            'password'   => bcrypt('password'),
        ]);
        UserRoleAssignment::forceCreate(['user_id' => $this->admin->id, 'role' => UserRole::Admin->value]);
    }

    /** Rubrik dipetakan lewat classroom_id produksi, jadi kelas dibuat dengan ID tsb. */
    private function application(int $classroomId, string $status = 'submitted'): AssessmentApplication
    {
        $classroom = Classroom::forceCreate(['id' => $classroomId, 'classrooms_code' => 'C' . $classroomId, 'title' => 'Skema ' . $classroomId]);

        return AssessmentApplication::forceCreate([
            'exam_session_id' => $this->makeSession($this->makeExam($classroom))->id,
            'code'           => 'APL-' . Str::random(8),
            'participant_id' => Participant::forceCreate([
                'name'     => 'Pemohon',
                'email'    => Str::random(8) . '@example.com',
                'password' => bcrypt('password'),
            ])->id,
            'classroom_id'   => $classroomId,
            'kode_batch'     => '-',
            'tujuan_asesmen' => 'Sertifikasi',
            'status'         => $status,
        ]);
    }

    private function save(AssessmentApplication $application, array $answers)
    {
        return $this->actingAs($this->admin)->post("/admin/applications/{$application->id}/penilaian-awal", ['answers' => $answers]);
    }

    public function test_new_assessment_uses_rev04_criteria_and_threshold(): void
    {
        // Lead Auditor SPMI: Rev.03 minimal 10, Rev.04 minimal 7.
        $application = $this->application(7);

        $this->save($application, [
            'pendidikan'       => 's2',            // 2
            'pengalaman_kerja' => 'asisten_dosen', // 1 (opsi baru Rev.04)
            'durasi_pm'        => 'gt10',          // 4 (Rev.03: 5)
            'durasi_dosen'     => 'lt1',           // 1 (opsi baru Rev.04)
            'pelatihan'        => 'tidak_relevan', // 1
        ])->assertSessionHasNoErrors();

        $assessment = $application->initialAssessment()->firstOrFail();
        $this->assertSame('Rev.04', $assessment->revision);
        $this->assertSame(9, (int) $assessment->total_score);
        $this->assertSame(7, (int) $assessment->threshold);
        $this->assertTrue($assessment->is_eligible);
        $this->assertStringContainsString('memenuhi persyaratan dasar', $assessment->result_sentence);
    }

    public function test_answers_are_filtered_to_the_rubric_and_sebutkan_kept_only_for_selected_lainnya(): void
    {
        // Lab ISO/IEC 17025: pelatihan multi + opsi "lainnya sebutkan".
        $application = $this->application(3);

        $this->save($application, [
            'pendidikan'         => 'd4_s1',                         // 2
            'prodi'              => 'sains',                         // 2
            'pengalaman_kerja'   => 'lainnya',                       // 1
            'durasi_kerja'       => 'y1_5',                          // 1
            'pelatihan_topik'    => ['iso17025', 'bogus', 'lainnya'], // 2 + 0
            'pengalaman_auditor' => [],
            'tidak_dikenal'      => 'x',
            'sebutkan'           => [
                'pengalaman_kerja' => '  Koordinator Lab  ',
                'pelatihan_topik'  => 'K3 Laboratorium',
                'durasi_kerja'     => 'diabaikan',
            ],
        ])->assertSessionHasNoErrors();

        $assessment = $application->initialAssessment()->firstOrFail();
        $this->assertSame(8, (int) $assessment->total_score);
        $this->assertEquals([
            'pendidikan'         => 'd4_s1',
            'prodi'              => 'sains',
            'pengalaman_kerja'   => 'lainnya',
            'durasi_kerja'       => 'y1_5',
            'pelatihan_topik'    => ['iso17025', 'lainnya'],
            'pengalaman_auditor' => [],
            'sebutkan'           => ['pengalaman_kerja' => 'Koordinator Lab', 'pelatihan_topik' => 'K3 Laboratorium'],
        ], $assessment->answers);
    }

    public function test_unanswered_single_criterion_is_rejected(): void
    {
        $application = $this->application(20);

        $this->save($application, ['pendidikan' => 's2_plus', 'prodi' => 'bukan-opsi'])
            ->assertSessionHasErrors('answers');

        $this->assertNull($application->initialAssessment()->first());
    }

    public function test_existing_rev03_assessment_keeps_its_score_and_pdf_until_reassessed(): void
    {
        $application = $this->application(7, 'approved');

        // Baris lama tanpa kolom revision → default migrasi Rev.03.
        $old = InitialAssessment::forceCreate([
            'assessment_application_id' => $application->id,
            'classroom_id'              => 7,
            'answers'                   => ['pendidikan' => 's3', 'pengalaman_kerja' => 'pimpinan_pm', 'durasi_pm' => 'gt10', 'durasi_dosen' => 'gt10', 'pelatihan' => 'relevan'],
            'total_score'               => 21,
            'threshold'                 => 10,
            'is_eligible'               => true,
            'assessed_by'               => $this->admin->id,
            'assessed_at'               => now(),
        ])->fresh();

        $this->assertSame('Rev.03', $old->revision);
        $this->assertSame(10, $old->rubric()['threshold']);
        $this->assertSame('Nilai 21 (≥ 10) = bisa langsung uji kompetensi', $old->result_sentence);

        $this->actingAs($this->admin)->get("/admin/applications/{$application->id}")
            ->assertInertia(fn (Assert $page) => $page
                ->where('application.initial_assessment.revision', 'Rev.03')
                ->where('initial_assessment_rubric.revision', InitialAssessmentRubric::CURRENT));

        $this->actingAs($this->admin)->get("/admin/applications/{$application->id}/fr-apl-03")
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');

        // "Ubah Penilaian" → disimpan ulang dengan revisi terbaru.
        $this->save($application, [
            'pendidikan' => 's3', 'pengalaman_kerja' => 'pimpinan_pm', 'durasi_pm' => 'gt10',
            'durasi_dosen' => 'gt10', 'pelatihan' => 'relevan',
        ])->assertSessionHasNoErrors();

        $new = $old->fresh();
        $this->assertSame('Rev.04', $new->revision);
        $this->assertSame(20, (int) $new->total_score);
        $this->assertSame(7, (int) $new->threshold);
    }

    public function test_rev04_pdf_renders(): void
    {
        $application = $this->application(13);

        $this->save($application, [
            'pendidikan' => 'd3_teknik', 'prodi' => 'teknik', 'pengalaman_kerja' => 'level_3',
            'durasi_kerja' => 'm6_y1', 'pelatihan' => [],
        ])->assertSessionHasNoErrors();
        $this->assertSame(8, (int) $application->initialAssessment()->firstOrFail()->total_score);

        $this->actingAs($this->admin)->get("/admin/applications/{$application->id}/fr-apl-03")
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    /** Semua 26 skema punya halaman Rev.04 sendiri dan rubriknya konsisten. */
    public function test_rev04_rubric_covers_every_scheme_consistently(): void
    {
        $map = Rev04::map();
        $this->assertSame(range(3, 28), collect(array_keys($map))->sort()->values()->all());

        foreach ($map + ['fallback' => Rev04::fallback()] as $id => $rubric) {
            $keys = array_column($rubric['criteria'], 'key');
            $this->assertSame($keys, array_unique($keys), "Kunci kriteria dobel di {$id}");
            $this->assertContains($rubric['threshold'], [6, 7], "Threshold {$id}");

            $max = 0;
            foreach ($rubric['criteria'] as $criterion) {
                $optionKeys = array_column($criterion['options'], 'key');
                $this->assertSame($optionKeys, array_unique($optionKeys), "Kunci opsi dobel di {$id}/{$criterion['key']}");
                $scores = array_map(fn ($o) => $o['score'] ?? 0, $criterion['options']);
                $max += $criterion['type'] === 'multi' ? array_sum($scores) : max($scores);
            }
            $this->assertGreaterThanOrEqual($rubric['threshold'], $max, "Skor maksimum {$id} di bawah threshold");
        }

        $this->assertSame(4, $map[15]['criteria'][2]['options'][0]['score']); // 2D Lifting Designer: 4 jenjang
        $this->assertSame(['Corporate Legal Officer'], $map[28]['ruang_lingkup']);

        // Semua skema Lifting: pelatihan hanya "Relevan" (centang opsional).
        foreach ([13, 14, 15, 16] as $id) {
            $pelatihan = collect($map[$id]['criteria'])->firstWhere('key', 'pelatihan');
            $this->assertSame('multi', $pelatihan['type'], "Pelatihan lifting {$id}");
            $this->assertSame(['relevan'], array_column($pelatihan['options'], 'key'));
        }

        // Daftar jabatan sesuai dokumen R4-1 (halaman 18–20).
        $topJabatan = fn (int $id) => collect($map[$id]['criteria'])->firstWhere('key', 'pengalaman_kerja')['options'][0]['label'];
        $this->assertStringStartsWith('Head of Sustainability', $topJabatan(25));
        $this->assertStringStartsWith('Head of ESG', $topJabatan(26));
        $this->assertStringStartsWith('Environmental Manager', $topJabatan(27));
    }
}
