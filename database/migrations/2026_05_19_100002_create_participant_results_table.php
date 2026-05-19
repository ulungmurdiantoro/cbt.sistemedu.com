<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('participant_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->decimal('nilai_pg', 5, 2)->nullable();
            $table->decimal('nilai_esai', 5, 2)->nullable();
            $table->decimal('nilai_wawancara', 5, 2)->nullable();
            $table->decimal('nilai_akhir', 5, 2)->nullable();
            $table->enum('keputusan', ['LULUS', 'TIDAK_LULUS'])->nullable();
            $table->boolean('is_finalized')->default(false);
            $table->timestamp('finalized_at')->nullable();
            $table->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('sk_number')->nullable()->unique();
            $table->string('sertifikat_number')->nullable()->unique();
            $table->timestamp('distributed_at')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->unsignedTinyInteger('attempt')->default(1);
            $table->timestamps();
            $table->unique(['exam_session_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('participant_results');
    }
};
