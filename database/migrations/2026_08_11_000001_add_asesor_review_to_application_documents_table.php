<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Kolom `status`/`reviewer_notes` yang lama tetap dipakai untuk pengecekan
    // kelengkapan dokumen oleh admin sebelum permohonan disetujui (approve).
    // Kolom baru ini khusus untuk verifikasi oleh asesor (atau admin atas nama
    // asesor) di tahap "Verifikasi Dokumen Peserta" — supaya asesor selalu
    // mulai dari status kosong, tidak otomatis ikut keputusan admin.
    public function up(): void
    {
        Schema::table('application_documents', function (Blueprint $table) {
            $table->enum('asesor_status', ['pending', 'verified', 'rejected'])->default('pending')->after('reviewer_notes');
            $table->string('asesor_reviewer_notes', 500)->nullable()->after('asesor_status');
        });
    }

    public function down(): void
    {
        Schema::table('application_documents', function (Blueprint $table) {
            $table->dropColumn(['asesor_status', 'asesor_reviewer_notes']);
        });
    }
};
