<?php

namespace App\Console\Commands;

use App\Models\Answer;
use App\Models\AnswerEssay;
use App\Models\ExamGroup;
use App\Models\ExamSession;
use App\Models\Grade;
use App\Models\ParticipantResult;
use App\Models\Student;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Kompensasi: buka lagi satu ujian (Esai atau PG) yang sudah diakhiri untuk satu peserta, mis. lupa menyimpan jawaban esai.
 *   php artisan ujian:beri-ulang 88 ToT.015.2026.00012 --ujian=esai --alasan="lupa simpan jawaban" --dry-run
 * - Waktu mulai/selesai dikosongkan & sisa waktu kembali penuh (atau --menit=N) → peserta menekan Kerjakan lagi di dashboard.
 * - Jawaban yang sudah tersimpan TIDAK dihapus (dilanjutkan). Nilai esai dari asesor dikosongkan supaya dinilai ulang.
 * - Hasil sesi peserta (belum Final) dikosongkan nilainya & verifikasi PK/MS-nya; yang sudah Final ditolak.
 * - Dicatat di tabel exam_retakes. Ujian lain peserta & peserta lain tidak tersentuh.
 * Jendela sesi hanya dicek di browser: bila jam selesai sesi sudah lewat, perpanjang dulu di Edit Sesi.
 */
class BeriUlangUjian extends Command
{
    protected $signature = 'ujian:beri-ulang
        {sesi : ID sesi}
        {no_peserta : No. Peserta (login ujian)}
        {--ujian=esai : esai atau pg}
        {--alasan= : Alasan kompensasi (wajib)}
        {--menit= : Durasi pengerjaan ulang (menit); bawaan = durasi ujian}
        {--dry-run : Tampilkan rencana tanpa menyimpan}';

    protected $description = 'Beri peserta kesempatan mengerjakan ulang satu ujian (kompensasi)';

    public function handle(): int
    {
        $jenis = strtolower((string) $this->option('ujian'));
        if (! in_array($jenis, ['esai', 'pg'], true)) {
            $this->error('--ujian harus esai atau pg.');

            return self::FAILURE;
        }
        $alasan = trim((string) $this->option('alasan'));
        if ($alasan === '') {
            $this->error('Isi --alasan="…" (dicatat sebagai riwayat kompensasi).');

            return self::FAILURE;
        }

        $session = ExamSession::find($this->argument('sesi'));
        if (! $session) {
            $this->error('Sesi tidak ditemukan.');

            return self::FAILURE;
        }
        $examId = $jenis === 'esai' ? $session->exam_id_esai : $session->exam_id_pg;
        if (! $examId) {
            $this->error("Sesi #{$session->id} tidak memakai ujian {$jenis}.");

            return self::FAILURE;
        }

        $student = Student::where('no_participant', $this->argument('no_peserta'))->first();
        if (! $student || ! ExamGroup::where('exam_session_id', $session->id)->where('student_id', $student->id)->where('exam_id', $examId)->exists()) {
            $this->error("Peserta {$this->argument('no_peserta')} tidak terdaftar di ujian {$jenis} sesi #{$session->id}.");

            return self::FAILURE;
        }

        $grade = Grade::with('exam')->where('exam_session_id', $session->id)->where('student_id', $student->id)->where('exam_id', $examId)->first();
        if (! $grade || ! $grade->start_time) {
            $this->warn('Peserta belum memulai ujian ini — tidak perlu dibuka ulang, cukup tekan Kerjakan di dashboard.');

            return self::SUCCESS;
        }

        $result = ParticipantResult::where('exam_session_id', $session->id)->where('student_id', $student->id)->first();
        if ($result?->is_finalized) {
            $this->error('Hasil peserta sudah Final — tidak bisa dibuka ulang.');

            return self::FAILURE;
        }

        $menit = $this->option('menit') !== null ? (int) $this->option('menit') : (int) $grade->exam->duration;
        if ($menit <= 0) {
            $this->error('--menit harus lebih dari 0.');

            return self::FAILURE;
        }

        $jawaban = $jenis === 'esai'
            ? AnswerEssay::where('exam_session_id', $session->id)->where('student_id', $student->id)->where('exam_id', $examId)
                ->whereNotNull('answer')->where('answer', '!=', '')->count()
            : Answer::where('exam_session_id', $session->id)->where('student_id', $student->id)->where('exam_id', $examId)
                ->whereNotNull('answer')->where('answer', '!=', 0)->count();

        $this->table(['', ''], [
            ['Sesi', "#{$session->id} {$session->title}"],
            ['Peserta', "{$student->no_participant} — {$student->name}"],
            ['Ujian', $grade->exam->title],
            ['Mulai / selesai sebelumnya', ($grade->start_time ?: '—') . ' / ' . ($grade->end_time ?: '—')],
            ['Jawaban tersimpan (tetap)', $jawaban],
            ['Waktu pengerjaan ulang', "{$menit} menit"],
            ['Jam selesai sesi', (string) $session->end_time],
            ['Alasan', $alasan],
        ]);

        if ($session->end_time && now()->greaterThan($session->end_time)) {
            $this->warn('Jam selesai sesi sudah lewat: dashboard peserta akan menampilkan "Waktu Terlewat". Perpanjang jam selesai sesi di Edit Sesi.');
        }

        if ($this->option('dry-run')) {
            $this->warn('Dry run — tidak ada yang disimpan.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($session, $student, $grade, $examId, $jenis, $menit, $alasan, $jawaban, $result) {
            DB::table('exam_retakes')->insert([
                'exam_session_id'       => $session->id,
                'student_id'            => $student->id,
                'exam_id'               => $examId,
                'alasan'                => $alasan,
                'durasi_menit'          => $menit,
                'start_time_sebelumnya' => $grade->start_time,
                'end_time_sebelumnya'   => $grade->end_time,
                'jawaban_tersimpan'     => $jawaban,
                'created_at'            => now(),
                'updated_at'            => now(),
            ]);

            $grade->forceFill([
                'start_time' => null,
                'end_time'   => null,
                'duration'   => $menit * 60000,
                'grade'      => $jenis === 'esai' ? 0 : $grade->grade,
            ])->save();

            if ($jenis === 'esai') {
                // Jawaban tetap; nilai asesor dikosongkan supaya dinilai ulang setelah peserta selesai
                AnswerEssay::where('exam_session_id', $session->id)->where('student_id', $student->id)->where('exam_id', $examId)
                    ->update(['score' => null, 'assessed_by' => null, 'assessed_at' => null]);
            }

            $result?->update([
                $jenis === 'esai' ? 'nilai_esai' : 'nilai_pg' => null,
                'nilai_akhir'              => null,
                'keputusan'                => null,
                'manager_verified_at'      => null,
                'manager_verified_by'      => null,
                'cert_manager_verified_at' => null,
                'cert_manager_verified_by' => null,
            ]);
        });

        $this->info("Ujian {$jenis} dibuka ulang untuk {$student->no_participant}. Peserta login lalu menekan Kerjakan di kartu ujiannya.");

        return self::SUCCESS;
    }
}
