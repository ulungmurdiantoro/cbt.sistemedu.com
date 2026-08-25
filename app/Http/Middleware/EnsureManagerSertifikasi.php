<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;

class EnsureManagerSertifikasi
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check() || auth()->user()->role !== UserRole::ManagerSertifikasi) {
            return redirect('/login');
        }

        return $next($request);
    }
}
