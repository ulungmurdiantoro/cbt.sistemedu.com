<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->foreignId('participant_id')->nullable()->after('id')
                ->constrained()->nullOnDelete();
            $table->boolean('is_active')->default(true)->after('gender');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['participant_id']);
            $table->dropColumn(['participant_id', 'is_active']);
        });
    }
};
