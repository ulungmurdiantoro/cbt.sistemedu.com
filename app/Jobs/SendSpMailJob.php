<?php

namespace App\Jobs;

use App\Mail\SpDistributionMail;
use App\Models\ParticipantResult;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

/**
 * Mengirim SP (tahap 1) ke satu peserta. Dipisah per peserta agar kegagalan
 * pada satu email tidak memicu pengiriman ulang ke peserta lain — sama pola
 * dengan SendResultMailJob (tahap 2, SK+Sertifikat).
 */
class SendSpMailJob implements ShouldQueue
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

        if (!$result || !$result->is_finalized || !$result->sp_number) {
            return;
        }

        // idempotent: lewati jika sudah pernah dikirim
        if ($result->sp_distributed_at) {
            return;
        }

        $email = $result->student?->participant?->email;
        if (!$email) {
            return;
        }

        Mail::to($email)->send(new SpDistributionMail($result));

        $result->update(['sp_distributed_at' => now()]);
    }
}
