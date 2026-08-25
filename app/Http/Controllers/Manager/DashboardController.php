<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\ExamSession;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $sessions = ExamSession::with('examPg.classroom', 'examEsai.classroom')
            ->withCount('participantResults')
            ->orderByDesc('end_time')
            ->get();

        return inertia('Manager/Dashboard', [
            'active_sessions'    => $sessions->filter(fn($s) => $s->end_time && now()->lt($s->end_time))->values(),
            'completed_sessions' => $sessions->filter(fn($s) => !$s->end_time || now()->gte($s->end_time))->values(),
        ]);
    }
}
