<?php

namespace App\Jobs;

use App\Models\ParticipantResult;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Orchestrator distribusi hasil satu sesi. Hanya menjadwalkan satu
 * SendResultMailJob per peserta yang sudah final & belum didistribusi.
 */
class DistributeResultsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $examSessionId,
        public readonly string $versi = 'with_kop',
    ) {}

    public function handle(): void
    {
        ParticipantResult::where('exam_session_id', $this->examSessionId)
            ->where('is_finalized', true)
            ->whereNull('distributed_at')
            ->pluck('id')
            ->each(fn($id) => SendResultMailJob::dispatch($id, $this->versi));
    }
}
