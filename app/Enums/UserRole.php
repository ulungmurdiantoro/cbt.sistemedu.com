<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin  = 'admin';
    case Asesor = 'asesor';

    public function label(): string
    {
        return match($this) {
            self::Admin  => 'Admin',
            self::Asesor => 'Asesor',
        };
    }

    public function dashboardRoute(): string
    {
        return match($this) {
            self::Admin  => 'admin.dashboard',
            self::Asesor => 'asesor.dashboard',
        };
    }
}
