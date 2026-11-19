<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Jenis ujian "Ujian Praktik": panduan + template di ujian (setiap versi soal = ujian tersendiri),
// dipilih per sesi lewat exam_id_praktik. Sesi tanpa ujian praktik tetap memakai "Upload Tugas".
// Durasi memakai exams.duration (0 = tanpa batas waktu). Waktu mulai peserta per sesi dicatat
// di student_praktik_attempts.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->text('praktik_panduan')->nullable();
            $table->string('praktik_template_path')->nullable();
            $table->string('praktik_template_name')->nullable();
        });

        $this->alterBehindView('exam_sessions', function (Blueprint $table) {
            $table->foreignId('exam_id_praktik')->nullable()->after('exam_id_esai')
                ->constrained('exams')->nullOnDelete();
        });

        Schema::create('student_praktik_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('exam_session_id')->constrained('exam_sessions')->cascadeOnDelete();
            $table->foreignId('exam_id')->nullable()->constrained('exams')->nullOnDelete();
            $table->timestamp('started_at');
            // Durasi (menit) saat peserta mulai — mengubah durasi ujian belakangan tidak menggeser batas waktunya.
            $table->unsignedSmallInteger('durasi');
            $table->timestamps();

            $table->unique(['student_id', 'exam_session_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_praktik_attempts');

        $this->alterBehindView('exam_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('exam_id_praktik');
        });

        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn(['praktik_panduan', 'praktik_template_path', 'praktik_template_name']);
        });
    }

    /**
     * SQLite (lokal/test) membangun ulang exam_sessions untuk menambah foreign key, dan RENAME tabel
     * hasil bangun ulang ditolak karena view v_sertifikasi_kelulusan merujuk tabel ini (lihat migrasi
     * 2026_10_26_000002). MySQL tidak terpengaruh.
     */
    private function alterBehindView(string $table, \Closure $callback): void
    {
        $sqlite = DB::getDriverName() === 'sqlite';
        if ($sqlite) {
            DB::statement('PRAGMA legacy_alter_table = ON');
        }

        try {
            Schema::table($table, $callback);
        } finally {
            if ($sqlite) {
                DB::statement('PRAGMA legacy_alter_table = OFF');
            }
        }
    }
};
