<?php

namespace App\Http\Controllers\Asesor;

use App\Http\Controllers\Controller;
use App\Models\AsesorAssignment;
use App\Models\ExamSession;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $asesor = auth()->user();

        $session_ids = AsesorAssignment::where('user_id', $asesor->id)
            ->pluck('exam_session_id')
            ->unique();

        $all = ExamSession::with('examPg.classroom', 'examEsai.classroom')
            ->whereIn('id', $session_ids)
            ->orderBy('end_time', 'desc')
            ->get()
            ->map(function ($session) use ($asesor) {
                $session->student_count = AsesorAssignment::where('user_id', $asesor->id)
                    ->where('exam_session_id', $session->id)
                    ->count();
                return $session;
            });

        return inertia('Asesor/Dashboard', [
            'active_sessions'    => $all->filter(fn($s) => $s->end_time && now()->lt($s->end_time))->values(),
            'completed_sessions' => $all->filter(fn($s) => !$s->end_time || now()->gte($s->end_time))->values(),
            'asesor'             => $asesor,
        ]);
    }
}
