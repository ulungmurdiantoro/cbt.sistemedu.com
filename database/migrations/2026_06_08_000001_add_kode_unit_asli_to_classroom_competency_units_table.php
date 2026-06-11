<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classroom_competency_units', function (Blueprint $table) {
            // Kode unit asli (referensi SKKNI), mis. M.71KKK01.002.1 untuk skema K3L.
            // Berbeda dari kode_unit yang merupakan kode skema internal (mis. SP.K3L.001.01).
            $table->string('kode_unit_asli', 100)->nullable()->after('kode_unit');
        });
    }

    public function down(): void
    {
        Schema::table('classroom_competency_units', function (Blueprint $table) {
            $table->dropColumn('kode_unit_asli');
        });
    }
};
