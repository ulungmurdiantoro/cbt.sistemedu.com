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

        // Sesi ujian yang ada penugasan untuk asesor ini
        $session_ids = AsesorAssignment::where('user_id', $asesor->id)
            ->pluck('exam_session_id')
            ->unique();

        $exam_sessions = ExamSession::with('exam.classroom')
            ->whereIn('id', $session_ids)
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($session) use ($asesor) {
                $session->student_count = AsesorAssignment::where('user_id', $asesor->id)
                    ->where('exam_session_id', $session->id)
                    ->count();
                return $session;
            });

        return inertia('Asesor/Dashboard', [
            'exam_sessions' => $exam_sessions,
            'asesor'        => $asesor,
        ]);
    }
}
