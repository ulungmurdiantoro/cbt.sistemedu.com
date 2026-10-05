<?php

namespace Tests\Feature\Asesor;

use App\Enums\UserRole;
use App\Models\AsesorAssignment;
use App\Models\AssessmentApplication;
use App\Models\Participant;
use App\Models\User;
use App\Models\UserRoleAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Feature\Concerns\CreatesExamFixtures;
use Tests\TestCase;

/** Membuat / mengganti TTD langsung dari halaman TTD AK.01 asesor. */
class TtdAk01SignatureTest extends TestCase
{
    use RefreshDatabase, CreatesExamFixtures;

    private function pngDataUrl(): string
    {
        $img = imagecreatetruecolor(40, 20);
        imagefill($img, 0, 0, imagecolorallocate($img, 255, 255, 255));
        imageline($img, 2, 10, 38, 10, imagecolorallocate($img, 0, 0, 0));
        ob_start();
        imagepng($img);

        return 'data:image/png;base64,' . base64_encode(ob_get_clean());
    }

    public function test_asesor_can_replace_signature_without_changing_signed_ak01(): void
    {
        Storage::fake('private');

        $classroom = $this->makeClassroom();
        $session   = $this->makeSession($this->makeExam($classroom));
        $signed    = $this->makeStudent($classroom);
        $pending   = $this->makeStudent($classroom);

        $asesor = User::forceCreate([
            'users_code' => 'ASR-' . Str::random(6),
            'name'       => 'Asesor Test',
            'email'      => Str::random(8) . '@example.com',
            'password'   => bcrypt('password'),
        ]);
        UserRoleAssignment::forceCreate(['user_id' => $asesor->id, 'role' => UserRole::Asesor->value]);

        $apps = [];
        foreach ([$signed, $pending] as $student) {
            AsesorAssignment::forceCreate(['user_id' => $asesor->id, 'exam_session_id' => $session->id, 'student_id' => $student->id]);
            $participant = Participant::forceCreate(['name' => $student->name, 'email' => Str::random(8) . '@example.com', 'password' => bcrypt('x')]);
            $apps[] = AssessmentApplication::forceCreate([
                'code'            => 'APL-' . Str::random(8),
                'participant_id'  => $participant->id,
                'classroom_id'    => $classroom->id,
                'exam_session_id' => $session->id,
                'student_id'      => $student->id,
                'kode_batch'      => '-',
                'tujuan_asesmen'  => 'Sertifikasi',
                'status'          => 'approved',
            ]);
        }

        $page = "/asesor/penilaian/{$session->id}/ttd-ak01";

        // Belum punya TTD → buat dari halaman ini
        $this->actingAs($asesor)->get($page)->assertInertia(fn (Assert $p) => $p->where('has_signature', false));
        $this->actingAs($asesor)->from($page)->post('/asesor/tanda-tangan', ['signature_data' => $this->pngDataUrl()])
            ->assertRedirect($page);
        $first = $asesor->fresh()->signature_path;
        Storage::disk('private')->assertExists($first);
        $this->actingAs($asesor)->get($page)->assertInertia(fn (Assert $p) => $p->where('has_signature', true));

        // Tandatangani AK.01 peserta pertama dengan TTD lama
        $this->actingAs($asesor)->post("{$page}/{$signed->id}")->assertSessionHasNoErrors();
        $this->assertSame($first, $apps[0]->fresh()->asesor_signature_path);

        // Ganti TTD → file baru; AK.01 yang sudah ditandatangani tetap memakai TTD lama
        $this->travel(2)->seconds();
        $this->actingAs($asesor)->from($page)->post('/asesor/tanda-tangan', ['signature_data' => $this->pngDataUrl()]);
        $second = $asesor->fresh()->signature_path;
        $this->assertNotSame($first, $second);
        $this->assertSame($first, $apps[0]->fresh()->asesor_signature_path);
        Storage::disk('private')->assertExists($first);

        // AK.01 berikutnya memakai TTD baru
        $this->actingAs($asesor)->post("{$page}/{$pending->id}")->assertSessionHasNoErrors();
        $this->assertSame($second, $apps[1]->fresh()->asesor_signature_path);
    }
}
