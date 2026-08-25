<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL tidak punya ALTER ENUM ADD VALUE langsung — modify kolom penuh.
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'asesor', 'manager_sertifikasi') DEFAULT 'admin'");
    }

    public function down(): void
    {
        DB::statement("UPDATE users SET role = 'admin' WHERE role = 'manager_sertifikasi'");
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'asesor') DEFAULT 'admin'");
    }
};
