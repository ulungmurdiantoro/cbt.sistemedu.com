<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// MySQL/MariaDB dengan explicit_defaults_for_timestamp = OFF menjadikan kolom TIMESTAMP NOT NULL pertama
// `DEFAULT current_timestamp() ON UPDATE current_timestamp()`: setiap UPDATE baris mengganti started_at dengan jam
// database (zona waktu server, mis. WIB) yang lalu dibaca aplikasi sebagai UTC → sisa waktu praktik melonjak (+7 jam).
// started_at dijadikan DATETIME (tanpa pembaruan otomatis) dan nilai yang tergeser dipulihkan dari created_at
// (keduanya diisi now() yang sama saat peserta menekan Mulai — UjianPraktik::start).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_praktik_attempts', function (Blueprint $table) {
            $table->dateTime('started_at')->change();
        });

        DB::table('student_praktik_attempts')
            ->whereNotNull('created_at')
            ->whereColumn('started_at', '!=', 'created_at')
            ->update(['started_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('student_praktik_attempts', function (Blueprint $table) {
            $table->timestamp('started_at')->change();
        });
    }
};
