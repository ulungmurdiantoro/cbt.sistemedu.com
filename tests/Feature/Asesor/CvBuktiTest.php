<?php

namespace Tests\Feature\Asesor;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\UserRoleAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/** Bukti dokumen (opsional) per baris CV asesor. */
class CvBuktiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('private');
    }

    private function asesor(): User
    {
        $user = User::forceCreate([
            'users_code' => 'ASR-' . Str::random(6),
            'name'       => 'Asesor Test',
            'email'      => Str::random(8) . '@example.com',
            'password'   => bcrypt('password'),
        ]);

        UserRoleAssignment::forceCreate(['user_id' => $user->id, 'role' => UserRole::Asesor->value]);

        return $user;
    }

    private function pdf(string $name = 'ijazah.pdf'): UploadedFile
    {
        return UploadedFile::fake()->create($name, 100, 'application/pdf');
    }

    private function bukti(User $user, string $section = 'pendidikan_formal', int $row = 0): ?array
    {
        return $user->fresh()->cv->{$section}[$row]['bukti'] ?? null;
    }

    public function test_rows_can_be_saved_without_bukti(): void
    {
        $user = $this->asesor();

        $this->actingAs($user)
            ->post('/asesor/cv', ['pelatihan' => [['judul_kegiatan' => 'Pelatihan Asesor', 'bukti_id' => '']]])
            ->assertSessionHasNoErrors();

        $this->assertSame('Pelatihan Asesor', $user->fresh()->cv->pelatihan[0]['judul_kegiatan']);
        $this->assertNull($this->bukti($user, 'pelatihan'));
    }

    public function test_bukti_is_stored_privately_and_only_id_and_name_reach_the_page(): void
    {
        $user = $this->asesor();

        $this->actingAs($user)
            ->post('/asesor/cv', ['pendidikan_formal' => [['jenjang' => 'S1', 'bukti_file' => $this->pdf()]]])
            ->assertSessionHasNoErrors();

        $bukti = $this->bukti($user);
        $this->assertSame('ijazah.pdf', $bukti['name']);
        Storage::disk('private')->assertExists($bukti['path']);

        $this->actingAs($user)->get('/asesor/cv')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('cv.pendidikan_formal.0.bukti', ['id' => $bukti['id'], 'name' => 'ijazah.pdf']));
    }

    public function test_kept_bukti_survives_save_and_removed_bukti_is_deleted(): void
    {
        $user = $this->asesor();
        $this->actingAs($user)->post('/asesor/cv', ['pendidikan_formal' => [
            ['jenjang' => 'S1', 'bukti_file' => $this->pdf('s1.pdf')],
            ['jenjang' => 'S2', 'bukti_file' => $this->pdf('s2.pdf')],
        ]]);
        [$s1, $s2] = [$this->bukti($user, row: 0), $this->bukti($user, row: 1)];

        // S1 dipertahankan (id dikirim balik), bukti S2 dihapus.
        $this->actingAs($user)->post('/asesor/cv', ['pendidikan_formal' => [
            ['jenjang' => 'S1', 'bukti_id' => $s1['id']],
            ['jenjang' => 'S2', 'bukti_id' => ''],
        ]])->assertSessionHasNoErrors();

        $this->assertSame($s1, $this->bukti($user, row: 0));
        $this->assertNull($this->bukti($user, row: 1));
        Storage::disk('private')->assertExists($s1['path']);
        Storage::disk('private')->assertMissing($s2['path']);
    }

    public function test_unknown_bukti_id_is_ignored(): void
    {
        $other = $this->asesor();
        $this->actingAs($other)->post('/asesor/cv', ['pendidikan_formal' => [['jenjang' => 'S1', 'bukti_file' => $this->pdf()]]]);
        $othersBukti = $this->bukti($other);

        $user = $this->asesor();
        $this->actingAs($user)
            ->post('/asesor/cv', ['pendidikan_formal' => [['jenjang' => 'S1', 'bukti_id' => $othersBukti['id']]]])
            ->assertSessionHasNoErrors();

        $this->assertNull($this->bukti($user));
        $this->actingAs($user)->get("/asesor/cv/bukti/{$othersBukti['id']}")->assertNotFound();
    }

    public function test_owner_can_open_bukti(): void
    {
        $user = $this->asesor();
        $this->actingAs($user)->post('/asesor/cv', ['sertifikasi_kompetensi' => [['jenis_sertifikasi' => 'Asesor', 'bukti_file' => $this->pdf()]]]);
        $bukti = $this->bukti($user, 'sertifikasi_kompetensi');

        $response = $this->actingAs($user)->get("/asesor/cv/bukti/{$bukti['id']}")
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->assertStringStartsWith('inline', $response->headers->get('Content-Disposition'));
    }

    public function test_bukti_must_be_pdf_or_image(): void
    {
        $user = $this->asesor();

        $this->actingAs($user)
            ->post('/asesor/cv', ['pelatihan' => [[
                'judul_kegiatan' => 'X',
                'bukti_file'     => UploadedFile::fake()->createWithContent('shell.php', '<?php echo 1;'),
            ]]])
            ->assertSessionHasErrors('pelatihan.0.bukti_file');

        $this->assertNull($user->fresh()->cv);
    }
}
