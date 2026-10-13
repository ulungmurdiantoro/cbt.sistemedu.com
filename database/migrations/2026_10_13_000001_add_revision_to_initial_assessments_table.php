<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Revisi dokumen FR.APL.03 yang dipakai saat penilaian disimpan. Penilaian yang sudah
     * ada dibuat dengan Rev.03 (default kolom); penilaian baru diisi controller dengan
     * InitialAssessmentRubric::CURRENT.
     */
    public function up(): void
    {
        Schema::table('initial_assessments', function (Blueprint $table) {
            $table->string('revision', 10)->default('Rev.03')->after('classroom_id');
        });
    }

    public function down(): void
    {
        Schema::table('initial_assessments', function (Blueprint $table) {
            $table->dropColumn('revision');
        });
    }
};
