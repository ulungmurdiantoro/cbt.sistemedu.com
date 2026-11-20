<?php

namespace Tests\Feature;

use App\Models\Exam;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\CreatesExamFixtures;
use Tests\TestCase;

/** Migrasi 2026_11_21_000001: tipe & judul "Ujian Praktik" lama → "Ujian Praktik/Keterampilan". */
class RenameUjianPraktikMigrationTest extends TestCase
{
    use RefreshDatabase, CreatesExamFixtures;

    public function test_tipe_dan_judul_ujian_praktik_lama_diganti(): void
    {
        $classroom = $this->makeClassroom();
        $lama = $this->makeExam($classroom, 'Ujian Praktik');
        $lama->forceFill(['title' => 'Skema X - Ujian Praktik V2'])->save();
        $pg = $this->makeExam($classroom, 'Pilihan Ganda');
        $pg->forceFill(['title' => 'Kisi Ujian Praktik (PG)'])->save();

        $migration = require database_path('migrations/2026_11_21_000001_rename_ujian_praktik_keterampilan.php');
        $migration->up();

        $this->assertSame(Exam::TYPE_PRAKTIK, $lama->fresh()->type);
        $this->assertSame('Skema X - Ujian Praktik/Keterampilan V2', $lama->fresh()->title);
        // Ujian jenis lain tidak disentuh
        $this->assertSame('Kisi Ujian Praktik (PG)', $pg->fresh()->title);

        // Dijalankan ulang: tidak menggandakan "/Keterampilan"
        $migration->up();
        $this->assertSame('Skema X - Ujian Praktik/Keterampilan V2', $lama->fresh()->title);
        $this->assertSame(1, DB::table('exams')->where('type', Exam::TYPE_PRAKTIK)->count());
    }
}
