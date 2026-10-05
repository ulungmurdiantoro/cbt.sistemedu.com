<?php

namespace App\Http\Responses;

use App\Enums\UserRole;
use App\Models\User;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        return redirect(self::dashboardUrl(auth()->user()) ?? route('admin.dashboard'));
    }

    /**
     * Dashboard staf sesuai role, atau null bila user tidak punya role staf.
     * User bisa punya lebih dari satu role — prioritas: admin > manager sertifikasi > asesor.
     * Dipakai juga saat staf yang masih login membuka "/" atau "/login".
     */
    public static function dashboardUrl(?User $user): ?string
    {
        return match (true) {
            (bool) $user?->hasRole(UserRole::Admin)              => route('admin.dashboard'),
            (bool) $user?->hasRole(UserRole::ManagerSertifikasi) => route('manager.dashboard'),
            (bool) $user?->hasRole(UserRole::Asesor)             => route('asesor.dashboard'),
            default                                              => null,
        };
    }
}
