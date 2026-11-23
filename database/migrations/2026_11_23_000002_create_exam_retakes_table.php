<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Catatan kompensasi "kerjakan ulang" satu ujian (PG/Esai) untuk satu peserta — php artisan ujian:beri-ulang.
// Menyimpan waktu mulai/selesai sebelumnya & alasan supaya kompensasi bisa ditelusuri.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_retakes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('exam_session_id')->index();
            $table->unsignedBigInteger('student_id')->index();
            $table->unsignedBigInteger('exam_id');
            $table->text('alasan');
            $table->unsignedInteger('durasi_menit');
            $table->dateTime('start_time_sebelumnya')->nullable();
            $table->dateTime('end_time_sebelumnya')->nullable();
            $table->unsignedInteger('jawaban_tersimpan')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_retakes');
    }
};
