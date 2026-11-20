<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Penilaian Ujian Praktik per aspek rubrik (mis. Corporate Legal Studi Kasus 2: 8 aspek berbobot, total 100%).
// exams.praktik_aspek = [{no, nama, bobot}]; praktik_scores.aspek = {no: nilai 0–100}. Nilai bagian
// (praktik_scores.score) dihitung server = Σ nilai × bobot / Σ bobot, hanya bila semua aspek terisi.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->json('praktik_aspek')->nullable()->after('praktik_rubrik');
        });

        Schema::table('praktik_scores', function (Blueprint $table) {
            $table->json('aspek')->nullable()->after('score');
        });
    }

    public function down(): void
    {
        Schema::table('praktik_scores', function (Blueprint $table) {
            $table->dropColumn('aspek');
        });

        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn('praktik_aspek');
        });
    }
};
