<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Catatan asesor per aspek Ujian Praktik ({no aspek: teks}) — diisi di halaman nilai praktik per peserta.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('praktik_scores', function (Blueprint $table) {
            $table->json('catatan')->nullable()->after('aspek');
        });
    }

    public function down(): void
    {
        Schema::table('praktik_scores', function (Blueprint $table) {
            $table->dropColumn('catatan');
        });
    }
};
