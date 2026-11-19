<?php

namespace Tests\Feature;

use App\Models\Essay;
use App\Models\Exam;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Feature\Concerns\CreatesExamFixtures;
use Tests\TestCase;

/** kunci-esai:perbarui — isi/ganti essays.answer soal yang sudah ada, dicocokkan lewat teks pertanyaan. */
class PerbaruiKunciEsaiTest extends TestCase
{
    use RefreshDatabase, CreatesExamFixtures;

    private string $name;

    protected function setUp(): void
    {
        parent::setUp();
        $this->name = 'test-' . Str::lower(Str::random(8));

        // Kolom is_essay ada di database produksi tetapi tidak dibuat migrasi — tambahkan seperti di produksi.
        if (! \Illuminate\Support\Facades\Schema::hasColumn('essays', 'is_essay')) {
            \Illuminate\Support\Facades\Schema::table('essays', fn ($t) => $t->boolean('is_essay')->nullable());
        }
    }

    protected function tearDown(): void
    {
        @unlink(database_path("data/kunci-esai/{$this->name}.json"));
        parent::tearDown();
    }

    private function writeData(array $esai, string $code = 'ToT'): void
    {
        file_put_contents(database_path("data/kunci-esai/{$this->name}.json"), json_encode([
            'classroom_code' => $code, 'sumber' => 'Test', 'esai' => $esai,
        ]));
    }

    private function essay(Exam $exam, string $question, string $answer = '<p>-</p>'): Essay
    {
        return Essay::forceCreate(['exam_id' => $exam->id, 'essays_code' => 'es-' . Str::random(8), 'question' => $question, 'answer' => $answer]);
    }

    public function test_fills_and_replaces_keys_by_question_text(): void
    {
        $exam  = $this->makeExam($this->makeClassroom('ToT'), 'Essay');
        $empty = $this->essay($exam, '<p>Apa yang dimaksud dengan&nbsp; konsep pembelajaran OBE?</p>');
        $old   = $this->essay($exam, 'Jelaskan 4 jenis media pembelajaran', '<p>kunci lama</p>');
        $extra = $this->essay($exam, 'Soal yang tidak ada di dokumen', '<p>tetap</p>');

        $this->writeData([
            ['question' => 'Apa yang dimaksud dengan konsep pembelajaran OBE', 'answer' => '<p>OBE adalah ...</p><script>alert(1)</script>'],
            ['question' => 'Jelaskan 4 jenis  media pembelajaran.', 'answer' => '<ol><li>Media cetak</li></ol>'],
        ]);

        $this->artisan('kunci-esai:perbarui', ['data' => $this->name, '--force' => true])
            ->expectsOutputToContain('2 kunci akan diperbarui; 2 soal ditandai uraian; 1 soal tidak ditemukan')
            ->assertSuccessful();

        $this->assertTrue((bool) $empty->refresh()->is_essay);                    // label "Poin / kisi jawaban"
        $this->assertFalse((bool) $extra->refresh()->is_essay);                   // soal di luar dokumen tidak disentuh

        $this->assertSame('<p>OBE adalah ...</p>', $empty->refresh()->answer);  // dibersihkan RichText
        $this->assertSame('<ol><li>Media cetak</li></ol>', $old->refresh()->answer);
        $this->assertSame('<p>tetap</p>', $extra->refresh()->answer);
        $this->assertStringContainsString('dimaksud', $empty->question);          // soal tidak berubah

        // Dijalankan ulang: tidak ada yang berubah
        $this->artisan('kunci-esai:perbarui', ['data' => $this->name, '--force' => true])
            ->expectsOutputToContain('0 kunci akan diperbarui; 0 soal ditandai uraian')
            ->assertSuccessful();
    }

    public function test_dry_run_and_other_skema_are_untouched(): void
    {
        $exam  = $this->makeExam($this->makeClassroom('ToT'), 'Essay');
        $other = $this->makeExam($this->makeClassroom('ESG'), 'Essay');
        $a     = $this->essay($exam, 'Soal A');
        $b     = $this->essay($other, 'Soal A');

        $this->writeData([['question' => 'Soal A', 'answer' => '<p>kunci A</p>']]);

        $this->artisan('kunci-esai:perbarui', ['data' => $this->name, '--dry-run' => true])->assertSuccessful();
        $this->assertSame('<p>-</p>', $a->refresh()->answer);

        $this->artisan('kunci-esai:perbarui', ['data' => $this->name, '--force' => true])->assertSuccessful();
        $this->assertSame('<p>kunci A</p>', $a->refresh()->answer);
        $this->assertSame('<p>-</p>', $b->refresh()->answer);
    }

    public function test_rejects_bad_data(): void
    {
        $this->makeClassroom('ToT');

        $this->writeData([['question' => 'Soal A', 'answer' => '-']]);
        $this->artisan('kunci-esai:perbarui', ['data' => $this->name])->assertFailed();

        $this->writeData([['question' => 'Soal A', 'answer' => '<p>x</p>'], ['question' => 'soal a!', 'answer' => '<p>y</p>']]);
        $this->artisan('kunci-esai:perbarui', ['data' => $this->name])->assertFailed();

        $this->writeData([['question' => 'Soal A', 'answer' => '<p>x</p>']], 'TIDAKADA');
        $this->artisan('kunci-esai:perbarui', ['data' => $this->name])->assertFailed();
    }

    public function test_shipped_tot_obe_data_is_valid(): void
    {
        $data = json_decode(file_get_contents(database_path('data/kunci-esai/tot-obe-rev01.json')), true);

        $this->assertSame('ToT', $data['classroom_code']);
        $this->assertCount(26, $data['esai']);
        foreach ($data['esai'] as $item) {
            $this->assertNotSame('', \App\Console\Commands\PerbaruiKunciEsai::normalize($item['question']));
            $this->assertTrue(\App\Support\RichText::hasContent($item['answer']));
        }
    }
}
