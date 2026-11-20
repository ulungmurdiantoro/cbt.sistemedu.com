<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Nilai Ujian Praktik sebagai komponen nilai sendiri (mis. Lifting: PG 20% + Calculation 50% + Gambar 30%).
// Bobot per bagian di Komposisi Nilai skema (grading_schemes.bobot_praktik_1/2, persen dari nilai akhir; 0 = praktik
// tetap dinilai lewat kriteria "Hasil Pengerjaan Worksheet" di wawancara). Asesor mengisi nilai per bagian di
// praktik_scores; nilai gabungan (rata-rata berbobot) disimpan di participant_results.nilai_praktik.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grading_schemes', function (Blueprint $table) {
            $table->decimal('bobot_praktik_1', 5, 2)->default(0)->after('bobot_wawancara');
            $table->decimal('bobot_praktik_2', 5, 2)->default(0)->after('bobot_praktik_1');
        });

        // Kolom biasa (tanpa foreign key) — aman walau participant_results dirujuk view v_sertifikasi_kelulusan.
        Schema::table('participant_results', function (Blueprint $table) {
            $table->decimal('nilai_praktik', 5, 2)->nullable()->after('nilai_wawancara');
        });

        Schema::create('praktik_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_session_id')->constrained('exam_sessions')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->unsignedTinyInteger('bagian')->default(1);
            $table->decimal('score', 5, 2)->nullable();
            $table->foreignId('asesor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['exam_session_id', 'student_id', 'bagian']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('praktik_scores');

        Schema::table('participant_results', function (Blueprint $table) {
            $table->dropColumn('nilai_praktik');
        });

        Schema::table('grading_schemes', function (Blueprint $table) {
            $table->dropColumn(['bobot_praktik_1', 'bobot_praktik_2']);
        });
    }
};
