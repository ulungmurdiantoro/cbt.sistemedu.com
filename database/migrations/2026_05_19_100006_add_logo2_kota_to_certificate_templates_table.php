<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificate_templates', function (Blueprint $table) {
            $table->string('kop_logo2_path')->nullable()->after('kop_path');
            $table->string('kota')->nullable()->default('Semarang')->after('jabatan_penandatangan');
        });
    }

    public function down(): void
    {
        Schema::table('certificate_templates', function (Blueprint $table) {
            $table->dropColumn(['kop_logo2_path', 'kota']);
        });
    }
};
