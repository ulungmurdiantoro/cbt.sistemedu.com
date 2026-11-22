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
 * Ujian Praktik/Keterampilan sebagai komponen nilai sendiri (Komposisi Nilai: bobot_praktik_1/2), mis. Lifting:
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
                ->has('rows', 1)
                ->where('rows.0.parts.0.score', null)
            );

        $this->nilai([1 => 80, 2 => 70])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame(2, PraktikScore::whereNotNull('score')->count());
        $this->get("/asesor/penilaian/{$this->ctx['session']->id}/praktik")->assertInertia(fn (Assert $page) => $page
            ->where('rows.0.parts.1.score', 70)
            ->where('rows.0.total', 76.25)
        );

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

        // Mengosongkan satu bagian menghapus nilainya; nilai praktik/keterampilan = bagian yang tersisa
        $this->nilai([1 => 80, 2 => null])->assertSessionHasNoErrors();
        $this->assertNull(PraktikScore::where('bagian', 2)->value('score'));
        $result = collect(app(ResultCalculatorService::class)->recalcForSession($this->ctx['session']))->first();
        $this->assertSame(80.0, $result->nilai_praktik);
    }

    public function test_practical_page_shows_rubric_and_interview_drops_worksheet(): void
    {
        Exam::whereKey($this->ctx['session']->exam_id_praktik)->update(['praktik_rubrik' => '<p><strong>C. RUBRIK STUDI KASUS 2</strong></p><table><tr><th>Aspek</th></tr></table>']);
        $this->ctx['session']->forceFill(['has_wawancara' => true])->save();

        // Rubrik hanya di halaman nilai per peserta, tidak di daftar
        $this->actingAs($this->ctx['asesor'])
            ->get("/asesor/penilaian/{$this->ctx['session']->id}/praktik")
            ->assertInertia(fn (Assert $page) => $page->missing('parts.0.rubrik'));
        $this->get("/asesor/penilaian/{$this->ctx['session']->id}/praktik/peserta/{$this->ctx['student']->id}")
            ->assertInertia(fn (Assert $page) => $page
                ->component('Asesor/Praktik/Nilai')
                ->where('parts.0.rubrik', fn ($html) => str_contains($html, 'RUBRIK STUDI KASUS 2'))
                ->where('parts.1.rubrik', null)
                ->where('nav.position', 1)
                ->where('nav.next_id', null)
            );

        // Keterampilan dinilai di halaman Praktik → wawancara = rata-rata 3 kriteria lisan, Worksheet diabaikan
        $this->get("/asesor/penilaian/{$this->ctx['session']->id}/wawancara")
            ->assertInertia(fn (Assert $page) => $page->where('praktik_terpisah', true));
        $this->post("/asesor/penilaian/{$this->ctx['session']->id}/wawancara", ['assessments' => [[
            'student_id' => $this->ctx['student']->id, 'gaya_wawancara' => 80, 'penguasaan_materi' => 70,
            'kemampuan_hadapi_pertanyaan' => 90, 'hasil_worksheet' => 10,
        ]]])->assertSessionHasNoErrors();

        $ia = \App\Models\InterviewAssessment::firstOrFail();
        $this->assertNull($ia->hasil_worksheet);
        $this->assertSame(80.0, (float) $ia->total_nilai);
    }

    public function test_part_scored_per_rubric_aspect(): void
    {
        // Bagian 1 dinilai per aspek (mis. Corporate Legal Studi Kasus 2): bobot 60 + 40
        $kriteria = [['min' => 90, 'max' => 100, 'label' => 'Sangat Baik', 'deskripsi' => 'Lengkap']];
        Exam::whereKey($this->ctx['session']->exam_id_praktik)->firstOrFail()->update(['praktik_aspek' => [
            ['no' => 1, 'nama' => 'Struktur', 'bobot' => 60, 'kriteria' => $kriteria],
            ['no' => 2, 'nama' => 'Ruang lingkup', 'bobot' => 40, 'kriteria' => $kriteria],
        ]]);
        $url = "/asesor/penilaian/{$this->ctx['session']->id}/praktik";
        $gradeUrl = "{$url}/peserta/{$this->ctx['student']->id}";
        $post = fn (array $aspek, array $nilai = [], array $catatan = []) => $this->actingAs($this->ctx['asesor'])->post($url, [
            'scores' => [['student_id' => $this->ctx['student']->id, 'nilai' => $nilai, 'aspek' => [1 => $aspek], 'catatan' => [1 => $catatan]]],
        ]);

        $this->actingAs($this->ctx['asesor'])->get($gradeUrl)->assertInertia(fn (Assert $page) => $page
            ->has('parts.0.aspek', 2)
            ->where('parts.0.aspek.0.bobot', 60)
            ->where('parts.0.aspek.0.kriteria.0.label', 'Sangat Baik')
            ->where('parts.1.aspek', [])
        );
        $this->get($url)->assertInertia(fn (Assert $page) => $page->where('parts.0.aspek_count', 2));

        // Belum lengkap: nilai aspek tersimpan, nilai bagian belum dihitung (nilai langsung dari browser diabaikan)
        $post([1 => 80, 2 => null], [1 => 99, 2 => 70], [1 => 'Format lengkap'])->assertSessionHasNoErrors();
        $ps = PraktikScore::where('bagian', 1)->firstOrFail();
        $this->assertNull($ps->score);
        $this->assertSame(80, (int) $ps->aspek[1]);
        $this->assertSame('Format lengkap', $ps->catatan[1]);
        $this->get($url)->assertInertia(fn (Assert $page) => $page->where('rows.0.parts.0.filled', 1));

        // Lengkap: 80 × 60% + 90 × 40% = 84
        $post([1 => 80, 2 => 90], [2 => 70])->assertSessionHasNoErrors();
        $this->assertSame(84.0, PraktikScore::where('bagian', 1)->value('score'));
        $this->get($gradeUrl)->assertInertia(fn (Assert $page) => $page
            ->where('parts.0.nilai.2', 90)
            ->where('parts.0.score', 84)
        );

        $post([1 => 120])->assertSessionHasErrors();
    }

    public function test_lifting_aspects_scored_per_item(): void
    {
        // Lifting Calculation: B1 (2 butir × 50) bobot 60 + B2 (1 butir × 100) bobot 40 — pilihan Ada & Benar / Salah / Tidak Ada
        $opsi = fn (int $max) => [['label' => 'Ada & Benar', 'nilai' => $max], ['label' => 'Ada tapi Salah', 'nilai' => $max / 2], ['label' => 'Tidak Ada', 'nilai' => 0]];
        Exam::whereKey($this->ctx['session']->exam_id_praktik)->firstOrFail()->update(['praktik_aspek' => [
            ['no' => 1, 'nama' => 'B1', 'bobot' => 60, 'item' => [['no' => 1, 'nama' => 'Load Factor'], ['no' => 2, 'nama' => 'Working Radius']], 'pilihan' => $opsi(50)],
            ['no' => 2, 'nama' => 'B2', 'bobot' => 40, 'item' => [['no' => 1, 'nama' => 'Site Preparation']], 'pilihan' => $opsi(100)],
        ]]);
        $url = "/asesor/penilaian/{$this->ctx['session']->id}/praktik";
        $post = fn (array $rincian) => $this->actingAs($this->ctx['asesor'])->post($url, [
            'scores' => [['student_id' => $this->ctx['student']->id, 'rincian' => [1 => $rincian]]],
        ]);

        // Belum semua butir dipilih: pilihan tersimpan, nilai aspek & bagian belum ada
        $post([1 => [1 => 50]])->assertSessionHasNoErrors();
        $ps = PraktikScore::where('bagian', 1)->firstOrFail();
        $this->assertNull($ps->score);
        $this->assertNull($ps->aspek[1]);
        $this->assertSame(50, (int) $ps->rincian[1][1]);

        // Lengkap: B1 = 50 + 25 = 75, B2 = 100 → 75 × 60% + 100 × 40% = 85
        $post([1 => [1 => 50, 2 => 25], 2 => [1 => 100]])->assertSessionHasNoErrors();
        $ps->refresh();
        $this->assertSame(75, (int) $ps->aspek[1]);
        $this->assertSame(85.0, $ps->score);
        $this->get("{$url}/peserta/{$this->ctx['student']->id}")->assertInertia(fn (Assert $page) => $page
            ->has('parts.0.aspek.0.item', 2)
            ->where('parts.0.rincian.1.2', 25)
        );

        // Nilai di luar pilihan ditolak
        $post([1 => [1 => 37]])->assertStatus(422);
    }

    public function test_failed_critical_gate_caps_aspect_at_50(): void
    {
        // ESG: Tugas 1 & 2 (bobot 50/50) dengan gerbang kritis — gagal → nilai aspek maks. 50
        $aspek = fn (int $no) => ['no' => $no, 'nama' => "Tugas {$no}", 'bobot' => 50, 'poin_kunci' => ['Poin'],
            'gerbang' => ['Warga sekitar tidak dipetakan'], 'maks_gerbang' => 50];
        Exam::whereKey($this->ctx['session']->exam_id_praktik)->firstOrFail()->update(['praktik_aspek' => [$aspek(1), $aspek(2)]]);
        $url = "/asesor/penilaian/{$this->ctx['session']->id}/praktik";
        $post = fn (array $nilai, array $rincian) => $this->actingAs($this->ctx['asesor'])->post($url, [
            'scores' => [['student_id' => $this->ctx['student']->id, 'aspek' => [1 => $nilai], 'rincian' => [1 => $rincian]]],
        ]);

        // Gerbang gagal tapi nilai 80 → ditolak
        $post([1 => 80, 2 => 90], [1 => ['gerbang' => true]])->assertStatus(422);

        $post([1 => 45, 2 => 90], [1 => ['gerbang' => true], 2 => ['gerbang' => false]])->assertSessionHasNoErrors();
        $ps = PraktikScore::where('bagian', 1)->firstOrFail();
        $this->assertSame(67.5, $ps->score);
        $this->assertTrue($ps->rincian[1]['gerbang']);
        $this->assertArrayNotHasKey(2, $ps->rincian);
        $this->get("{$url}/peserta/{$this->ctx['student']->id}")->assertInertia(fn (Assert $page) => $page
            ->where('parts.0.aspek.0.gerbang.0', 'Warga sekitar tidak dipetakan')
            ->where('parts.0.rincian.1.gerbang', true)
        );
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
        // Tanpa nilai praktik/keterampilan yang ditunggu, tugas asesor di sesi ini lengkap → Sesi Selesai, tanpa tombol Praktik
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

        // Lifting: PG + 2 praktik saja → tanpa tombol Esai & Wawancara; Wawancara muncul bila sesi mengaktifkannya
        $semula = $this->ctx['session']->only('exam_id_esai', 'has_wawancara');
        $this->ctx['session']->update(['exam_id_esai' => null, 'has_wawancara' => false]);
        $this->get('/asesor/dashboard')->assertInertia(fn (Assert $page) => $page
            ->where('active_sessions.0.show_esai', false)
            ->where('active_sessions.0.show_wawancara', false)
        );
        $this->ctx['session']->update(['has_wawancara' => true]);
        $this->get('/asesor/dashboard')->assertInertia(fn (Assert $page) => $page
            ->where('active_sessions.0.show_wawancara', true)
        );
        $this->ctx['session']->update($semula);
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
