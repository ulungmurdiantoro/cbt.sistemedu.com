<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->timestamp('remidi_start_at')->nullable()->after('end_time');
            $table->timestamp('remidi_end_at')->nullable()->after('remidi_start_at');
        });
    }

    public function down(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->dropColumn(['remidi_start_at', 'remidi_end_at']);
        });
    }
};
