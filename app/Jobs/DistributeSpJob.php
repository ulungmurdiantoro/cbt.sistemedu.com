<?php

namespace App\Jobs;

use App\Models\ParticipantResult;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Orchestrator distribusi SP (tahap 1) satu sesi — dijadwalkan terpisah dari
 * DistributeResultsJob (tahap 2, SK+Sertifikat) supaya peserta bisa memeriksa
 * & mengajukan revisi data sebelum dokumen final diterbitkan.
 */
class DistributeSpJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $examSessionId,
    ) {}

    public function handle(): void
    {
        ParticipantResult::where('exam_session_id', $this->examSessionId)
            ->where('is_finalized', true)
            ->whereNull('sp_distributed_at')
            ->pluck('id')
            ->each(fn ($id) => SendSpMailJob::dispatch($id));
    }
}
