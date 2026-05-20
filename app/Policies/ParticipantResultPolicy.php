<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\ParticipantResult;
use App\Models\User;

/**
 * Policy untuk ParticipantResult.
 *
 * Guard 'web' dipakai Admin & Asesor.
 * Download SK/Sertifikat untuk peserta dijaga di Peserta\ResultController
 * dengan whereHas ownership check (participant guard berbeda).
 */
class ParticipantResultPolicy
{
    /** Admin boleh lihat semua */
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    /** Admin boleh lihat satu result */
    public function view(User $user, ParticipantResult $result): bool
    {
        return $user->role === UserRole::Admin;
    }

    /** Hanya admin yang boleh finalisasi */
    public function finalize(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    /** Hanya admin yang boleh distribusi */
    public function distribute(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    /** Admin download SK/sertifikat semua peserta */
    public function download(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    /**
     * Asesor hanya boleh download result jika ditugaskan ke student tsb.
     * Digunakan via manual check di controller — policy ini sebagai referensi logika.
     */
    public function downloadAsAsesor(User $user, ParticipantResult $result): bool
    {
        if ($user->role !== UserRole::Asesor) return false;

        return $user->assignments()
            ->where('exam_session_id', $result->exam_session_id)
            ->where('student_id', $result->student_id)
            ->exists();
    }
}
