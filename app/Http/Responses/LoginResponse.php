<?php

namespace App\Http\Responses;

use App\Enums\UserRole;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $user = auth()->user();

        if ($user?->role === UserRole::Asesor) {
            return redirect()->route('asesor.dashboard');
        }

        if ($user?->role === UserRole::ManagerSertifikasi) {
            return redirect()->route('manager.dashboard');
        }

        return redirect()->route('admin.dashboard');
    }
}
