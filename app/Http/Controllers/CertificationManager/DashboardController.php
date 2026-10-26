<?php

namespace App\Http\Controllers\CertificationManager;

use App\Http\Controllers\Controller;
use App\Models\ExamSession;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $sessions = ExamSession::with('examPg.classroom', 'examEsai.classroom', 'pengambilKeputusan:id,name')
            // Jumlah peserta = peserta terdaftar di sesi (akun aktif), sama dengan daftar
            // di halaman Tinjau Sertifikasi — bukan jumlah baris participant_results.
            ->withActiveStudentsCount()
            ->withCount([
                // Tahap 1: dicentang Pengambil Keputusan.
                'participantResults as pk_verified_count' => fn ($q) => $q->enrolled()->whereNotNull('manager_verified_at'),
                // Tahap 2: dicentang Manager Sertifikasi. Peserta yang sudah Final sebelum ada
                // tahap ini ikut dihitung selesai.
                'participantResults as reviewed_count' => fn ($q) => $q->enrolled()
                    ->where(fn ($q) => $q->whereNotNull('cert_manager_verified_at')->orWhere('is_finalized', true)),
            ])
            ->orderByDesc('end_time')
            ->get();

        return inertia('CertificationManager/Dashboard', [
            'active_sessions'    => $sessions->filter(fn ($s) => $s->end_time && now()->lt($s->end_time))->values(),
            'completed_sessions' => $sessions->filter(fn ($s) => !$s->end_time || now()->gte($s->end_time))->values(),
        ]);
    }
}
