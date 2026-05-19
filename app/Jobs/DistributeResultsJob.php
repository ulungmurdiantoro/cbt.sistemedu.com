<?php

namespace App\Jobs;

use App\Mail\ResultDistributionMail;
use App\Models\ExamSession;
use App\Models\ParticipantResult;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class DistributeResultsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $examSessionId,
        public readonly string $versi = 'with_kop',
    ) {}

    public function handle(): void
    {
        $results = ParticipantResult::where('exam_session_id', $this->examSessionId)
            ->where('is_finalized', true)
            ->with(['student.participant', 'examSession'])
            ->get();

        foreach ($results as $result) {
            $email = $result->student?->participant?->email;
            if (!$email) continue;

            Mail::to($email)->send(new ResultDistributionMail($result, $this->versi));

            $result->update(['distributed_at' => now()]);
        }
    }
}
