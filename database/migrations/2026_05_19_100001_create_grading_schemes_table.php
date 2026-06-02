<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grading_schemes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
            $table->decimal('bobot_pg', 5, 2)->default(40);
            $table->decimal('bobot_esai', 5, 2)->default(35);
            $table->decimal('bobot_wawancara', 5, 2)->default(25);
            $table->decimal('nilai_kelulusan', 5, 2)->default(60);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grading_schemes');
    }
};
