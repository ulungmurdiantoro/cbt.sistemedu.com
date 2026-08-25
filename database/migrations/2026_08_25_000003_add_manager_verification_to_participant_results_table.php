<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('participant_results', function (Blueprint $table) {
            $table->timestamp('manager_verified_at')->nullable()->after('keputusan');
            $table->foreignId('manager_verified_by')->nullable()->after('manager_verified_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('participant_results', function (Blueprint $table) {
            $table->dropConstrainedForeignId('manager_verified_by');
            $table->dropColumn('manager_verified_at');
        });
    }
};
