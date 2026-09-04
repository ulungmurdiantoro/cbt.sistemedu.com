<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin              = 'admin';
    case Asesor             = 'asesor';
    case ManagerSertifikasi = 'manager_sertifikasi';

    public function label(): string
    {
        return match($this) {
            self::Admin              => 'Admin',
            self::Asesor             => 'Asesor',
            self::ManagerSertifikasi => 'Pengambil Keputusan',
        };
    }

    public function dashboardRoute(): string
    {
        return match($this) {
            self::Admin              => 'admin.dashboard',
            self::Asesor             => 'asesor.dashboard',
            self::ManagerSertifikasi => 'manager.dashboard',
        };
    }
}
