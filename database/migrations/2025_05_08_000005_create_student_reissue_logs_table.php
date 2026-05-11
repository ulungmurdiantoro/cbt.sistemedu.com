<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_reissue_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('old_student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->foreignId('new_student_id')->constrained('students')->cascadeOnDelete();
            $table->text('reason')->nullable();
            $table->foreignId('reissued_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_reissue_logs');
    }
};
