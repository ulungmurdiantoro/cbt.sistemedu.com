<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('answer_essays', function (Blueprint $table) {
            $table->decimal('score', 5, 2)->nullable()->after('is_correct');
            $table->foreignId('assessed_by')->nullable()->constrained('users')->nullOnDelete()->after('score');
            $table->timestamp('assessed_at')->nullable()->after('assessed_by');
        });
    }

    public function down(): void
    {
        Schema::table('answer_essays', function (Blueprint $table) {
            $table->dropForeign(['assessed_by']);
            $table->dropColumn(['score', 'assessed_by', 'assessed_at']);
        });
    }
};
