<?php

namespace App\Http\Middleware;

use App\Models\AssessmentApplication;
use App\Models\ExamSession;
use Closure;
use Illuminate\Http\Request;

/**
 * Portal Pengambil Keputusan: halaman/berkas per sesi hanya untuk PK yang ditunjuk admin di
 * sesi itu (exam_sessions.pengambil_keputusan_id). Sesinya dibaca dari parameter route
 * {examSession} / {examSessionId} / {application} — controller-nya dipakai bersama portal
 * Manager Sertifikasi (semua sesi), jadi pembatasan ini sengaja di middleware, bukan di controller.
 */
class EnsureAppointedPengambilKeputusan
{
    public function handle(Request $request, Closure $next)
    {
        $route = $request->route();

        if ($route->hasParameter('examSession')) {
            $sessionId = $this->id($route->parameter('examSession'));
        } elseif ($route->hasParameter('examSessionId')) {
            $sessionId = (int) $route->parameter('examSessionId');
        } elseif ($route->hasParameter('application')) {
            $application = $route->parameter('application');
            $sessionId   = $application instanceof AssessmentApplication
                ? $application->exam_session_id
                : AssessmentApplication::whereKey($application)->value('exam_session_id');
        } else {
            return $next($request);
        }

        abort_unless(
            $sessionId && ExamSession::whereKey($sessionId)->where('pengambil_keputusan_id', auth()->id())->exists(),
            403,
            'Anda tidak ditunjuk sebagai Pengambil Keputusan untuk sesi ini.'
        );

        return $next($request);
    }

    private function id(mixed $value): int
    {
        return $value instanceof ExamSession ? $value->id : (int) $value;
    }
}
