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
        Schema::table('classrooms', function (Blueprint $table) {
            $table->string('kode')->nullable()->after('kode_skema');
            $table->string('nomor_skema')->nullable()->after('kode');
            $table->string('gelar')->nullable()->after('nomor_skema');
        });
    }

    public function down(): void
    {
        Schema::table('classrooms', function (Blueprint $table) {
            $table->dropColumn(['kode', 'nomor_skema', 'gelar']);
        });
    }
};
