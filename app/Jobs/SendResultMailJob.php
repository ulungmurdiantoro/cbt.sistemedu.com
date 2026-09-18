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

        // Certification date & Valid until pada Sertifikat mengikuti tanggal
        // dikirimnya SK/Sertifikat ini (bukan tanggal finalisasi PENGAMBIL
        // KEPUTUSAN) — masa berlaku 3 tahun dihitung inklusif tanggal terbit,
        // jadi berakhir sehari SEBELUM tanggal ulang tahun ke-3 (mis. terbit
        // 15 Sep 2026 -> berlaku sampai 14 Sep 2029, bukan 15 Sep 2029).
        //
        // Di-set di memory DULU (belum disimpan) supaya isi email — termasuk
        // baris "Berlaku Hingga" — sudah terisi benar saat dirender & dikirim.
        // Baru disimpan permanen SETELAH kirim berhasil, supaya kalau
        // pengiriman gagal (exception di atas), baris ini tidak keliru
        // tertandai "sudah terdistribusi" (jaga idempotensi retry job ini).
        $now = now();
        $result->distributed_at = $now;
        $result->valid_until    = $now->copy()->addYears(config('lsp.sertifikat_valid_years', 3))->subDay();

        Mail::to($email)->send(new ResultDistributionMail($result));

        $result->save();
    }
}
