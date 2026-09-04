<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Dokumen "Keputusan Sertifikasi" — berita acara satu sesi ujian, diterbitkan
// sekali saat Pengambil Keputusan menekan Finalisasi Semua. Nomornya wajib
// tetap sama tiap diunduh ulang, jadi disimpan di sini (bukan dihitung ulang
// tiap request seperti nomor SK/SP per peserta).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->string('keputusan_number')->nullable()->after('kode_batch');
            $table->timestamp('keputusan_issued_at')->nullable()->after('keputusan_number');
            $table->foreignId('keputusan_issued_by')->nullable()->after('keputusan_issued_at')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('keputusan_issued_by');
            $table->dropColumn(['keputusan_number', 'keputusan_issued_at']);
        });
    }
};
