<?php

namespace App\Console\Commands;

use App\Mail\NoPesertaChangedMail;
use App\Models\AssessmentApplication;
use App\Models\ExamSession;
use App\Models\ParticipantResult;
use App\Services\StudentEnrollmentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Samakan segmen batch No. Peserta dengan Kode Batch sesi, mis. sesi diubah dari "02" ke "002" setelah peserta disetujui:
 * QCA.02.2026.00001 → QCA.002.2026.00001 (nomor urut & tahun tetap; bila sudah dipakai, nomor urut berikutnya).
 * Batch di permohonan sesi itu ikut disamakan. Peserta yang sudah Final dilewati (nomornya sudah tercetak di dokumen).
 *
 *   php artisan no-peserta:samakan-batch 93 94 95 --dry-run
 *   php artisan no-peserta:samakan-batch 93 94 95 --kirim-email   (+ email nomor baru ke peserta, NoPesertaChangedMail)
 *   php artisan no-peserta:samakan-batch                          (semua sesi)
 *
 * No. Peserta = login ujian: tanpa --kirim-email, beri tahu peserta sendiri (daftar nomor lama → baru ada di keluaran).
 */
class SamakanBatchNoPeserta extends Command
{
    protected $signature = 'no-peserta:samakan-batch
        {sesi?* : ID sesi (kosong = semua sesi)}
        {--kirim-email : Kirim email No. Peserta baru ke peserta yang nomornya diubah}
        {--dry-run : Tampilkan rencana tanpa menyimpan (dan tanpa mengirim email)}';

    protected $description = 'Samakan batch No. Peserta & permohonan dengan Kode Batch sesi';

    public function handle(StudentEnrollmentService $enrollment): int
    {
        $dry   = (bool) $this->option('dry-run');
        $email = (bool) $this->option('kirim-email');

        $ids = $this->argument('sesi');
        $sessions = ExamSession::query()
            ->when($ids, fn ($q) => $q->whereIn('id', $ids))
            ->whereNotNull('kode_batch')->where('kode_batch', '!=', '')
            ->orderBy('id')
            ->get();

        $rows = [];
        $apps = 0;
        $reserved = [];   // nomor baru yang sudah direncanakan (dry-run belum menyimpannya)
        foreach ($sessions as $session) {
            $batch = trim($session->kode_batch);
            $applications = AssessmentApplication::with('student', 'participant', 'classroom', 'examSession')
                ->where('exam_session_id', $session->id)->get();

            foreach ($applications as $application) {
                if ($application->kode_batch !== $batch) {
                    $apps++;
                    if (! $dry) {
                        $application->update(['kode_batch' => $batch]);
                    }
                }

                $student = $application->student;
                $parts = $student ? explode('.', (string) $student->no_participant) : [];
                if (! $student || count($parts) !== 4 || $parts[1] === $batch) {
                    continue;
                }

                $final = ParticipantResult::where('exam_session_id', $session->id)
                    ->where('student_id', $student->id)->where('is_finalized', true)->exists();
                if ($final) {
                    $rows[] = ["#{$session->id}", $student->name, $student->no_participant, '—', 'dilewati: sudah Final', '—'];

                    continue;
                }

                [$kode, , $tahun, $urut] = $parts;
                $lama = $student->no_participant;
                $baru = $enrollment->nextNoParticipant($kode, $batch, $tahun, (int) $urut, $reserved);
                $reserved[] = $baru;
                $alamat = $application->participant?->email;

                $status = '—';
                if (! $dry) {
                    $student->update(['no_participant' => $baru]);
                    if ($email) {
                        $status = $this->kirim($application, $lama, $baru);
                    }
                } elseif ($email) {
                    $status = $alamat ? "akan dikirim ke {$alamat}" : 'tanpa email';
                }

                $rows[] = ["#{$session->id}", $student->name, $lama, $baru, $dry ? 'akan diubah' : 'diubah', $status];
            }
        }

        if ($rows) {
            $this->table(['Sesi', 'Peserta', 'No. Peserta lama', 'No. Peserta baru', 'Tindakan', 'Email'], $rows);
        } else {
            $this->info('Tidak ada No. Peserta yang batch-nya berbeda dengan Kode Batch sesi.');
        }
        $this->line("Permohonan yang batch-nya disamakan: {$apps}.");

        if ($dry) {
            $this->warn('Dry run — tidak ada yang disimpan dan tidak ada email yang dikirim.');
        } elseif (collect($rows)->contains(fn ($r) => $r[4] === 'diubah')) {
            $email
                ? $this->warn('Peserta yang emailnya gagal / tidak ada di kolom Email: beri tahu nomor barunya secara langsung.')
                : $this->warn('No. Peserta = login ujian: beri tahu peserta di atas nomor barunya (atau jalankan dengan --kirim-email).');
        }

        return self::SUCCESS;
    }

    /** Kirim email nomor baru; gagal kirim tidak menghentikan perintah (nomor tetap sudah diubah). */
    private function kirim(AssessmentApplication $application, string $lama, string $baru): string
    {
        $alamat = $application->participant?->email;
        if (! $alamat) {
            return 'tanpa email';
        }

        try {
            Mail::to($alamat)->send(new NoPesertaChangedMail($application->fresh(['participant', 'classroom', 'examSession', 'student']), $lama, $baru));

            return "terkirim ke {$alamat}";
        } catch (\Throwable $e) {
            return 'gagal: ' . mb_strimwidth($e->getMessage(), 0, 60, '…');
        }
    }
}
