<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Rekomendasi Kompeten/Belum Kompeten — diisi asesor sendiri (penilaian
    // profesional) saat Verifikasi Akhir, jadi bagian dari "Laporan Asesmen"
    // yang direview Manager Sertifikasi sebelum finalisasi nilai.
    public function up(): void
    {
        Schema::table('assessment_applications', function (Blueprint $table) {
            $table->enum('asesor_rekomendasi', ['K', 'BK'])->nullable()->after('asesor_verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('assessment_applications', function (Blueprint $table) {
            $table->dropColumn('asesor_rekomendasi');
        });
    }
};
