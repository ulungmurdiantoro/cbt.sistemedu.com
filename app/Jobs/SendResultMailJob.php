<?php

namespace App\Jobs;

use App\Mail\ResultDistributionMail;
use App\Models\ParticipantResult;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

/**
 * Mengirim email hasil ke satu peserta. Dipisah per peserta agar kegagalan
 * pada satu email tidak memicu pengiriman ulang ke peserta lain.
 */
class SendResultMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly int $participantResultId,
    ) {}

    public function handle(): void
    {
        $result = ParticipantResult::with(['student.participant', 'examSession'])
            ->find($this->participantResultId);

        if (!$result || !$result->is_finalized) {
            return;
        }

        // idempotent: lewati jika sudah pernah didistribusi
        if ($result->distributed_at) {
            return;
        }

        $email = $result->student?->participant?->email;
        if (!$email) {
            return;
        }

        Mail::to($email)->send(new ResultDistributionMail($result));

        $result->update(['distributed_at' => now()]);
    }
}
