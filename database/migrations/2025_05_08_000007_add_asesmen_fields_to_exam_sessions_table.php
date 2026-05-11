<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->string('konteks_asesmen')->default('Sertifikasi Person')->after('end_time');
            $table->string('tempat_ujian')->default('Online (Zoom Meeting)')->after('konteks_asesmen');
            $table->string('kode_batch')->nullable()->after('tempat_ujian');
        });
    }

    public function down(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->dropColumn(['konteks_asesmen', 'tempat_ujian', 'kode_batch']);
        });
    }
};
