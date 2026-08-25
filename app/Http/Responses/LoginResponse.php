<?php

namespace App\Http\Responses;

use App\Enums\UserRole;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $user = auth()->user();

        // User bisa punya lebih dari satu role — prioritas: admin > manager sertifikasi > asesor.
        if ($user?->hasRole(UserRole::Admin)) {
            return redirect()->route('admin.dashboard');
        }

        if ($user?->hasRole(UserRole::ManagerSertifikasi)) {
            return redirect()->route('manager.dashboard');
        }

        if ($user?->hasRole(UserRole::Asesor)) {
            return redirect()->route('asesor.dashboard');
        }

        return redirect()->route('admin.dashboard');
    }
}
