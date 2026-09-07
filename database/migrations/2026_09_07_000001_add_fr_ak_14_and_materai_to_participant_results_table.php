<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // FR.AK.14 — Surat Pernyataan Pemegang Sertifikat. Peserta tanda tangan di
    // sini (setelah LULUS & hasil difinalisasi), lalu materai elektroniknya
    // dibubuhkan otomatis lewat API Peruri e-Meterai (gratis, ditanggung LSP
    // lewat saldo Peruri POS-nya sendiri — tidak ada langkah bayar peserta).
    // Download SK & Sertifikat digembok sampai materai_status = 'stamped'.
    public function up(): void
    {
        Schema::table('participant_results', function (Blueprint $table) {
            $table->string('fr_ak_14_signature_path')->nullable()->after('attempt');
            $table->timestamp('fr_ak_14_signed_at')->nullable()->after('fr_ak_14_signature_path');

            $table->enum('materai_status', ['none', 'processing', 'stamped', 'failed'])
                ->default('none')->after('fr_ak_14_signed_at');
            $table->timestamp('materai_stamped_at')->nullable()->after('materai_status');
            $table->string('materai_document_path')->nullable()->after('materai_stamped_at');
            $table->text('materai_failure_reason')->nullable()->after('materai_document_path');
        });
    }

    public function down(): void
    {
        Schema::table('participant_results', function (Blueprint $table) {
            $table->dropColumn([
                'fr_ak_14_signature_path',
                'fr_ak_14_signed_at',
                'materai_status',
                'materai_stamped_at',
                'materai_document_path',
                'materai_failure_reason',
            ]);
        });
    }
};
