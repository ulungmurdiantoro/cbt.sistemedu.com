<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessment_applications', function (Blueprint $table) {
            $table->string('admin_signature_path', 500)->nullable()->after('approved_by');
            $table->string('admin_signature_name', 255)->nullable()->after('admin_signature_path');
        });
    }

    public function down(): void
    {
        Schema::table('assessment_applications', function (Blueprint $table) {
            $table->dropColumn(['admin_signature_path', 'admin_signature_name']);
        });
    }
};
