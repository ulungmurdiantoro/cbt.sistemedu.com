<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // CV Asesor — diisi & diperbarui sendiri oleh asesor lewat portalnya
    // (/asesor/cv), diterbitkan sebagai PDF resmi lewat DocumentGeneratorService.
    // Kolom JSON menampung baris tabel berulang (pendidikan, pelatihan, dst)
    // apa adanya sebagai array — tidak pernah di-query/filter di level DB,
    // hanya dibaca utuh untuk form & PDF (pola sama seperti snapshot_pribadi/
    // snapshot_pekerjaan di assessment_applications).
    public function up(): void
    {
        Schema::create('asesor_cvs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();

            $table->string('tempat_tanggal_lahir')->nullable();
            $table->string('jenis_kelamin')->nullable();
            $table->text('alamat_rumah')->nullable();
            $table->string('nama_institusi')->nullable();
            $table->text('alamat_institusi')->nullable();
            $table->string('no_handphone')->nullable();
            $table->string('photo_path', 500)->nullable();

            $table->json('pendidikan_formal')->nullable();
            $table->json('pelatihan')->nullable();
            $table->json('pengalaman_kerja')->nullable();
            $table->json('keahlian')->nullable();
            $table->json('pengalaman_profesional')->nullable();
            $table->json('sertifikasi_kompetensi')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asesor_cvs');
    }
};
