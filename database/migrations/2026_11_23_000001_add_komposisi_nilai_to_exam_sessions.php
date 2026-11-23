<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Komposisi Nilai beku per sesi: skema yang sama bisa punya sesi lama (ujian & komposisi lama) dan sesi baru (paket V2).
// Null = mengikuti Komposisi Nilai skema (grading_schemes); terisi = salinan komposisi yang berlaku untuk sesi itu
// (GradingScheme::forSession). Diisi otomatis oleh paket-soal:pasang --perbarui untuk sesi lama sebelum komposisi skema diubah.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->json('komposisi_nilai')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->dropColumn('komposisi_nilai');
        });
    }
};
