<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grading_schemes', function (Blueprint $table) {
            // Faktor konversi skor mentah wawancara (sum 4 kriteria) ke total_nilai.
            // Default 0.075 = 30 poin maks (400 × 0.075 = 30).
            $table->decimal('faktor_wawancara', 6, 4)->default(0.0750)->after('bobot_wawancara');
        });
    }

    public function down(): void
    {
        Schema::table('grading_schemes', function (Blueprint $table) {
            $table->dropColumn('faktor_wawancara');
        });
    }
};
