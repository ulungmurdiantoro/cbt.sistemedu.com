<?php

namespace Tests\Feature\Admin;

use App\Models\ParticipantResult;
use App\Services\DocumentGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\Feature\Concerns\CreatesExamFixtures;
use Tests\TestCase;

/**
 * SP/SK yang sudah dikirim di-cache sebagai file jadi. Koreksi data peserta di menu Peserta (tujuan tahap SP:
 * revisi typo nama) harus membuat PDF-nya dibuat ulang — nomor & tanggal dokumen tetap.
 */
class DocumentNameCorrectionTest extends TestCase
{
    use RefreshDatabase, CreatesExamFixtures;

    private ParticipantResult $result;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $classroom = $this->makeClassroom();
        $session   = $this->makeSession($this->makeExam($classroom));
        $student   = $this->makeStudent($classroom);
        $student->forceFill(['name' => 'Budi Santosa'])->save();

        $this->result = ParticipantResult::forceCreate([
            'exam_session_id'   => $session->id,
            'student_id'        => $student->id,
            'keputusan'         => 'LULUS',
            'nilai_akhir'       => 85,
            'is_finalized'      => true,
            'sp_number'         => '001/SPT/EDUKIA/X/2026',
            'sp_distributed_at' => now(),
            'sk_number'         => '001/SK-SP/LSP-EDUKIA/X/2026',
            'distributed_at'    => now(),
        ]);
    }

    private function pdf(string $method): string
    {
        return app(DocumentGeneratorService::class)->{$method}($this->result->fresh());
    }

    public function test_sp_is_regenerated_after_name_correction(): void
    {
        $this->pdf('spPdf');
        $this->pdf('spPdf');
        $this->assertCount(1, Storage::disk('local')->files('documents/sp'), 'Tanpa perubahan data tetap memakai cache.');

        $this->result->student->update(['name' => 'Budi Santoso']);
        $this->pdf('spPdf');

        $this->assertCount(2, Storage::disk('local')->files('documents/sp'));
    }

    #[RequiresPhpExtension('gd')] // QR code SK
    public function test_sk_is_regenerated_after_name_correction(): void
    {
        $this->pdf('skPdf');
        $this->result->student->update(['name' => 'Budi Santoso']);
        $this->pdf('skPdf');

        $this->assertCount(2, Storage::disk('local')->files('documents/sk'));
    }
}
