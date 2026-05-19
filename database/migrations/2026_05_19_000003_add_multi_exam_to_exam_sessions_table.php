<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->unsignedBigInteger('exam_id_pg')->nullable()->after('exam_id');
            $table->unsignedBigInteger('exam_id_esai')->nullable()->after('exam_id_pg');
            $table->boolean('has_wawancara')->default(false)->after('exam_id_esai');

            $table->foreign('exam_id_pg')->references('id')->on('exams')->nullOnDelete();
            $table->foreign('exam_id_esai')->references('id')->on('exams')->nullOnDelete();
        });

        // migrate existing exam_id to the appropriate new column based on exam type
        \DB::statement("
            UPDATE exam_sessions es
            JOIN exams e ON e.id = es.exam_id
            SET
                es.exam_id_pg   = CASE WHEN e.type = 'Pilihan Ganda' THEN es.exam_id ELSE NULL END,
                es.exam_id_esai = CASE WHEN e.type IN ('Essay','Essay Migas') THEN es.exam_id ELSE NULL END
            WHERE es.exam_id IS NOT NULL
        ");
    }

    public function down(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->dropForeign(['exam_id_pg']);
            $table->dropForeign(['exam_id_esai']);
            $table->dropColumn(['exam_id_pg', 'exam_id_esai', 'has_wawancara']);
        });
    }
};
