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
            $table->timestamp('pakta_signed_at')->nullable()->after('signature_path');
        });
    }

    public function down(): void
    {
        Schema::table('assessment_applications', function (Blueprint $table) {
            $table->dropColumn('pakta_signed_at');
        });
    }
};
