<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Role baru Manager Sertifikasi (certification_manager) — verifikasi tahap kedua setelah
    // Pengambil Keputusan, lalu finalisasi. 'manager_sertifikasi' tetap = Pengambil Keputusan
    // (nama teknis lama).
    public function up(): void
    {
        Schema::table('user_roles', function (Blueprint $table) {
            $table->enum('role', ['admin', 'asesor', 'manager_sertifikasi', 'certification_manager'])->change();
        });
    }

    public function down(): void
    {
        DB::table('user_roles')->where('role', 'certification_manager')->delete();

        Schema::table('user_roles', function (Blueprint $table) {
            $table->enum('role', ['admin', 'asesor', 'manager_sertifikasi'])->change();
        });
    }
};
