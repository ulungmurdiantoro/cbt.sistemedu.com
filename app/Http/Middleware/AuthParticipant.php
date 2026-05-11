<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AuthParticipant
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->guard('participant')->check()) {
            return redirect()->route('peserta.login');
        }

        return $next($request);
    }
}
