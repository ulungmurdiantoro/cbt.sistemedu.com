<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;

class EnsureCertificationManager
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check() || !auth()->user()->hasRole(UserRole::CertificationManager)) {
            return redirect('/login');
        }

        return $next($request);
    }
}
