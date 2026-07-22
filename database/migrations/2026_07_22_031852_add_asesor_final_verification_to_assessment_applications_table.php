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
        Schema::table('assessment_applications', function (Blueprint $table) {
            $table->foreignId('asesor_verified_by')->nullable()->after('admin_signature_name')->constrained('users')->nullOnDelete();
            $table->timestamp('asesor_verified_at')->nullable()->after('asesor_verified_by');
            $table->string('asesor_signature_path')->nullable()->after('asesor_verified_at');
            $table->string('asesor_signature_name')->nullable()->after('asesor_signature_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assessment_applications', function (Blueprint $table) {
            $table->dropForeign(['asesor_verified_by']);
            $table->dropColumn(['asesor_verified_by', 'asesor_verified_at', 'asesor_signature_path', 'asesor_signature_name']);
        });
    }
};
