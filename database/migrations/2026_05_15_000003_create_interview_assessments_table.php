<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interview_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_session_id')->constrained('exam_sessions')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('asesor_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('gaya_wawancara', 5, 2)->nullable();
            $table->decimal('penguasaan_materi', 5, 2)->nullable();
            $table->decimal('kemampuan_hadapi_pertanyaan', 5, 2)->nullable();
            $table->decimal('hasil_worksheet', 5, 2)->nullable();
            $table->decimal('total_nilai', 6, 2)->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->unique(['exam_session_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interview_assessments');
    }
};
