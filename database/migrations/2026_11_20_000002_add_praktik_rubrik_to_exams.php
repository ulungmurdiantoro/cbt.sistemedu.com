<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Rubrik penilaian Ujian Praktik (dari FR.IA.04) — panduan asesor di halaman Nilai Praktik, tidak dikirim ke peserta.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->longText('praktik_rubrik')->nullable()->after('praktik_panduan');
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn('praktik_rubrik');
        });
    }
};
