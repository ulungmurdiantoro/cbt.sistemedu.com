<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Ujian Praktik bisa terdiri dari dua bagian per sesi (mis. Lifting: Calculation 300 menit + Gambar 180 menit).
// Bagian 1 = exam_sessions.exam_id_praktik (yang sudah ada), bagian 2 = exam_id_praktik_2. Setiap bagian punya
// waktu mulai & unggahan sendiri: student_praktik_attempts / student_tasks dibedakan lewat kolom `bagian`
// (data lama = bagian 1, termasuk Upload Tugas biasa).
return new class extends Migration
{
    public function up(): void
    {
        $this->alterBehindView('exam_sessions', function (Blueprint $table) {
            $table->foreignId('exam_id_praktik_2')->nullable()->after('exam_id_praktik')
                ->constrained('exams')->nullOnDelete();
        });

        foreach (['student_tasks', 'student_praktik_attempts'] as $name) {
            // Indeks unik baru dibuat dulu: MySQL memakai indeks unik lama untuk foreign key student_id,
            // jadi indeks lama baru bisa dibuang setelah ada indeks lain yang diawali student_id.
            Schema::table($name, function (Blueprint $table) use ($name) {
                $table->unsignedTinyInteger('bagian')->default(1)->after('exam_session_id');
                $table->unique(['student_id', 'exam_session_id', 'bagian'], "{$name}_student_session_bagian_unique");
            });
            Schema::table($name, function (Blueprint $table) {
                $table->dropUnique(['student_id', 'exam_session_id']);
            });
        }
    }

    public function down(): void
    {
        foreach (['student_tasks', 'student_praktik_attempts'] as $name) {
            DB::table($name)->where('bagian', '>', 1)->delete();
            Schema::table($name, function (Blueprint $table) {
                $table->unique(['student_id', 'exam_session_id']);
            });
            Schema::table($name, function (Blueprint $table) use ($name) {
                $table->dropUnique("{$name}_student_session_bagian_unique");
                $table->dropColumn('bagian');
            });
        }

        $this->alterBehindView('exam_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('exam_id_praktik_2');
        });
    }

    /** Sama dengan migrasi 2026_11_18_000001: SQLite menolak RENAME tabel yang dirujuk view v_sertifikasi_kelulusan. */
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
