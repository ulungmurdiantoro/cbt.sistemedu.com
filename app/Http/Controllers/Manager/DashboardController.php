<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\ExamSession;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $sessions = ExamSession::with('examPg.classroom', 'examEsai.classroom')
            // Hanya hitung hasil milik akun peserta yang masih aktif — biar angka
            // di sini sama dengan daftar di halaman Tinjau Sertifikasi (akun lama
            // hasil re-issue / merge duplikat tidak ikut).
            ->withCount(['participantResults as participant_results_count' => function ($q) {
                $q->whereHas('student', fn ($s) => $s->where('is_active', true));
            }])
            ->orderByDesc('end_time')
            ->get();

        return inertia('Manager/Dashboard', [
            'active_sessions'    => $sessions->filter(fn($s) => $s->end_time && now()->lt($s->end_time))->values(),
            'completed_sessions' => $sessions->filter(fn($s) => !$s->end_time || now()->gte($s->end_time))->values(),
        ]);
    }
}
