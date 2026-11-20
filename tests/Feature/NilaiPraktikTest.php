<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AsesorAssignment;
use App\Models\Classroom;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\Grade;
use App\Models\GradingScheme;
use App\Models\PraktikScore;
use App\Models\Student;
use App\Models\User;
use App\Models\UserRoleAssignment;
use App\Services\ResultCalculatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Feature\Concerns\CreatesExamFixtures;
use Tests\TestCase;

/**
 * Ujian Praktik sebagai komponen nilai sendiri (Komposisi Nilai: bobot_praktik_1/2), mis. Lifting:
 * PG 20% + Praktek Calculation 50% + Praktek Gambar 30%, lulus ≥ 70. Asesor mengisi nilai per bagian.
 */
class NilaiPraktikTest extends TestCase
{
    use RefreshDatabase, CreatesExamFixtures;

    private array $ctx;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        $classroom = $this->makeClassroom('LHC');
        $pg        = $this->makeExam($classroom);
        $calc      = $this->praktikExam($classroom, 'Lifting - Calculation', 300);
        $gambar    = $this->praktikExam($classroom, 'Lifting - Gambar', 180);
        $session   = $this->makeSession($pg);
        // ID sesi ≥ batas progres dashboard asesor (Asesor\DashboardController::PROGRESS_FROM_SESSION_ID)
        ExamSession::whereKey($session->id)->update(['id' => 100]);
        $session = ExamSession::findOrFail(100);
        $session->forceFill(['exam_id_praktik' => $calc->id, 'exam_id_praktik_2' => $gambar->id])->save();

        GradingScheme::forceCreate([
            'classroom_id'      => $classroom->id,
            'bobot_ujian_tulis' => 20,
            'proporsi_pg'       => 100,
            'bobot_pg'          => 20,
            'bobot_esai'        => 0,
            'bobot_wawancara'   => 0,
            'bobot_praktik_1'   => 50,
            'bobot_praktik_2'   => 30,
            'nilai_kelulusan'   => 70,
        ]);

        $asesor  = $this->user(UserRole::Asesor);
        $student = $this->makeStudent($classroom);
        $this->enroll($student, $pg, $session);
        Grade::where('student_id', $student->id)->update(['grade' => 90, 'start_time' => now(), 'end_time' => now()]);
        AsesorAssignment::forceCreate(['user_id' => $asesor->id, 'exam_session_id' => $session->id, 'student_id' => $student->id]);

        $this->ctx = compact('classroom', 'session', 'asesor', 'student');
    }

    private function praktikExam(Classroom $classroom, string $title, int $durasi): Exam
    {
        $exam = $this->makeExam($classroom, Exam::TYPE_PRAKTIK, $durasi);
        $exam->forceFill(['title' => $title, 'praktik_panduan' => 'Panduan', 'praktik_template_path' => 'praktik_templates/x.zip'])->save();

        return $exam;
    }

    private function user(UserRole $role): User
    {
        $user = User::forceCreate([
            'users_code' => 'U-' . Str::random(6),
            'name'       => 'User ' . $role->value,
            'email'      => Str::random(8) . '@example.com',
            'password'   => bcrypt('password'),
        ]);
        UserRoleAssignment::forceCreate(['user_id' => $user->id, 'role' => $role->value]);

        return $user;
    }

    private function nilai(array $nilai)
    {
        return $this->actingAs($this->ctx['asesor'])->post("/asesor/penilaian/{$this->ctx['session']->id}/praktik", [
            'scores' => [['student_id' => $this->ctx['student']->id, 'nilai' => $nilai]],
        ]);
    }

    public function test_asesor_scores_each_part_and_final_uses_20_50_30(): void
    {
        $this->actingAs($this->ctx['asesor'])
            ->get("/asesor/penilaian/{$this->ctx['session']->id}/praktik")
            ->assertInertia(fn (Assert $page) => $page
                ->component('Asesor/Praktik/Show')
                ->has('parts', 2)
                ->where('parts.0.title', 'Lifting - Calculation')
                ->where('parts.0.bobot', 50)
                ->where('parts.1.bobot', 30)
                ->has('students', 1)
            );

        $this->nilai([1 => 80, 2 => 70])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame(2, PraktikScore::whereNotNull('score')->count());

        $result = collect(app(ResultCalculatorService::class)->recalcForSession($this->ctx['session']))->first();
        // Praktik = (80×50 + 70×30) / 80 = 76,25; akhir = 90×0,2 + 80×0,5 + 70×0,3 = 79 → LULUS (≥ 70)
        $this->assertSame(76.25, $result->nilai_praktik);
        $this->assertSame(79.0, (float) $result->nilai_akhir);
        $this->assertSame('LULUS', $result->keputusan);

        // Gambar rendah → tidak lulus
        $this->nilai([1 => 60, 2 => 40])->assertSessionHasNoErrors();
        $result = collect(app(ResultCalculatorService::class)->recalcForSession($this->ctx['session']))->first();
        $this->assertSame(60.0, (float) $result->nilai_akhir); // 18 + 30 + 12
        $this->assertSame('TIDAK_LULUS', $result->keputusan);

        // Mengosongkan satu bagian menghapus nilainya; nilai praktik = bagian yang tersisa
        $this->nilai([1 => 80, 2 => null])->assertSessionHasNoErrors();
        $this->assertNull(PraktikScore::where('bagian', 2)->value('score'));
        $result = collect(app(ResultCalculatorService::class)->recalcForSession($this->ctx['session']))->first();
        $this->assertSame(80.0, $result->nilai_praktik);
    }

    public function test_scores_are_validated_and_limited_to_assigned_participants(): void
    {
        $this->nilai([1 => 120])->assertSessionHasErrors();

        $other = $this->makeStudent($this->ctx['classroom']);
        $this->actingAs($this->ctx['asesor'])->post("/asesor/penilaian/{$this->ctx['session']->id}/praktik", [
            'scores' => [['student_id' => $other->id, 'nilai' => [1 => 80]]],
        ])->assertForbidden();

        $this->actingAs($this->user(UserRole::Asesor))
            ->get("/asesor/penilaian/{$this->ctx['session']->id}/praktik")
            ->assertForbidden();
        $this->assertSame(0, PraktikScore::count());
    }

    public function test_page_is_not_used_when_scheme_has_no_praktik_weight(): void
    {
        GradingScheme::where('classroom_id', $this->ctx['classroom']->id)
            ->update(['bobot_praktik_1' => 0, 'bobot_praktik_2' => 0, 'bobot_wawancara' => 80]);

        $this->actingAs($this->ctx['asesor'])
            ->get("/asesor/penilaian/{$this->ctx['session']->id}/praktik")
            ->assertNotFound();
        // Tanpa nilai praktik yang ditunggu, tugas asesor di sesi ini lengkap → Sesi Selesai, tanpa tombol Praktik
        $this->get('/asesor/dashboard')->assertInertia(fn (Assert $page) => $page
            ->where('completed_sessions.0.praktik_weights', [])
            ->missing('completed_sessions.0.progress.items.praktik')
        );
    }

    public function test_rekap_dashboard_and_review_show_praktik(): void
    {
        $this->nilai([1 => 80])->assertSessionHasNoErrors();

        // Asesor: tombol Praktik + progres (Gambar belum dinilai)
        $this->get('/asesor/dashboard')->assertInertia(fn (Assert $page) => $page
            ->where('active_sessions.0.praktik_weights', ['1' => 50, '2' => 30])
            ->where('active_sessions.0.progress.items.praktik', ['done' => 0, 'total' => 1])
        );
        $this->get("/asesor/penilaian/{$this->ctx['session']->id}/rekap")->assertInertia(fn (Assert $page) => $page
            ->where('scheme.bobot_praktik', 80)
            ->where('rows.0.nilai_praktik', 80)
        );

        // Admin Rekap Hasil: kolom Praktik + rincian per bagian; tahap Nilai menunggu praktik
        $this->actingAs($this->user(UserRole::Admin))
            ->get("/admin/results/{$this->ctx['session']->id}")
            ->assertInertia(fn (Assert $page) => $page
                ->has('praktik_parts', 2)
                ->where('praktik_parts.1.title', 'Lifting - Gambar')
                ->where('rows.0.nilai_praktik', 80)
                ->where('rows.0.praktik.1', 80)
                ->where('rows.0.progress.steps.nilai.state', 'pending')
                ->where('rows.0.progress.steps.nilai.note', 'Belum dinilai: praktik')
            );

        $this->nilai([1 => 80, 2 => 70])->assertSessionHasNoErrors();
        $this->actingAs($this->user(UserRole::Admin))
            ->get("/admin/results/{$this->ctx['session']->id}")
            ->assertInertia(fn (Assert $page) => $page
                ->where('rows.0.nilai_praktik', 76.25)
                ->where('rows.0.progress.steps.nilai.state', 'done')
            );
    }

    public function test_grading_scheme_form_saves_praktik_weights(): void
    {
        $admin = $this->user(UserRole::Admin);
        $url = "/admin/classrooms/{$this->ctx['classroom']->id}/grading-scheme";

        $this->actingAs($admin)->post($url, [
            'bobot_ujian_tulis' => 20, 'proporsi_pg' => 100, 'bobot_wawancara' => 0,
            'bobot_praktik_1' => 50, 'bobot_praktik_2' => 20, 'nilai_kelulusan' => 70,
        ])->assertSessionHasErrors('bobot_ujian_tulis');

        $this->actingAs($admin)->post($url, [
            'bobot_ujian_tulis' => 20, 'proporsi_pg' => 100, 'bobot_wawancara' => 0,
            'bobot_praktik_1' => 50, 'bobot_praktik_2' => 30, 'nilai_kelulusan' => 70,
        ])->assertSessionHasNoErrors();

        $scheme = GradingScheme::where('classroom_id', $this->ctx['classroom']->id)->first();
        $this->assertSame([20.0, 0.0, 50.0, 30.0], [$scheme->bobot_pg, $scheme->bobot_esai, $scheme->bobot_praktik_1, $scheme->bobot_praktik_2]);
    }
}
