<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('participants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();

            // Data Pribadi (FR.APL.01 Bagian 1a)
            $table->string('nik')->nullable();
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->enum('jenis_kelamin', ['L', 'P'])->nullable();
            $table->string('kebangsaan')->default('Indonesia');
            $table->text('alamat_rumah')->nullable();
            $table->string('kode_pos_rumah', 10)->nullable();
            $table->string('telp_rumah')->nullable();
            $table->string('hp')->nullable();
            $table->string('email_alt')->nullable();
            $table->enum('kualifikasi_pendidikan', ['SD', 'SMP', 'SMA', 'D3', 'S1', 'S2', 'S3'])->nullable();

            // Data Pekerjaan (FR.APL.01 Bagian 1b)
            $table->string('institusi')->nullable();
            $table->string('jabatan')->nullable();
            $table->text('alamat_kantor')->nullable();
            $table->string('kode_pos_kantor', 10)->nullable();
            $table->string('telp_kantor')->nullable();
            $table->string('fax_kantor')->nullable();
            $table->string('email_kantor')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('participants');
    }
};
