<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Panduan Penilaian Umum (per ujian esai) + matriks rubrik per soal dari FR.IA.06 — hanya untuk halaman Nilai Esai asesor.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->longText('esai_panduan')->nullable();
        });
        Schema::table('essays', function (Blueprint $table) {
            $table->json('rubrik')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('essays', function (Blueprint $table) {
            $table->dropColumn('rubrik');
        });
        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn('esai_panduan');
        });
    }
};
