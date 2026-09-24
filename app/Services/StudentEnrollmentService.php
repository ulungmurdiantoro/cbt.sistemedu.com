<?php

namespace App\Services;

use App\Models\Answer;
use App\Models\AnswerEssay;
use App\Models\AsesorAssignment;
use App\Models\AssessmentApplication;
use App\Models\ExamGroup;
use App\Models\ExamSession;
use App\Models\Grade;
use App\Models\Student;
use App\Models\StudentReissueLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Akun ujian (Student) & enrollment (ExamGroup) untuk permohonan sertifikasi:
 * dipakai saat permohonan disetujui, akun di-reissue, atau batch dipindah.
 */
class StudentEnrollmentService
{
    /**
     * Student aktif untuk participant + skema yang sama dipakai ulang
     * (skema sama, sesi berbeda); kalau belum ada, buat baru.
     */
    public function findOrCreateStudent(AssessmentApplication $application): Student
    {
        return Student::where('participant_id', $application->participant_id)
            ->where('classroom_id', $application->classroom_id)
            ->where('is_active', true)
            ->first()
            ?? $this->createStudent($application);
    }

    public function createStudent(AssessmentApplication $application): Student
    {
        $participant = $application->participant;

        return Student::create([
            'participant_id' => $participant->id,
            'classroom_id'   => $application->classroom_id,
            'no_participant' => $this->generateNoParticipant($application),
            'name'           => $participant->name,
            'position'       => $participant->jabatan ?? '-',
            'institution'    => $participant->institusi ?? '-',
            'gender'         => $participant->jenis_kelamin ?? 'L',
            'is_active'      => true,
        ]);
    }

    /**
     * Buat exam_group untuk semua ujian di sesi (PG dan/atau Esai).
     * Mengembalikan exam_group pertama (disimpan di assessment_applications.exam_group_id).
     */
    public function enroll(int $studentId, ExamSession $session): ?ExamGroup
    {
        $first = null;

        foreach (array_filter([$session->exam_id_pg, $session->exam_id_esai]) as $examId) {
            $eg = ExamGroup::create([
                'exam_groups_code' => 'EG-' . strtoupper(Str::random(8)),
                'exam_id'          => $examId,
                'exam_session_id'  => $session->id,
                'student_id'       => $studentId,
            ]);
            $first ??= $eg;
        }

        return $first;
    }

    /** Nonaktifkan akun lama, buat akun + enrollment baru, pindahkan penugasan asesor. */
    public function reissue(AssessmentApplication $application, ?string $reason, int $reissuedBy): Student
    {
        return DB::transaction(function () use ($application, $reason, $reissuedBy) {
            $oldStudent = $application->student;

            // nonaktifkan student lama
            if ($oldStudent) {
                $oldStudent->update(['is_active' => false]);
            }

            $newStudent   = $this->createStudent($application);
            $newExamGroup = $this->enroll($newStudent->id, $application->examSession);

            // Pindahkan penugasan asesor dari student lama ke student baru — supaya
            // tidak nyangkut menunjuk ke akun yang sudah dinonaktifkan, dan asesor
            // tidak melihat 2 baris nama yang sama (lama + baru) saat menilai.
            if ($oldStudent) {
                $oldAssignments = AsesorAssignment::where('exam_session_id', $application->exam_session_id)
                    ->where('student_id', $oldStudent->id)
                    ->get();

                foreach ($oldAssignments as $assignment) {
                    $alreadyAssignedToNew = AsesorAssignment::where('user_id', $assignment->user_id)
                        ->where('exam_session_id', $application->exam_session_id)
                        ->where('student_id', $newStudent->id)
                        ->exists();

                    if ($alreadyAssignedToNew) {
                        // Asesor ini sudah punya penugasan ke student baru juga — cukup buang yang lama.
                        $assignment->delete();
                    } else {
                        $assignment->update(['student_id' => $newStudent->id]);
                    }
                }

                // Buang enrollment (ExamGroup) student lama di sesi ini — sudah
                // digantikan ExamGroup student baru. Kalau dibiarkan, student lama
                // tetap muncul di roster Tinjau Sertifikasi (yang ambil dari
                // ExamGroup). Jawaban ujian lama tidak tersentuh (terhubung lewat
                // student_id + exam_session_id, bukan exam_group_id).
                ExamGroup::where('exam_session_id', $application->exam_session_id)
                    ->where('student_id', $oldStudent->id)
                    ->delete();
            }

            StudentReissueLog::create([
                'assessment_application_id' => $application->id,
                'old_student_id'            => $oldStudent?->id,
                'new_student_id'            => $newStudent->id,
                'reason'                    => $reason,
                'reissued_by'               => $reissuedBy,
            ]);

            $application->update([
                'student_id'    => $newStudent->id,
                'exam_group_id' => $newExamGroup?->id,
            ]);

            return $newStudent;
        });
    }

    /**
     * Sudah ada nilai/jawaban tersimpan di sesi permohonan saat ini? Kalau ya,
     * batch tidak boleh dipindah otomatis supaya data hasil ujian tidak yatim.
     */
    public function hasExamActivity(AssessmentApplication $application): bool
    {
        if (! $application->student_id) {
            return false;
        }

        $oldSession = ExamSession::find($application->exam_session_id);
        $oldExamIds = array_filter([$oldSession?->exam_id_pg, $oldSession?->exam_id_esai]);

        if (! $oldExamIds) {
            return false;
        }

        foreach ([Grade::class, Answer::class, AnswerEssay::class] as $model) {
            $exists = $model::where('student_id', $application->student_id)
                ->where('exam_session_id', $application->exam_session_id)
                ->whereIn('exam_id', $oldExamIds)
                ->exists();

            if ($exists) {
                return true;
            }
        }

        return false;
    }

    /** Pindahkan permohonan (dan enrollment akun ujiannya, bila ada) ke sesi/batch lain. */
    public function moveToSession(AssessmentApplication $application, ExamSession $newSession): void
    {
        DB::transaction(function () use ($application, $newSession) {
            if ($application->student_id) {
                ExamGroup::where('exam_session_id', $application->exam_session_id)
                    ->where('student_id', $application->student_id)
                    ->delete();

                $application->exam_group_id = $this->enroll($application->student_id, $newSession)?->id;
            }

            $application->exam_session_id = $newSession->id;
            $application->konteks_asesmen = $newSession->konteks_asesmen;
            $application->tempat_ujian    = $newSession->tempat_ujian;
            $application->kode_batch      = $newSession->kode_batch ?? '-';
            $application->save();
        });
    }

    public function generateNoParticipant(AssessmentApplication $application): string
    {
        $kodeSkema = $application->classroom->kode_skema ?? '';
        $kode      = substr($kodeSkema, 7, 3) ?: 'SKM';
        $batch     = $application->kode_batch ?? '-';
        $year      = now()->year;
        $prefix    = $kode . '.' . $batch . '.' . $year . '.';

        // Ambil nomor urut tertinggi yang SUDAH benar-benar dipakai untuk kombinasi
        // kode+batch+tahun ini, baru +1 — jangan hitung jumlah permohonan (bisa bentrok
        // kalau ada permohonan yang batch-nya berubah, ditolak lalu direset, dst).
        $lastNumber = Student::where('no_participant', 'like', $prefix . '%')
            ->get(['no_participant'])
            ->map(fn ($s) => (int) substr($s->no_participant, strlen($prefix)))
            ->max() ?? 0;

        $next = $lastNumber + 1;

        // Pengaman tambahan kalau masih bentrok (mis. ada nomor yang diinput manual).
        while (Student::where('no_participant', $prefix . str_pad($next, 5, '0', STR_PAD_LEFT))->exists()) {
            $next++;
        }

        return $prefix . str_pad($next, 5, '0', STR_PAD_LEFT);
    }
}
