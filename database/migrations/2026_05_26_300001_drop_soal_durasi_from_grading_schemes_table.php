<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grading_schemes', function (Blueprint $table) {
            $table->dropColumn([
                'jumlah_soal_pg',
                'durasi_pg_menit',
                'jumlah_soal_esai',
                'durasi_esai_menit',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('grading_schemes', function (Blueprint $table) {
            $table->unsignedSmallInteger('jumlah_soal_pg')->default(100);
            $table->unsignedSmallInteger('durasi_pg_menit')->default(120);
            $table->unsignedSmallInteger('jumlah_soal_esai')->default(10);
            $table->unsignedSmallInteger('durasi_esai_menit')->default(90);
        });
    }
};
