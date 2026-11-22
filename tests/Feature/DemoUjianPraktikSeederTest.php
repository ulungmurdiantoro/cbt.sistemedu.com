<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AsesorAssignment;
use App\Models\ExamGroup;
use App\Models\ExamSession;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\DemoUjianPraktikSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Feature\Concerns\CreatesExamFixtures;
use Tests\TestCase;

/** Seeder peserta dummy: sesi DEMO per skema V2 yang terpasang, dua peserta, asesor demo; aman dijalankan ulang. */
class DemoUjianPraktikSeederTest extends TestCase
{
    use RefreshDatabase, CreatesExamFixtures;

    public function test_sesi_dan_peserta_demo_untuk_skema_v2_yang_terpasang(): void
    {
        Storage::fake('public');
        Storage::fake('private');
        $templates = sys_get_temp_dir() . '/demo-seeder-' . Str::random(6);
        File::ensureDirectoryExists($templates);
        File::put("{$templates}/hse.docx", 'isi');
        File::put("{$templates}/lifting-calculation.zip", 'isi');
        File::put("{$templates}/lifting-gambar.zip", 'isi');
        $this->makeClassroom('K3L');
        $this->makeClassroom('LHC');
        $this->artisan('paket-soal:pasang', ['versi' => 'v2', '--skema' => ['hse', 'lifting'], '--force' => true, '--templates' => $templates])
            ->assertSuccessful();

        $this->seed(DemoUjianPraktikSeeder::class);
        $this->seed(DemoUjianPraktikSeeder::class);              // dijalankan ulang: tidak dobel

        $hse = ExamSession::where('exam_sessions_code', 'DEMO-V2-HSE')->firstOrFail();
        $this->assertNotNull($hse->exam_id_esai);
        $this->assertNotNull($hse->exam_id_praktik);
        $lifting = ExamSession::where('exam_sessions_code', 'DEMO-V2-LIFTING')->firstOrFail();
        $this->assertNull($lifting->exam_id_esai);
        $this->assertNotNull($lifting->exam_id_praktik_2);       // dua bagian
        $this->assertSame(2, ExamSession::where('exam_sessions_code', 'like', 'DEMO-V2-%')->count());   // skema lain belum terpasang

        $student = Student::where('no_participant', 'DEMO-HSE-01')->firstOrFail();
        $this->assertSame(2, ExamGroup::where('exam_session_id', $hse->id)->where('student_id', $student->id)->count());  // PG + esai
        $this->assertSame(4, Student::where('no_participant', 'like', 'DEMO-%')->count());

        $asesor = User::where('email', 'asesor.demo@cbt.test')->firstOrFail();
        $this->assertTrue($asesor->hasRole(UserRole::Asesor));
        $this->assertSame(4, AsesorAssignment::where('user_id', $asesor->id)->count());

        // Peserta bisa login ujian dengan No. Peserta dan melihat halaman Ujian Praktik/Keterampilan
        $this->actingAsStudent($student)->get(route('student.praktik.confirmation', $hse->id))->assertOk();

        File::deleteDirectory($templates);
    }
}
