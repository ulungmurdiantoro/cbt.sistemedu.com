<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('participant_results', function (Blueprint $table) {
            $table->timestamp('sp_distributed_at')->nullable()->after('distributed_at');
        });
    }

    public function down(): void
    {
        Schema::table('participant_results', function (Blueprint $table) {
            $table->dropColumn('sp_distributed_at');
        });
    }
};
