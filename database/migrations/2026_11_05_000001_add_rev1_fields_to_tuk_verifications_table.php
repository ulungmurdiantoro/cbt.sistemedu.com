<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // FR.TUK.06 Rev.1. Checklist lama tetap Rev.0 (kolom revision default 'Rev.0') dan dicetak dengan
    // formatnya sendiri; checklist baru disimpan sebagai Rev.1 — lihat App\Support\TukChecklist.
    public function up(): void
    {
        Schema::table('tuk_verifications', function (Blueprint $table) {
            $table->string('revision', 10)->default('Rev.0')->after('student_id');

            // A. Lokasi Peserta Rev.1 = pilihan (lokasi_peserta) + isian "Lainnya"
            $table->string('lokasi_lainnya')->nullable()->after('lokasi_peserta');

            // H. Ketidaksesuaian, perbaikan & verifikasi ulang:
            // [{"uraian": "...", "tindakan": "...", "batas_waktu": "...", "status": "memenuhi|tidak_memenuhi"}]
            $table->json('ketidaksesuaian')->nullable()->after('catatan_awal');
            $table->string('kesimpulan_ulang', 20)->nullable()->after('ketidaksesuaian');   // memenuhi | tidak_memenuhi

            // I. Hasil pemantauan: tindakan + dampak terhadap kelanjutan asesmen
            $table->text('tindakan_pemantauan')->nullable()->after('uraian_pemantauan');
            $table->string('dampak_pemantauan', 30)->nullable()->after('tindakan_pemantauan');
            $table->string('dampak_lainnya')->nullable()->after('dampak_pemantauan');
        });
    }

    public function down(): void
    {
        Schema::table('tuk_verifications', function (Blueprint $table) {
            $table->dropColumn([
                'revision', 'lokasi_lainnya', 'ketidaksesuaian', 'kesimpulan_ulang',
                'tindakan_pemantauan', 'dampak_pemantauan', 'dampak_lainnya',
            ]);
        });
    }
};
