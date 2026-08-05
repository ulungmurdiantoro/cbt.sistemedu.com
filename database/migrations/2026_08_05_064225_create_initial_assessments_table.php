<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('initial_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_application_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('classroom_id')->constrained();
            $table->json('answers');
            $table->unsignedInteger('total_score');
            $table->unsignedInteger('threshold');
            $table->boolean('is_eligible');
            $table->foreignId('assessed_by')->constrained('users');
            $table->timestamp('assessed_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('initial_assessments');
    }
};
