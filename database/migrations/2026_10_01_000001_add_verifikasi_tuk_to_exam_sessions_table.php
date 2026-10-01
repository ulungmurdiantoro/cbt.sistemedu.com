<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Saklar per sesi: tampilkan checklist FR.TUK.06 (Verifikasi TUK Online) untuk
    // sesi ini. Sesi lama default mati; sesi baru dicentang dari form sesi.
    public function up(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->boolean('verifikasi_tuk')->default(false)->after('has_wawancara');
        });
    }

    public function down(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->dropColumn('verifikasi_tuk');
        });
    }
};
