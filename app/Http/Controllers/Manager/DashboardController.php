<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\ExamSession;

class DashboardController extends Controller
{
    public function __invoke()
    {
        // Hanya sesi yang ditunjuk admin untuk PK ini (exam_sessions.pengambil_keputusan_id).
        $sessions = ExamSession::where('pengambil_keputusan_id', auth()->id())
            ->with('examPg.classroom', 'examEsai.classroom')
            // Jumlah peserta = peserta terdaftar di sesi (akun aktif), sama dengan daftar
            // di halaman Tinjau Sertifikasi — bukan jumlah baris participant_results.
            ->withActiveStudentsCount()
            // Progres peninjauan (centang Verifikasi) — dipakai untuk badge
            // Belum/Sebagian/Sudah Ditinjau di dashboard.
            ->withCount(['participantResults as reviewed_count' => fn ($q) => $q->enrolled()->whereNotNull('manager_verified_at')])
            ->orderByDesc('end_time')
            ->get();

        return inertia('Manager/Dashboard', [
            'active_sessions'    => $sessions->filter(fn($s) => $s->end_time && now()->lt($s->end_time))->values(),
            'completed_sessions' => $sessions->filter(fn($s) => !$s->end_time || now()->gte($s->end_time))->values(),
        ]);
    }
}
