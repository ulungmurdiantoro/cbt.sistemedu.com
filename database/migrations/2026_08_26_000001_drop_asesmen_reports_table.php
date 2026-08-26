<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// FR.AK.05 tidak lagi butuh form/"laporan" tersendiri — PDF dibuat langsung dari
// rekomendasi K/BK (di assessment_applications) dan data sesi ujian. Kolom narasi
// (aspek negatif/positif, dll) dihapus dari alur; tabel ini jadi tidak terpakai.
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('asesmen_reports');
    }

    public function down(): void
    {
        Schema::create('asesmen_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('tanggal_asesmen')->nullable();
            $table->text('aspek_negatif_positif')->nullable();
            $table->text('pencatatan_penolakan')->nullable();
            $table->text('saran_perbaikan')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->unique(['exam_session_id', 'user_id']);
        });
    }
};
