<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grading_schemes', function (Blueprint $table) {
            // Struktur dua lapis
            $table->decimal('bobot_ujian_tulis', 5, 2)->default(70)->after('bobot_wawancara'); // tulis (PG+Esai) total
            $table->decimal('proporsi_pg', 5, 2)->default(65)->after('bobot_ujian_tulis');    // % PG dalam ujian tulis
            // Info tabel a
            $table->unsignedSmallInteger('jumlah_soal_pg')->default(100)->after('proporsi_pg');
            $table->unsignedSmallInteger('durasi_pg_menit')->default(120)->after('jumlah_soal_pg');
            $table->unsignedSmallInteger('jumlah_soal_esai')->default(10)->after('durasi_pg_menit');
            $table->unsignedSmallInteger('durasi_esai_menit')->default(90)->after('jumlah_soal_esai');
        });
    }

    public function down(): void
    {
        Schema::table('grading_schemes', function (Blueprint $table) {
            $table->dropColumn([
                'bobot_ujian_tulis', 'proporsi_pg',
                'jumlah_soal_pg', 'durasi_pg_menit',
                'jumlah_soal_esai', 'durasi_esai_menit',
            ]);
        });
    }
};
