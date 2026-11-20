<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Pilihan per butir untuk aspek yang dinilai per butir (mis. Lifting: Ada & Benar 10 / Ada tapi Salah 5 / Tidak Ada 0):
// praktik_scores.rincian = {no aspek: {no butir: nilai}}; nilai aspek = jumlah butir (lihat NilaiPraktik::itemScore).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('praktik_scores', function (Blueprint $table) {
            $table->json('rincian')->nullable()->after('aspek');
        });
    }

    public function down(): void
    {
        Schema::table('praktik_scores', function (Blueprint $table) {
            $table->dropColumn('rincian');
        });
    }
};
