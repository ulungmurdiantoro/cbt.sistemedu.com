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
        foreach (['lifting.zip', 'hse.docx', 'legal.docx', 'esg.zip'] as $f) {
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

        // Durasi praktik ESG dari lembar kerja (120 menit); lainnya tanpa batas waktu
        $this->assertSame(120, (int) Exam::where('exams_code', 'exms-v2-esg-praktik')->value('duration'));
        $this->assertSame(0, (int) Exam::where('exams_code', 'exms-v2-lifting-praktik')->value('duration'));
    }

    public function test_pengaturan_mencontoh_ujian_sejenis_terakhir_di_kelas(): void
    {
        $this->classrooms();
        $esg = \App\Models\Classroom::where('classrooms_code', 'ESG')->first();
        $old = $this->makeExam($esg, 'Pilihan Ganda', 90);
        $old->update(['random_question' => 'Y', 'random_answer' => 'Y']);

        $this->pasang(['--skema' => ['esg']])->assertSuccessful();

        $pg = Exam::where('exams_code', 'exms-v2-esg-pg')->first();
        $this->assertSame(90, (int) $pg->duration);
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
