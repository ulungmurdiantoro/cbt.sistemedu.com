<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // FR.TUK.06 — Checklist Verifikasi TUK Online, satu per peserta per sesi,
    // diisi admin sebagai Pengawas Ujian. Hanya pencatatan: tidak mengunci ujian.
    public function up(): void
    {
        Schema::create('tuk_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();

            // A. Identitas pelaksanaan (yang tidak bisa diambil dari data sesi/peserta)
            $table->date('tanggal_asesmen')->nullable();
            $table->string('waktu_asesmen', 50)->nullable();
            $table->string('lokasi_peserta')->nullable();

            // B–F. {"B1": {"status": "sesuai|tidak_sesuai", "catatan": "..."}, ...}
            // Daftar kriteria ada di App\Support\TukChecklist.
            $table->json('items')->nullable();

            // G. Kesimpulan verifikasi awal
            $table->string('kesimpulan_awal', 20)->nullable();   // layak | layak_perbaikan | tidak_layak
            $table->text('catatan_awal')->nullable();

            // H. Hasil pemantauan selama asesmen
            $table->string('hasil_pemantauan', 20)->nullable();  // tidak_ada | ada
            $table->text('uraian_pemantauan')->nullable();

            // I. Validasi pengawas ujian
            $table->string('kesimpulan_akhir', 20)->nullable();  // layak | tidak_layak
            $table->foreignId('pengawas_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('pengawas_name')->nullable();
            $table->string('pengawas_signature_path', 500)->nullable();
            $table->timestamp('verified_at')->nullable();        // saat kesimpulan awal pertama kali diisi

            $table->timestamps();

            $table->unique(['exam_session_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tuk_verifications');
    }
};
