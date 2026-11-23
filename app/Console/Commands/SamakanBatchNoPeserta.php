<?php

namespace App\Console\Commands;

use App\Models\AssessmentApplication;
use App\Models\ExamSession;
use App\Models\ParticipantResult;
use App\Services\StudentEnrollmentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Samakan segmen batch No. Peserta dengan Kode Batch sesi, mis. sesi diubah dari "02" ke "002" setelah peserta disetujui:
 * QCA.02.2026.00001 → QCA.002.2026.00001 (nomor urut & tahun tetap; bila sudah dipakai, nomor urut berikutnya).
 * Batch di permohonan sesi itu ikut disamakan. Peserta yang sudah Final dilewati (nomornya sudah tercetak di dokumen).
 *
 *   php artisan no-peserta:samakan-batch 93 94 95 --dry-run
 *   php artisan no-peserta:samakan-batch            (semua sesi)
 *
 * No. Peserta = login ujian: beri tahu peserta nomor barunya (daftar nomor lama → baru ada di keluaran perintah).
 */
class SamakanBatchNoPeserta extends Command
{
    protected $signature = 'no-peserta:samakan-batch
        {sesi?* : ID sesi (kosong = semua sesi)}
        {--dry-run : Tampilkan rencana tanpa menyimpan}';

    protected $description = 'Samakan batch No. Peserta & permohonan dengan Kode Batch sesi';

    public function handle(StudentEnrollmentService $enrollment): int
    {
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
            $applications = AssessmentApplication::with('student')->where('exam_session_id', $session->id)->get();

            foreach ($applications as $application) {
                if ($application->kode_batch !== $batch) {
                    $apps++;
                    if (! $this->option('dry-run')) {
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
                    $rows[] = ["#{$session->id}", $student->name, $student->no_participant, '—', 'dilewati: sudah Final'];

                    continue;
                }

                [$kode, , $tahun, $urut] = $parts;
                $baru = $enrollment->nextNoParticipant($kode, $batch, $tahun, (int) $urut, $reserved);
                $reserved[] = $baru;
                $rows[] = ["#{$session->id}", $student->name, $student->no_participant, $baru, $this->option('dry-run') ? 'akan diubah' : 'diubah'];

                if (! $this->option('dry-run')) {
                    DB::transaction(fn () => $student->update(['no_participant' => $baru]));
                }
            }
        }

        if ($rows) {
            $this->table(['Sesi', 'Peserta', 'No. Peserta lama', 'No. Peserta baru', 'Tindakan'], $rows);
        } else {
            $this->info('Tidak ada No. Peserta yang batch-nya berbeda dengan Kode Batch sesi.');
        }
        $this->line("Permohonan yang batch-nya disamakan: {$apps}.");

        if ($this->option('dry-run')) {
            $this->warn('Dry run — tidak ada yang disimpan.');
        } elseif (collect($rows)->contains(fn ($r) => $r[4] === 'diubah')) {
            $this->warn('No. Peserta = login ujian: beri tahu peserta di atas nomor barunya.');
        }

        return self::SUCCESS;
    }
}
