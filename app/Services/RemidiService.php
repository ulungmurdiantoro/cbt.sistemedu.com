<?php

namespace App\Services;

use App\Models\Answer;
use App\Models\AnswerEssay;
use App\Models\ExamSession;
use App\Models\Grade;
use App\Models\ParticipantResult;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

class RemidiService
{
    /**
     * Reset PG + Esai state for attempt 2, keeping wawancara score.
     */
    public function startRemidi(ExamSession $session, Student $student): ParticipantResult
    {
        return DB::transaction(function () use ($session, $student) {
            $result = ParticipantResult::where('exam_session_id', $session->id)
                ->where('student_id', $student->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_if($result->attempt >= 2, 422, 'Kesempatan remidi sudah digunakan.');
            abort_if($result->keputusan !== 'TIDAK_LULUS', 422, 'Hanya peserta tidak lulus yang dapat remidi.');

            // Hapus jawaban PG
            if ($session->exam_id_pg) {
                Answer::where('exam_session_id', $session->id)
                    ->where('student_id', $student->id)
                    ->where('exam_id', $session->exam_id_pg)
                    ->delete();

                Grade::where('exam_session_id', $session->id)
                    ->where('student_id', $student->id)
                    ->where('exam_id', $session->exam_id_pg)
                    ->delete();
            }

            // Hapus jawaban esai
            if ($session->exam_id_esai) {
                AnswerEssay::where('exam_session_id', $session->id)
                    ->where('student_id', $student->id)
                    ->where('exam_id', $session->exam_id_esai)
                    ->delete();

                Grade::where('exam_session_id', $session->id)
                    ->where('student_id', $student->id)
                    ->where('exam_id', $session->exam_id_esai)
                    ->delete();
            }

            // Reset result (wawancara dipertahankan). Nomor SK & SP sengaja TIDAK
            // dikosongkan: dipakai lagi saat finalisasi ulang supaya tidak hangus.
            // Verifikasi Pengambil Keputusan & status kirim SP direset — hasil
            // remidi harus diverifikasi ulang dan SP-nya dikirim ulang.
            $result->update([
                'nilai_pg'    => null,
                'nilai_esai'  => null,
                'nilai_akhir' => null,
                'keputusan'   => null,
                'is_finalized'=> false,
                'finalized_at'=> null,
                'manager_verified_at' => null,
                'manager_verified_by' => null,
                'sertifikat_number' => null,
                'distributed_at'    => null,
                'sp_distributed_at' => null,
                'attempt'     => 2,
            ]);

            return $result;
        });
    }
}
