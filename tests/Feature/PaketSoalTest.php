<?php

namespace Tests\Feature;

use App\Models\Essay;
use App\Models\Exam;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Feature\Concerns\CreatesExamFixtures;
use Tests\TestCase;

/**
 * paket-soal:pasang memasang paket soal V2 (database/data/paket-soal/v2) sebagai ujian baru per skema:
 * PG + Esai (Lifting tanpa esai) + Ujian Praktik, tanpa import dan tanpa menyentuh ujian lama.
 */
class PaketSoalTest extends TestCase
{
    use RefreshDatabase, CreatesExamFixtures;

    private string $templates;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::fake('private');

        $this->templates = sys_get_temp_dir() . '/paket-soal-test-' . Str::random(6);
        File::ensureDirectoryExists($this->templates);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->templates);
        parent::tearDown();
    }

    private function classrooms(): void
    {
        foreach (['LHC', 'K3L', 'CLO', 'ESG'] as $code) {
            $this->makeClassroom($code);
        }
    }

    private function templateFiles(): void
    {
        foreach (['lifting-calculation.zip', 'lifting-gambar.zip', 'hse.docx', 'legal.docx', 'esg.zip'] as $f) {
            File::put("{$this->templates}/{$f}", "isi {$f}");
        }
    }

    private function pasang(array $options = []): \Illuminate\Testing\PendingCommand
    {
        return $this->artisan('paket-soal:pasang', ['versi' => 'v2', '--force' => true, '--templates' => $this->templates] + $options);
    }

    public function test_data_paket_v2_lolos_pemeriksaan_dan_dry_run_tidak_menyimpan(): void
    {
        $this->classrooms();

        $this->pasang(['--dry-run' => true])
            ->expectsOutputToContain('Data paket lolos pemeriksaan')
            ->assertSuccessful();

        $this->assertSame(0, Exam::count());
    }

    public function test_memasang_pg_esai_dan_praktik_per_skema(): void
    {
        $this->classrooms();
        $this->templateFiles();

        $this->pasang()->assertSuccessful();

        foreach (['lifting', 'hse', 'legal', 'esg'] as $key) {
            $pg = Exam::where('exams_code', "exms-v2-{$key}-pg")->firstOrFail();
            $this->assertSame('Pilihan Ganda', $pg->type);
            $this->assertStringEndsWith('Pilihan Ganda V2', $pg->title);
            $this->assertSame(100, $pg->questions()->count());
            $this->assertSame(0, Question::where('exam_id', $pg->id)->where(fn ($q) => $q->where('answer', '<', 1)->orWhere('answer', '>', 5))->count());

            $praktik = Exam::where('exams_code', "exms-v2-{$key}-praktik")->firstOrFail();
            $this->assertSame(Exam::TYPE_PRAKTIK, $praktik->type);
            $this->assertNotEmpty($praktik->praktik_panduan);
            $this->assertStringStartsWith('Ujian Praktik V2', $praktik->praktik_template_name);
            Storage::disk('private')->assertExists($praktik->praktik_template_path);
        }

        // Heavy Lifting tanpa ujian esai
        $this->assertNull(Exam::where('exams_code', 'exms-v2-lifting-esai')->first());
        $this->assertSame(10, Exam::where('exams_code', 'exms-v2-hse-esai')->firstOrFail()->essays()->count());
        $this->assertSame(13, Exam::where('exams_code', 'exms-v2-legal-esai')->firstOrFail()->essays()->count());
        $this->assertSame(10, Exam::where('exams_code', 'exms-v2-esg-esai')->firstOrFail()->essays()->count());
        $this->assertSame(0, Essay::where('answer', '')->count());

        // Gambar soal disalin ke disk public dan dirujuk lewat /storage
        $lifting = Exam::where('exams_code', 'exms-v2-lifting-pg')->first();
        $withImage = $lifting->questions()->where('question', 'like', '%<img src="/storage/paket-soal/v2/lifting/%')->count();
        $this->assertGreaterThanOrEqual(40, $withImage);
        Storage::disk('public')->assertExists('paket-soal/v2/lifting/lifting-q094-1.png');
        // Soal 94 & 95 memakai load chart yang sama
        $chart = '/storage/paket-soal/v2/lifting/lifting-q094-1.png';
        $this->assertSame(2, $lifting->questions()->where('question', 'like', "%{$chart}%")->count());

        // Durasi praktik 120 menit, kecuali Lifting: dua bagian — Calculation 300 menit, Gambar 180 menit
        foreach (['esg', 'hse', 'legal'] as $key) {
            $this->assertSame(120, (int) Exam::where('exams_code', "exms-v2-{$key}-praktik")->value('duration'), $key);
            $this->assertNull(Exam::where('exams_code', "exms-v2-{$key}-praktik-2")->first());
        }
        $calc = Exam::where('exams_code', 'exms-v2-lifting-praktik')->firstOrFail();
        $gambar = Exam::where('exams_code', 'exms-v2-lifting-praktik-2')->firstOrFail();
        $this->assertSame([300, 180], [(int) $calc->duration, (int) $gambar->duration]);
        $this->assertStringEndsWith('Ujian Praktik V2 - Calculation', $calc->title);
        $this->assertStringEndsWith('Ujian Praktik V2 - Gambar', $gambar->title);
        $this->assertSame('Ujian Praktik V2 - Lifting Gambar.zip', $gambar->praktik_template_name);
        $this->assertSame('isi lifting-gambar.zip', Storage::disk('private')->get($gambar->praktik_template_path));
        $this->assertStringContainsString('CALCULATION - C', $calc->praktik_panduan);
        $this->assertStringNotContainsString('B. PRAKTIK GAMBAR', $calc->praktik_panduan);
        $this->assertStringContainsString('B. PRAKTIK GAMBAR', $gambar->praktik_panduan);
        // Lampiran Rubrik Penilaian Asesor: Calculation B1 45 / B2 20 / B3 20 / B4 15, Gambar C 100 — dinilai per butir
        $this->assertSame([45, 20, 20, 15], array_column($calc->praktik_aspek, 'bobot'));
        $this->assertSame([10, 5, 10, 10], array_map(fn ($a) => count($a['item']), $calc->praktik_aspek));
        $this->assertSame([20, 10, 0], array_column($calc->praktik_aspek[1]['pilihan'], 'nilai'));
        $this->assertCount(10, $gambar->praktik_aspek[0]['item']);
        $this->assertStringContainsString('LIFTING PLAN DRAWING', strtoupper($gambar->praktik_rubrik));
        $this->assertStringNotContainsString('CALCULATION - C', $gambar->praktik_panduan);
    }

    public function test_pengaturan_mencontoh_ujian_sejenis_terakhir_di_kelas(): void
    {
        $this->classrooms();
        $esg = \App\Models\Classroom::where('classrooms_code', 'ESG')->first();
        $old = $this->makeExam($esg, 'Pilihan Ganda', 90);
        $old->update(['random_question' => 'Y', 'random_answer' => 'Y']);

        $this->pasang(['--skema' => ['esg']])->assertSuccessful();

        $pg = Exam::where('exams_code', 'exms-v2-esg-pg')->first();
        // Durasi dari dokumen skema (paket), pengaturan acak dari ujian sejenis terakhir
        $this->assertSame(120, (int) $pg->duration);
        $this->assertSame(90, (int) Exam::where('exams_code', 'exms-v2-esg-esai')->value('duration'));
        $this->assertSame('Y', $pg->random_answer);
        // Ujian lama tidak berubah
        $this->assertSame(0, $old->fresh()->questions()->count());
        $this->assertSame(1, Exam::where('exams_code', 'like', 'exms-v2-%')->where('type', 'Pilihan Ganda')->count());
    }

    public function test_jalankan_ulang_tidak_menggandakan_dan_melengkapi_template(): void
    {
        $this->classrooms();

        // Pertama: template belum diunggah ke server
        $this->pasang()->expectsOutputToContain('tidak ada di')->assertSuccessful();
        $this->assertNull(Exam::where('exams_code', 'exms-v2-hse-praktik')->value('praktik_template_path'));
        $exams = Exam::count();
        $questions = Question::count();

        // Kedua: template sudah ada → dilengkapi, soal tidak dobel
        $this->templateFiles();
        $this->pasang()->assertSuccessful();

        $this->assertSame($exams, Exam::count());
        $this->assertSame($questions, Question::count());
        $path = Exam::where('exams_code', 'exms-v2-hse-praktik')->value('praktik_template_path');
        $this->assertNotNull($path);
        Storage::disk('private')->assertExists($path);
    }

    public function test_rubrik_studi_kasus_2_corporate_legal_untuk_asesor(): void
    {
        $this->classrooms();
        $this->templateFiles();
        $this->pasang(['--skema' => ['legal']])->assertSuccessful();

        $exam = Exam::where('exams_code', 'exms-v2-legal-praktik')->firstOrFail();
        $this->assertStringContainsString('RUBRIK STUDI KASUS 2', $exam->praktik_rubrik);
        $this->assertStringContainsString('<table>', $exam->praktik_rubrik);
        $this->assertStringNotContainsString('STUDI KASUS 1', $exam->praktik_rubrik);
        // 8 aspek Studi Kasus 2, bobot total 100% (Rekapitulasi Nilai FR.IA.04)
        $this->assertCount(8, $exam->praktik_aspek);
        $this->assertSame(100, array_sum(array_column($exam->praktik_aspek, 'bobot')));
        $this->assertSame('Pembatasan sampai CK-1', $exam->praktik_aspek[3]['nama']);
        // Rubrik tidak ikut terserialisasi (peserta, admin JSON)
        $this->assertArrayNotHasKey('praktik_rubrik', $exam->toArray());
        $this->assertArrayNotHasKey('praktik_aspek', $exam->toArray());
    }

    public function test_esai_membawa_panduan_umum_dan_matriks_per_soal(): void
    {
        $this->classrooms();
        $this->templateFiles();
        $this->pasang(['--skema' => ['hse', 'legal', 'esg']])->assertSuccessful();

        foreach (['hse' => 10, 'legal' => 13] as $key => $n) {
            $exam = Exam::where('exams_code', "exms-v2-{$key}-esai")->firstOrFail();
            $this->assertStringContainsString('Panduan Penilaian Umum', $exam->esai_panduan, $key);
            $this->assertStringContainsString('<li><strong>Kesesuaian Poin Kunci:</strong>', $exam->esai_panduan, $key);
            $essays = $exam->essays()->get();
            $this->assertCount($n, $essays->filter(fn ($e) => count($e->rubrik ?? []) === 5), $key);
            $this->assertSame([[90, 100], [70, 89], [50, 69], [30, 49], [10, 29]],
                array_map(fn ($b) => [$b['min'], $b['max']], $essays[0]->rubrik), $key);
        }

        // HSE soal 10: lanjutan baris di halaman terakhir dokumen ikut tergabung
        $hse10 = Exam::where('exams_code', 'exms-v2-hse-esai')->firstOrFail()->essays()->get()[9];
        $this->assertStringEndsWith('berlawanan dengan inspeksi tahunan yang reaktif.', $hse10->rubrik[0]['deskripsi']);

        // ESG: tanpa panduan umum, rentang per soal sendiri (soal 5: 90–100 / 70–80 / 40–60 / 10–30)
        $esg = Exam::where('exams_code', 'exms-v2-esg-esai')->firstOrFail();
        $this->assertNull($esg->esai_panduan);
        $esgEssays = $esg->essays()->get();
        $this->assertSame([[81, 100], [61, 80], [31, 60], [0, 30]], array_map(fn ($b) => [$b['min'], $b['max']], $esgEssays[0]->rubrik));
        $this->assertSame([[90, 100], [70, 80], [40, 60], [10, 30]], array_map(fn ($b) => [$b['min'], $b['max']], $esgEssays[4]->rubrik));
    }

    public function test_perbarui_melengkapi_matriks_esai_yang_sudah_terpasang(): void
    {
        $this->classrooms();
        $this->templateFiles();
        $this->pasang(['--skema' => ['hse']])->assertSuccessful();

        // Ujian esai terpasang sebelum ada matriks; HTML satu soal berbeda (pencocokan lewat teks huruf/angka saja)
        $exam = Exam::where('exams_code', 'exms-v2-hse-esai')->firstOrFail();
        $exam->update(['esai_panduan' => null]);
        Essay::where('exam_id', $exam->id)->update(['rubrik' => null]);
        $first = $exam->essays()->first();
        $first->update(['question' => '<p>' . strip_tags($first->question) . '</p>']);

        $this->pasang(['--skema' => ['hse']])->expectsOutputToContain('pakai --perbarui')->assertSuccessful();
        $this->assertNull($exam->fresh()->esai_panduan);

        $this->pasang(['--skema' => ['hse'], '--perbarui' => true])->assertSuccessful();
        $this->assertNotNull($exam->fresh()->esai_panduan);
        $this->assertSame(10, Essay::where('exam_id', $exam->id)->whereNotNull('rubrik')->count());
        $this->assertSame(10, Essay::where('exam_id', $exam->id)->count());

        $this->pasang(['--skema' => ['hse'], '--dry-run' => true])->expectsOutputToContain('matriks rubrik sudah sama')->assertSuccessful();
    }

    public function test_skema_laboratorium_dan_pangan_dari_folder_27_28(): void
    {
        $skema = ['glp' => 'GLP', 'qc' => 'QCA', 'fsms' => 'FMO', 'rnd' => 'RDO', 'panelis' => 'PSP', 'iso17025' => 'LQO'];
        foreach ($skema as $key => $code) {
            $this->makeClassroom($code);
            File::put("{$this->templates}/" . ($key === 'panelis' ? 'panelis.pdf' : "{$key}.docx"), "isi {$key}");
        }

        $this->pasang(['--skema' => array_keys($skema)])->assertSuccessful();

        foreach (['glp' => 100, 'qc' => 100, 'fsms' => 100, 'rnd' => 100, 'panelis' => 100, 'iso17025' => 100] as $key => $n) {
            $this->assertSame($n, Exam::where('exams_code', "exms-v2-{$key}-pg")->firstOrFail()->questions()->count(), $key);
            $praktik = Exam::where('exams_code', "exms-v2-{$key}-praktik")->firstOrFail();
            $this->assertSame(120, (int) $praktik->duration, $key);
            $this->assertSame(100.0, (float) array_sum(array_column($praktik->praktik_aspek, 'bobot')), $key);
            $this->assertNotEmpty($praktik->praktik_rubrik, $key);
            // Kunci jawaban / rubrik tidak ada di panduan peserta
            $this->assertStringNotContainsStringIgnoringCase('kunci jawaban', $praktik->praktik_panduan, $key);
            $this->assertStringNotContainsString('RUBRIK', $praktik->praktik_panduan, $key);
        }

        // Komposisi Nilai & durasi disamakan dengan CLO/HSE/ESG; Panelis tanpa esai → Ujian Tulis seluruhnya PG
        foreach ($skema as $key => $code) {
            $scheme = \App\Models\GradingScheme::where('classroom_id', \App\Models\Classroom::where('classrooms_code', $code)->value('id'))->firstOrFail();
            $expected = $key === 'panelis' ? [70.0, 0.0, 22.5, 7.5, 60.0] : [45.5, 24.5, 22.5, 7.5, 60.0];
            $this->assertSame($expected, [(float) $scheme->bobot_pg, (float) $scheme->bobot_esai, (float) $scheme->bobot_wawancara,
                (float) $scheme->bobot_praktik_1, (float) $scheme->nilai_kelulusan], $key);
            $this->assertSame(120, (int) Exam::where('exams_code', "exms-v2-{$key}-pg")->value('duration'), $key);
        }
        $this->assertSame(90, (int) Exam::where('exams_code', 'exms-v2-glp-esai')->value('duration'));

        // Panelis tanpa esai; skema lain 10 soal esai bermatriks + Panduan Penilaian Umum
        $this->assertNull(Exam::where('exams_code', 'exms-v2-panelis-esai')->first());
        foreach (['glp' => 5, 'qc' => 5, 'fsms' => 4, 'rnd' => 5, 'iso17025' => 4] as $key => $bands) {
            $esai = Exam::where('exams_code', "exms-v2-{$key}-esai")->firstOrFail();
            $this->assertSame(10, $esai->essays()->count(), $key);
            $this->assertStringContainsString('Panduan Penilaian Umum', $esai->esai_panduan, $key);
            $this->assertCount($bands, $esai->essays()->first()->rubrik, $key);
        }

        // Rubrik bergerbang (sumber bukti, poin kunci, gerbang kritis maks. 50) — GLP A1–A4: 20/35/25/20
        $glp = Exam::where('exams_code', 'exms-v2-glp-praktik')->firstOrFail()->praktik_aspek;
        $this->assertSame([20, 35, 25, 20], array_column($glp, 'bobot'));
        $this->assertSame('Stasiun 1', $glp[0]['sumber']);
        $this->assertSame(50, $glp[0]['maks_gerbang']);
        $this->assertSame([76, 51, 26, 0], array_column($glp[0]['kriteria'], 'min'));
        // R&D: rubrik per nomor soal dikelompokkan per tugas (30/30/20/20)
        $rnd = Exam::where('exams_code', 'exms-v2-rnd-praktik')->firstOrFail()->praktik_aspek;
        $this->assertSame([15, 15, 15, 15, 10, 10, 20], array_column($rnd, 'bobot'));
        $this->assertStringStartsWith('Tugas 1:', $rnd[0]['kelompok']);
        $this->assertSame([90, 70, 50, 30, 10], array_column($rnd[0]['kriteria'], 'min'));
        // ISO 17025: tabel kriteria unjuk kerja 4 × 25%
        $iso = Exam::where('exams_code', 'exms-v2-iso17025-praktik')->firstOrFail()->praktik_aspek;
        $this->assertSame('Penyusunan Instruksi Kerja Pengujian', $iso[0]['nama']);
        // Koreksi pengguna: GLP soal 26 membawa konteks soal 25; QC soal 99 kunci C
        $glpPg = Question::where('exam_id', Exam::where('exams_code', 'exms-v2-glp-pg')->value('id'))->orderBy('id')->skip(25)->first();
        $this->assertStringContainsString('Melakukan Persiapan Penerapan GLP', $glpPg->question);
        $this->assertStringContainsString('Apakah hal tersebut?', $glpPg->question);
        $this->assertSame(3, (int) Question::where('exam_id', Exam::where('exams_code', 'exms-v2-qc-pg')->value('id'))->orderBy('id')->skip(98)->value('answer'));
        // Opsi PG terakhir tidak membawa catatan kaki dokumen / judul bagian berikutnya
        $this->assertSame('Teguran lisan saja', Question::where('exam_id', Exam::where('exams_code', 'exms-v2-panelis-pg')->value('id'))
            ->orderBy('id')->skip(19)->value('option_4'));
    }

    public function test_kelas_dengan_kode_lama_atau_kode_lain_tetap_ditemukan(): void
    {
        // Database produksi: Food Safety masih FSI, ISO 17025 masih ISL, Panelis memakai kode lain
        $fsi = \App\Models\Classroom::forceCreate(['classrooms_code' => 'FSI', 'title' => 'Food Safety Management Officer']);
        $isl = \App\Models\Classroom::forceCreate(['classrooms_code' => 'ISL', 'title' => 'Laboratory Quality System Officer ISO/IEC 17025']);
        $pan = \App\Models\Classroom::forceCreate(['classrooms_code' => 'PTS', 'title' => 'Panelis Terlatih Pengujian Sensori Pangan']);
        foreach (['fsms.docx', 'iso17025.docx', 'panelis.pdf'] as $f) {
            File::put("{$this->templates}/{$f}", "isi {$f}");
        }

        $this->pasang(['--skema' => ['fsms', 'iso17025', 'panelis'], '--dry-run' => true])
            ->expectsOutputToContain('FSI — Food Safety Management Officer (paket: FMO)')
            ->assertSuccessful();

        $this->pasang(['--skema' => ['fsms', 'iso17025', 'panelis']])->assertSuccessful();

        foreach (['fsms' => $fsi, 'iso17025' => $isl, 'panelis' => $pan] as $key => $classroom) {
            foreach (['pg', 'praktik'] as $part) {
                $this->assertSame($classroom->id, Exam::where('exams_code', "exms-v2-{$key}-{$part}")->value('classroom_id'), "{$key} {$part}");
            }
        }
        $this->assertSame($fsi->id, Exam::where('exams_code', 'exms-v2-fsms-esai')->value('classroom_id'));
        $this->assertTrue(\App\Models\GradingScheme::where('classroom_id', $pan->id)->exists());
    }

    public function test_judul_yang_cocok_lebih_dari_satu_kelas_tidak_dipakai(): void
    {
        \App\Models\Classroom::forceCreate(['classrooms_code' => 'PT1', 'title' => 'Panelis Terlatih A']);
        \App\Models\Classroom::forceCreate(['classrooms_code' => 'PT2', 'title' => 'Panelis Terlatih B']);

        $this->pasang(['--skema' => ['panelis']])->expectsOutputToContain('tidak ditemukan')->assertSuccessful();
        $this->assertSame(0, Exam::count());
    }

    public function test_hse_praktik_dinilai_per_kuk(): void
    {
        $this->classrooms();
        $this->templateFiles();
        $this->pasang(['--skema' => ['hse']])->assertSuccessful();

        // RUBRIK PENILAIAN PRAKTIK (KRITERIA UNJUK KERJA): Tugas 1 35% (1 KUK), Tugas 2 35% (2 KUK), Tugas 3 30% (3 KUK)
        $aspek = Exam::where('exams_code', 'exms-v2-hse-praktik')->firstOrFail()->praktik_aspek;
        $this->assertCount(6, $aspek);
        $this->assertEqualsWithDelta([35, 17.5, 17.5, 10, 10, 10], array_column($aspek, 'bobot'), 0.001);
        $this->assertSame('Prosedur Penggunaan Eye Wash', $aspek[4]['nama']);
        $this->assertStringStartsWith('Tugas 3', $aspek[4]['kelompok']);
        $this->assertSame([76, 51, 26, 0], array_column($aspek[0]['kriteria'], 'min'));
    }

    public function test_esg_praktik_sesuai_fr_ia_04_rev(): void
    {
        $this->classrooms();
        $this->templateFiles();
        $this->pasang(['--skema' => ['esg']])->assertSuccessful();

        $exam = Exam::where('exams_code', 'exms-v2-esg-praktik')->firstOrFail();
        $this->assertStringContainsString('LEMBAR DATA KASUS — PT XYZ', $exam->praktik_panduan);
        $this->assertStringContainsString('D. Tugas 4 — Ringkasan Bahan Pengungkapan', $exam->praktik_panduan);
        // Tugas 1–4 @25%, rentang 76–100 / 51–75 / 26–50 / 0–25, gerbang kritis → maks. 50
        $this->assertSame([25, 25, 25, 25], array_column($exam->praktik_aspek, 'bobot'));
        $this->assertSame([76, 51, 26, 0], array_column($exam->praktik_aspek[0]['kriteria'], 'min'));
        $this->assertCount(2, $exam->praktik_aspek[1]['gerbang']);
        $this->assertSame(50, $exam->praktik_aspek[0]['maks_gerbang']);
        $this->assertCount(5, $exam->praktik_aspek[3]['poin_kunci']);
        $this->assertStringContainsString('REKAPITULASI NILAI', $exam->praktik_rubrik);
    }

    public function test_corporate_legal_praktik_hanya_studi_kasus_2(): void
    {
        $panduan = json_decode(File::get(database_path('data/paket-soal/v2/legal.json')), true)['praktik']['panduan'];

        $this->assertStringStartsWith('Studi Kasus 2', $panduan);
        $this->assertStringContainsString('SURAT KUASA KHUSUS', $panduan);
        $this->assertStringNotContainsString('Studi Kasus 1', $panduan);
        $this->assertStringNotContainsString('LEGAL OPINION', $panduan);
    }

    /** Simulasikan Ujian Praktik Corporate Legal versi lama (dua studi kasus) yang sudah terpasang. */
    private function legalPraktikLama(): Exam
    {
        $this->classrooms();
        $this->templateFiles();
        $this->pasang(['--skema' => ['legal']])->assertSuccessful();

        $exam = Exam::where('exams_code', 'exms-v2-legal-praktik')->firstOrFail();
        $exam->update(['praktik_panduan' => "Studi Kasus 1\nLEGAL OPINION\n\nStudi Kasus 2\nSURAT KUASA"]);
        File::put("{$this->templates}/legal.docx", 'template baru: studi kasus 2 saja');

        return $exam->fresh();
    }

    public function test_perbarui_praktik_menyamakan_ujian_yang_belum_dipakai(): void
    {
        $exam = $this->legalPraktikLama();
        $oldPath = $exam->praktik_template_path;
        $paket = json_decode(File::get(database_path('data/paket-soal/v2/legal.json')), true)['praktik'];

        // Tanpa opsi: hanya diberi tahu, tidak diubah
        $this->pasang(['--skema' => ['legal']])->expectsOutputToContain('pakai --perbarui')->assertSuccessful();
        $this->assertSame($oldPath, $exam->fresh()->praktik_template_path);
        $this->assertStringContainsString('LEGAL OPINION', $exam->fresh()->praktik_panduan);

        // Dry run tidak menyimpan
        $this->pasang(['--skema' => ['legal'], '--perbarui-praktik' => true, '--dry-run' => true])
            ->expectsOutputToContain('diperbarui: panduan, template')->assertSuccessful();
        $this->assertSame($oldPath, $exam->fresh()->praktik_template_path);

        $this->pasang(['--skema' => ['legal'], '--perbarui-praktik' => true])->assertSuccessful();

        $exam->refresh();
        $this->assertSame($paket['panduan'], $exam->praktik_panduan);
        $this->assertNotSame($oldPath, $exam->praktik_template_path);
        $this->assertSame('template baru: studi kasus 2 saja', Storage::disk('private')->get($exam->praktik_template_path));
        Storage::disk('private')->assertMissing($oldPath);
        $this->assertSame(1, Exam::where('exams_code', 'exms-v2-legal-praktik')->count());

        // Dijalankan lagi: sudah sama
        $this->pasang(['--skema' => ['legal'], '--perbarui-praktik' => true])
            ->expectsOutputToContain('sudah sama dengan paket')->assertSuccessful();
        $this->assertSame($exam->praktik_template_path, $exam->fresh()->praktik_template_path);
    }

    public function test_perbarui_praktik_lifting_lama_jadi_dua_bagian(): void
    {
        $this->classrooms();
        $this->templateFiles();
        $this->pasang(['--skema' => ['lifting']])->assertSuccessful();

        // Seperti yang sudah terpasang di server: satu ujian praktik gabungan tanpa batas waktu
        $calc = Exam::where('exams_code', 'exms-v2-lifting-praktik')->firstOrFail();
        Exam::where('exams_code', 'exms-v2-lifting-praktik-2')->delete();
        $kelas = \App\Models\Classroom::where('classrooms_code', 'LHC')->value('title');
        $calc->update(['title' => "{$kelas} - Ujian Praktik V2", 'duration' => 0, 'praktik_panduan' => 'A. ... B. PRAKTIK GAMBAR ...']);

        $this->pasang(['--skema' => ['lifting'], '--perbarui-praktik' => true, '--dry-run' => true])
            ->expectsOutputToContain('diperbarui: judul, panduan, durasi')
            ->assertSuccessful();

        $this->pasang(['--skema' => ['lifting'], '--perbarui-praktik' => true])->assertSuccessful();

        $calc->refresh();
        $this->assertSame("{$kelas} - Ujian Praktik V2 - Calculation", $calc->title);
        $this->assertSame(300, (int) $calc->duration);
        $gambar = Exam::where('exams_code', 'exms-v2-lifting-praktik-2')->firstOrFail();
        $this->assertSame(180, (int) $gambar->duration);
        $this->assertSame(2, Exam::where('exams_code', 'like', 'exms-v2-lifting-praktik%')->count());
    }

    public function test_perbarui_praktik_tidak_mengubah_ujian_yang_sudah_dipakai(): void
    {
        $exam = $this->legalPraktikLama();
        $oldPath = $exam->praktik_template_path;

        $classroom = \App\Models\Classroom::where('classrooms_code', 'CLO')->first();
        $session = $this->makeSession(Exam::where('exams_code', 'exms-v2-legal-pg')->first());
        $session->update(['exam_id_praktik' => $exam->id]);
        \App\Models\StudentTask::create([
            'student_id'      => $this->makeStudent($classroom)->id,
            'exam_session_id' => $session->id,
            'file_path'       => 'tugas/hasil.docx',
        ]);

        $this->pasang(['--skema' => ['legal'], '--perbarui-praktik' => true])
            ->expectsOutputToContain('sudah dipakai 1 peserta')->assertSuccessful();

        $exam->refresh();
        $this->assertStringContainsString('LEGAL OPINION', $exam->praktik_panduan);
        $this->assertSame($oldPath, $exam->praktik_template_path);
        Storage::disk('private')->assertExists($oldPath);
    }

    public function test_durasi_dan_komposisi_nilai_sesuai_dokumen_skema(): void
    {
        $this->classrooms();
        $this->templateFiles();
        $this->pasang()->assertSuccessful();

        // Corporate Legal / HSE / ESG: PG 120, Esai 90; Tulis 70 (PG 65) + Lisan 22,5 + Keterampilan 7,5 (= 30 × 75/25%); lulus 60
        foreach (['legal' => 'CLO', 'hse' => 'K3L', 'esg' => 'ESG'] as $key => $code) {
            $this->assertSame(120, (int) Exam::where('exams_code', "exms-v2-{$key}-pg")->value('duration'), $key);
            $this->assertSame(90, (int) Exam::where('exams_code', "exms-v2-{$key}-esai")->value('duration'), $key);
            $scheme = \App\Models\GradingScheme::where('classroom_id', \App\Models\Classroom::where('classrooms_code', $code)->value('id'))->firstOrFail();
            $this->assertSame([45.5, 24.5, 22.5, 7.5, 0.0, 60.0],
                [$scheme->bobot_pg, $scheme->bobot_esai, $scheme->bobot_wawancara, $scheme->bobot_praktik_1, $scheme->bobot_praktik_2, $scheme->nilai_kelulusan], $key);
        }

        // Lifting: PG 60 menit 20%, Praktek Calculation 50%, Praktek Gambar 30%; lulus 70
        $this->assertSame(60, (int) Exam::where('exams_code', 'exms-v2-lifting-pg')->value('duration'));
        $lhc = \App\Models\GradingScheme::where('classroom_id', \App\Models\Classroom::where('classrooms_code', 'LHC')->value('id'))->firstOrFail();
        $this->assertSame([20.0, 0.0, 0.0, 50.0, 30.0, 70.0],
            [$lhc->bobot_pg, $lhc->bobot_esai, $lhc->bobot_wawancara, $lhc->bobot_praktik_1, $lhc->bobot_praktik_2, $lhc->nilai_kelulusan]);
    }

    public function test_perbarui_menyamakan_durasi_dan_komposisi_yang_sudah_ada(): void
    {
        $this->classrooms();
        $esg = \App\Models\Classroom::where('classrooms_code', 'ESG')->first();
        \App\Models\GradingScheme::create(['classroom_id' => $esg->id, 'bobot_pg' => 40, 'bobot_esai' => 35, 'bobot_wawancara' => 25,
            'bobot_ujian_tulis' => 75, 'proporsi_pg' => 53.33, 'nilai_kelulusan' => 70]);
        $this->pasang(['--skema' => ['esg']])->assertSuccessful();

        // Yang sudah ada: durasi ujian diubah manual & komposisi lama tidak ditimpa tanpa --perbarui
        $pg = Exam::where('exams_code', 'exms-v2-esg-pg')->firstOrFail();
        $pg->update(['duration' => 100]);
        $this->assertSame(40.0, \App\Models\GradingScheme::where('classroom_id', $esg->id)->value('bobot_pg') + 0.0);
        $this->pasang(['--skema' => ['esg'], '--dry-run' => true])
            ->expectsOutputToContain('pakai --perbarui')
            ->assertSuccessful();

        $this->pasang(['--skema' => ['esg'], '--perbarui' => true])->assertSuccessful();
        $this->assertSame(120, (int) $pg->fresh()->duration);
        $scheme = \App\Models\GradingScheme::where('classroom_id', $esg->id)->first();
        $this->assertSame([45.5, 24.5, 22.5, 7.5, 60.0], [$scheme->bobot_pg, $scheme->bobot_esai, $scheme->bobot_wawancara, $scheme->bobot_praktik_1, $scheme->nilai_kelulusan]);

        // Ujian yang sudah dikerjakan peserta tidak diubah durasinya
        $pg->refresh()->update(['duration' => 100]);
        $session = $this->makeSession($pg);
        \App\Models\Grade::forceCreate(['grades_code' => 'g-' . Str::random(6), 'exam_id' => $pg->id, 'exam_session_id' => $session->id,
            'student_id' => $this->makeStudent($esg)->id, 'duration' => 0, 'total_correct' => 0, 'grade' => 0, 'start_time' => now()]);
        $this->pasang(['--skema' => ['esg'], '--perbarui' => true])->expectsOutputToContain('sudah dikerjakan peserta')->assertSuccessful();
        $this->assertSame(100, (int) $pg->fresh()->duration);
    }

    public function test_skema_tanpa_kelas_dilewati(): void
    {
        $this->makeClassroom('ESG');

        $this->pasang()->expectsOutputToContain('tidak ditemukan')->assertSuccessful();

        $this->assertNotNull(Exam::where('exams_code', 'exms-v2-esg-pg')->first());
        $this->assertNull(Exam::where('exams_code', 'exms-v2-lifting-pg')->first());
    }

    public function test_data_rusak_ditolak_tanpa_memasang_apa_pun(): void
    {
        $this->classrooms();
        $versi = 'zz-test-' . Str::lower(Str::random(6));
        $dir = database_path("data/paket-soal/{$versi}");
        File::ensureDirectoryExists($dir);

        try {
            File::put("{$dir}/esg.json", json_encode([
                'classroom_code' => 'ESG',
                'pilihan_ganda'  => [
                    ['no' => 1, 'blocks' => [['text' => 'Soal']], 'options' => ['A', 'B'], 'answer' => 3],
                    ['no' => 2, 'blocks' => [['text' => 'Soal'], ['image' => 'esg/tidak-ada.png']], 'options' => ['A', 'B'], 'answer' => 1],
                ],
            ]));

            $this->artisan('paket-soal:pasang', ['versi' => $versi, '--force' => true])
                ->expectsOutputToContain('kunci tidak valid')
                ->expectsOutputToContain('tidak ada')
                ->assertFailed();

            $this->assertSame(0, Exam::count());
        } finally {
            File::deleteDirectory($dir);
        }
    }
}
