<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\ClassroomCompetencyUnit;
use App\Services\DocumentGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesExamFixtures;
use Tests\TestCase;

/**
 * Halaman 2 sertifikat: tabel unit kompetensi tidak boleh menimpa blok TTD/QR yang tercetak
 * di background (±213 mm dari atas). Skema dengan banyak unit dipadatkan, skema pendek tidak.
 */
class SertifikatUnitTableTest extends TestCase
{
    use RefreshDatabase;
    use CreatesExamFixtures;

    private function backLayout(Classroom $classroom): array
    {
        return app(DocumentGeneratorService::class)->sertifikatBackLayout([
            'classroom'       => $classroom,
            'competencyUnits' => ClassroomCompetencyUnit::where('classroom_id', $classroom->id)->orderBy('order')->get(),
            'certNumber'      => '008-010-10-2026-00001',
        ]);
    }

    private function addUnits(Classroom $classroom, int $count, string $judul): void
    {
        for ($i = 1; $i <= $count; $i++) {
            ClassroomCompetencyUnit::create([
                'classroom_id' => $classroom->id,
                'kode_unit'    => sprintf('F.42LFE00.%03d.1', $i),
                'judul_unit'   => $judul,
                'order'        => $i,
            ]);
        }
    }

    public function test_short_unit_list_keeps_normal_size(): void
    {
        $classroom = $this->makeClassroom();
        $this->addUnits($classroom, 3, 'Menyusun rencana operasi pengangkatan');

        $layout = $this->backLayout($classroom);

        $this->assertTrue($layout['fits']);
        $this->assertSame(0, $layout['density']);
    }

    public function test_long_unit_list_is_compacted_above_signature_block(): void
    {
        $classroom = $this->makeClassroom();
        // 9 unit berjudul panjang (dua bahasa) — dengan ukuran normal tabel melewati blok TTD/QR.
        $this->addUnits($classroom, 9, 'Mengawasi proses pengangkatan dan pemasangan beban sesuai Lifting Plan '
            . 'Supervising Lifting and Installation Operations in Accordance with the Lifting Plan');

        $layout = $this->backLayout($classroom);

        $this->assertTrue($layout['fits']);
        $this->assertGreaterThan(0, $layout['density']);
        $this->assertLessThanOrEqual(208, $layout['bottom']);
    }
}
