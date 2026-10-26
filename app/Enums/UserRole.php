<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin                = 'admin';
    case Asesor               = 'asesor';
    // Nama teknis lama: 'manager_sertifikasi' = Pengambil Keputusan (portal /manager).
    case ManagerSertifikasi   = 'manager_sertifikasi';
    // Manajer Sertifikasi yang sebenarnya (portal /manager-sertifikasi): verifikasi tahap
    // kedua setelah Pengambil Keputusan, lalu finalisasi.
    case CertificationManager = 'certification_manager';

    public function label(): string
    {
        return match($this) {
            self::Admin                => 'Admin',
            self::Asesor               => 'Asesor',
            self::ManagerSertifikasi   => 'Pengambil Keputusan',
            self::CertificationManager => 'Manajer Sertifikasi',
        };
    }

    public function dashboardRoute(): string
    {
        return match($this) {
            self::Admin                => 'admin.dashboard',
            self::Asesor               => 'asesor.dashboard',
            self::ManagerSertifikasi   => 'manager.dashboard',
            self::CertificationManager => 'cert_manager.dashboard',
        };
    }
}
