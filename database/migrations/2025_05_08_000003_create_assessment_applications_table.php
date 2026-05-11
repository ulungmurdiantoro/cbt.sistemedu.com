<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_applications', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('participant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exam_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('exam_group_id')->nullable()->constrained()->nullOnDelete();

            $table->string('konteks_asesmen')->default('Sertifikasi Person');
            $table->string('tempat_ujian')->default('Online (Zoom Meeting)');
            $table->string('kode_batch');
            $table->enum('tujuan_asesmen', ['Sertifikasi', 'Sertifikasi Ulang'])->default('Sertifikasi');

            // snapshot data saat submit untuk audit trail
            $table->json('snapshot_pribadi')->nullable();
            $table->json('snapshot_pekerjaan')->nullable();

            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected'])->default('draft');
            $table->text('admin_notes')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->unique(['participant_id', 'exam_session_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_applications');
    }
};
