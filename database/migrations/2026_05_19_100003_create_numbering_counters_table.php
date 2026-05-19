<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('numbering_counters', function (Blueprint $table) {
            $table->id();
            $table->string('type');           // 'sk' | 'sertifikat'
            $table->string('scope')->nullable(); // null=global(sk), classroom_id string for sertifikat
            $table->smallInteger('year');
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();
            $table->unique(['type', 'scope', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('numbering_counters');
    }
};
