<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Support\UjianPraktik;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\CreatesExamFixtures;
use Tests\TestCase;

/**
 * Database yang belum menjalankan migrasi 2026_11_21_000001 masih menyimpan tipe "Ujian Praktik": ujian itu tetap dikenali
 * sebagai Ujian Praktik/Keterampilan — peserta tidak jatuh ke "Upload Tugas", tombol penilaian asesor tetap ada.
 */
class UjianPraktikTipeLamaTest extends TestCase
{
    use RefreshDatabase, CreatesExamFixtures;

    public function test_tipe_lama_tetap_dikenali_sebagai_ujian_praktik(): void
    {
        $classroom = $this->makeClassroom('K3L');
        $pg = $this->makeExam($classroom, 'Pilihan Ganda');
        $praktik = $this->makeExam($classroom, Exam::TYPE_PRAKTIK, 120);
        DB::table('exams')->where('id', $praktik->id)->update(['type' => Exam::TYPE_PRAKTIK_LAMA]);   // belum dimigrasi
        $session = $this->makeSession($pg);
        $session->forceFill(['exam_id_praktik' => $praktik->id])->save();
        $student = $this->makeStudent($classroom);
        $this->enroll($student, $pg, $session);

        $praktik = $praktik->fresh();
        $this->assertSame(Exam::TYPE_PRAKTIK, $praktik->type);
        $this->assertTrue($praktik->isPraktik());
        $this->assertSame([1], array_keys(UjianPraktik::parts($session->id)));
        $this->assertSame('Ujian Praktik/Keterampilan', UjianPraktik::tugasStatus($session->id, $student->id)['label']);
        $this->assertSame(Exam::TYPE_PRAKTIK, $praktik->toArray()['type']);                          // halaman Vue

        // Dashboard peserta: Ujian Praktik/Keterampilan tampil sebagai kartu sendiri
        $this->actingAsStudent($student)
            ->get('/student/dashboard')
            ->assertInertia(fn ($page) => $page
                ->component('Student/Dashboard/Index')
                ->has('praktik_cards', 1)
                ->where('praktik_cards.0.bagian', 1)
                ->where('praktik_cards.0.title', $praktik->title)
                ->where('praktik_cards.0.durasi', 120)
                ->where('praktik_cards.0.started', false)
                ->where('praktik_cards.0.exam_session.id', $session->id)
                ->where("tugas_label_by_session.{$session->id}", 'Ujian Praktik/Keterampilan'));
    }
}
