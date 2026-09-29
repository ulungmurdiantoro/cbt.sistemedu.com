<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\ExamSession;
use App\Models\ParticipantResult;
use App\Models\User;
use App\Models\UserRoleAssignment;
use App\Services\RemidiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Feature\Concerns\CreatesExamFixtures;
use Tests\TestCase;

class NumberingRepairTest extends TestCase
{
    use RefreshDatabase, CreatesExamFixtures;

    private function userWithRole(UserRole $role): User
    {
        $user = User::forceCreate([
            'users_code' => 'U-' . Str::random(6),
            'name'       => 'User Test',
            'email'      => Str::random(8) . '@example.com',
            'password'   => bcrypt('password'),
        ]);
        UserRoleAssignment::forceCreate(['user_id' => $user->id, 'role' => $role->value]);

        return $user;
    }

    /** Sesi dengan peserta bernomor SK/SP mulai $sk/$sp (urut No. Peserta). */
    private function numberedSession(int $count, int $sk, int $sp, array $extra = []): ExamSession
    {
        $classroom = $this->makeClassroom('SKM-' . Str::random(4));
        $session   = $this->makeSession($this->makeExam($classroom));

        for ($i = 0; $i < $count; $i++) {
            $student = $this->makeStudent($classroom);
            $student->forceFill(['no_participant' => sprintf('NP-%d-%03d', $session->id, $i + 1)])->save();

            ParticipantResult::forceCreate([
                'exam_session_id' => $session->id,
                'student_id'      => $student->id,
                'keputusan'       => 'LULUS',
                'is_finalized'    => true,
                'finalized_at'    => now(),
                'sk_number'       => sprintf('%03d/SK-SP/LSP-EDUKIA/IX/2026', $sk + $i),
                'sp_number'       => sprintf('%03d/SPT/EDUKIA/IX/2026', $sp + $i),
                ...$extra,
            ]);
        }

        return $session;
    }

    private function setCounters(int $sk, int $sp): void
    {
        DB::table('numbering_counters')->insert([
            ['type' => 'sk', 'scope' => null, 'year' => 2026, 'last_number' => $sk],
            ['type' => 'sp', 'scope' => null, 'year' => 2026, 'last_number' => $sp],
        ]);
    }

    private function counter(string $type): int
    {
        return DB::table('numbering_counters')->where('type', $type)->value('last_number');
    }

    public function test_renumber_closes_gap_and_lowers_counter(): void
    {
        $this->numberedSession(3, 481, 497, []);          // sesi sebelumnya: SK 481–483, SP 497–499
        $session = $this->numberedSession(2, 515, 531);    // loncat: SK 515–516, SP 531–532
        $this->setCounters(516, 532);

        $this->artisan('numbering:renumber-session', ['session' => $session->id, '--sk' => 484, '--sp' => 500, '--force' => true])
            ->assertSuccessful();

        $numbers = ParticipantResult::where('exam_session_id', $session->id)->orderBy('sk_number')->get();
        $this->assertSame(['484/SK-SP/LSP-EDUKIA/IX/2026', '485/SK-SP/LSP-EDUKIA/IX/2026'], $numbers->pluck('sk_number')->all());
        $this->assertSame(['500/SPT/EDUKIA/IX/2026', '501/SPT/EDUKIA/IX/2026'], $numbers->pluck('sp_number')->all());
        $this->assertSame(485, $this->counter('sk'));
        $this->assertSame(501, $this->counter('sp'));
    }

    public function test_renumber_dry_run_changes_nothing(): void
    {
        $session = $this->numberedSession(1, 515, 531);
        $this->setCounters(515, 531);

        $this->artisan('numbering:renumber-session', ['session' => $session->id, '--sk' => 485, '--dry-run' => true])
            ->assertSuccessful();

        $this->assertSame('515/SK-SP/LSP-EDUKIA/IX/2026', ParticipantResult::first()->sk_number);
        $this->assertSame(515, $this->counter('sk'));
    }

    public function test_renumber_refuses_numbers_used_by_other_session(): void
    {
        $this->numberedSession(2, 485, 501);
        $session = $this->numberedSession(1, 515, 531);
        $this->setCounters(515, 531);

        $this->artisan('numbering:renumber-session', ['session' => $session->id, '--sk' => 486, '--force' => true])
            ->expectsOutputToContain('sudah dipakai sesi lain: 486')
            ->assertFailed();
    }

    public function test_renumber_refuses_distributed_documents_unless_allowed(): void
    {
        $session = $this->numberedSession(1, 515, 531, ['sp_distributed_at' => now()]);
        $this->setCounters(515, 531);

        $this->artisan('numbering:renumber-session', ['session' => $session->id, '--sp' => 501, '--force' => true])
            ->assertFailed();
        $this->assertSame('531/SPT/EDUKIA/IX/2026', ParticipantResult::first()->sp_number);

        $this->artisan('numbering:renumber-session', ['session' => $session->id, '--sp' => 501, '--force' => true, '--allow-distributed' => true])
            ->assertSuccessful();
        $this->assertSame('501/SPT/EDUKIA/IX/2026', ParticipantResult::first()->sp_number);
    }

    public function test_normalize_removes_spaces(): void
    {
        $this->numberedSession(1, 473, 489);
        DB::table('participant_results')->update(['sp_number' => '489/SPT/EDUKIA /IX/2026']);

        $this->artisan('numbering:normalize', ['--force' => true])->assertSuccessful();

        $this->assertSame('489/SPT/EDUKIA/IX/2026', ParticipantResult::first()->sp_number);
    }

    public function test_numbers_are_saved_without_spaces(): void
    {
        $this->numberedSession(1, 1, 1);
        ParticipantResult::first()->update(['sp_number' => '002/SPT/EDUKIA /IX/2026']);

        $this->assertSame('002/SPT/EDUKIA/IX/2026', ParticipantResult::first()->sp_number);
    }

    public function test_finalize_numbers_by_no_participant_and_reuses_existing(): void
    {
        $classroom = $this->makeClassroom('SKM-' . Str::random(4));
        $session   = $this->makeSession($this->makeExam($classroom));
        $this->setCounters(10, 20);

        // Dibuat terbalik: baris DB pertama = No. Peserta terakhir.
        foreach (['NP-003', 'NP-001', 'NP-002'] as $no) {
            $student = $this->makeStudent($classroom);
            $student->forceFill(['no_participant' => $no])->save();
            ParticipantResult::forceCreate([
                'exam_session_id'     => $session->id,
                'student_id'          => $student->id,
                'keputusan'           => 'TIDAK_LULUS',
                'manager_verified_at' => now(),
                // NP-002 peserta remidi: masih memegang nomor dari finalisasi pertama.
                'sk_number'           => $no === 'NP-002' ? '005/SK-SP/LSP-EDUKIA/IX/2026' : null,
                'sp_number'           => $no === 'NP-002' ? '015/SPT/EDUKIA/IX/2026' : null,
            ]);
        }

        $this->actingAs($this->userWithRole(UserRole::ManagerSertifikasi))
            ->post("/manager/sertifikasi/{$session->id}/finalize")
            ->assertRedirect();

        $sk = ParticipantResult::with('student')->get()->mapWithKeys(fn ($r) => [$r->student->no_participant => substr($r->sk_number, 0, 3)]);
        $this->assertSame(['NP-001' => '011', 'NP-002' => '005', 'NP-003' => '012'], $sk->sortKeys()->all());
        $this->assertSame(12, $this->counter('sk'));
        $this->assertSame(22, $this->counter('sp'));
    }

    public function test_remidi_keeps_numbers_and_requires_reverification(): void
    {
        $session = $this->numberedSession(1, 7, 9, [
            'keputusan'           => 'TIDAK_LULUS',
            'manager_verified_at' => now(),
            'sp_distributed_at'   => now(),
        ]);
        $result = ParticipantResult::first();

        app(RemidiService::class)->startRemidi($session, $result->student);

        $result->refresh();
        $this->assertSame('007/SK-SP/LSP-EDUKIA/IX/2026', $result->sk_number);
        $this->assertSame('009/SPT/EDUKIA/IX/2026', $result->sp_number);
        $this->assertNull($result->manager_verified_at);
        $this->assertNull($result->sp_distributed_at);
    }

    public function test_cannot_delete_session_or_student_holding_numbers(): void
    {
        $session = $this->numberedSession(1, 1, 1);
        $admin   = $this->userWithRole(UserRole::Admin);

        $this->actingAs($admin)->delete("/admin/exam_sessions/{$session->id}")->assertSessionHasErrors('delete');
        $this->actingAs($admin)->delete('/admin/students/' . ParticipantResult::first()->student_id)->assertSessionHasErrors('delete');

        $this->assertDatabaseHas('exam_sessions', ['id' => $session->id]);
        $this->assertSame(1, ParticipantResult::count());
    }
}
