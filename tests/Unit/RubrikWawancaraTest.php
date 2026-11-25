<?php

namespace Tests\Unit;

use App\Support\RubrikWawancara;
use PHPUnit\Framework\TestCase;

/** Rubrik Indikator Penilaian Wawancara: pita N > 80 / 71–80 / 60–70 / < 60, sama untuk semua skema. */
class RubrikWawancaraTest extends TestCase
{
    public function test_band_boundaries_follow_the_rubric_table(): void
    {
        $this->assertSame('a', RubrikWawancara::pita(80.01));
        $this->assertSame('b', RubrikWawancara::pita(80));
        $this->assertSame('b', RubrikWawancara::pita(71));
        $this->assertSame('c', RubrikWawancara::pita(70));
        $this->assertSame('c', RubrikWawancara::pita('60'));
        $this->assertSame('d', RubrikWawancara::pita(59.99));
        $this->assertNull(RubrikWawancara::pita(null));
        $this->assertNull(RubrikWawancara::pita(''));
    }

    public function test_every_criterion_describes_every_band(): void
    {
        $this->assertSame(['gaya_wawancara', 'penguasaan_materi', 'kemampuan_hadapi_pertanyaan'], array_keys(RubrikWawancara::KRITERIA));
        foreach (RubrikWawancara::KRITERIA as $k) {
            $this->assertSame(['a', 'b', 'c', 'd'], array_keys($k['deskripsi']));
        }
        $this->assertSame('71 ≤ N ≤ 80', RubrikWawancara::label('b'));
        $this->assertStringStartsWith('Sangat menguasai', RubrikWawancara::deskripsi('penguasaan_materi', 85));
        $this->assertNull(RubrikWawancara::deskripsi('hasil_worksheet', 85));
    }
}
