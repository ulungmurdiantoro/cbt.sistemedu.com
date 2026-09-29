<?php

namespace Tests\Feature;

use App\Models\ParticipantResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\CreatesExamFixtures;
use Tests\TestCase;

class AuditNumberingTest extends TestCase
{
    use RefreshDatabase, CreatesExamFixtures;

    public function test_reports_missing_and_duplicate_numbers(): void
    {
        $classroom = $this->makeClassroom();
        $session   = $this->makeSession($this->makeExam($classroom));

        foreach (['001', '002', '002', '005'] as $i => $seq) {
            ParticipantResult::forceCreate([
                'exam_session_id' => $session->id,
                'student_id'      => $this->makeStudent($classroom)->id,
                'is_finalized'    => true,
                'finalized_at'    => now(),
                'sp_number'       => "{$seq}/SPT/EDUKIA/IX/2026",
                'sk_number'       => sprintf('%03d/SK-SP/LSP-EDUKIA/IX/2026', $i + 1),
            ]);
        }

        DB::table('numbering_counters')->insert([
            ['type' => 'sp', 'scope' => null, 'year' => 2026, 'last_number' => 6],
            ['type' => 'sk', 'scope' => null, 'year' => 2026, 'last_number' => 4],
        ]);

        $this->artisan('numbering:audit', ['--type' => ['sp']])
            ->expectsOutputToContain('3 nomor tidak dipakai siapa pun: 003–004, 006')
            ->expectsOutputToContain('Nomor urut 002 dipakai 2 kali')
            ->assertSuccessful();

        $this->artisan('numbering:audit', ['--type' => ['sk']])
            ->expectsOutputToContain('Tidak ada nomor yang hilang.')
            ->assertSuccessful();
    }
}
