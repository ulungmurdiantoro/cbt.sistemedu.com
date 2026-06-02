<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('certificate_templates');
    }

    public function down(): void
    {
        Schema::create('certificate_templates', function (Blueprint $table) {
            $table->id();
            $table->string('kop_path', 500)->nullable();
            $table->string('kop_logo2_path', 500)->nullable();
            $table->string('ttd_path', 500)->nullable();
            $table->string('bg_sertifikat_path', 500)->nullable();
            $table->string('nama_penandatangan')->nullable();
            $table->string('jabatan_penandatangan')->nullable();
            $table->string('kota')->nullable();
            $table->text('sk_body')->nullable();
            $table->timestamps();
        });
    }
};
