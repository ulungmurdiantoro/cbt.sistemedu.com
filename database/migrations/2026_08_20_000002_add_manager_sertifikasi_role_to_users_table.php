<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Khusus MySQL. Di SQLite (test) kolom ini toh dihapus oleh migrasi
        // 2026_08_25_000001 (role pindah ke tabel user_roles).
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // MySQL tidak punya ALTER ENUM ADD VALUE langsung — modify kolom penuh.
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'asesor', 'manager_sertifikasi') DEFAULT 'admin'");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("UPDATE users SET role = 'admin' WHERE role = 'manager_sertifikasi'");
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'asesor') DEFAULT 'admin'");
    }
};
