<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Verifikasi Manager Sertifikasi — tahap kedua setelah verifikasi Pengambil Keputusan
    // (manager_verified_at), syarat finalisasi. Peserta yang sudah Final sebelum alur ini
    // sengaja dibiarkan kosong (tidak pernah diverifikasi Manager Sertifikasi).
    public function up(): void
    {
        $this->alterBehindView('participant_results', function (Blueprint $table) {
            $table->timestamp('cert_manager_verified_at')->nullable()->after('manager_verified_by');
            $table->foreignId('cert_manager_verified_by')->nullable()->after('cert_manager_verified_at')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        $this->alterBehindView('participant_results', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cert_manager_verified_by');
            $table->dropColumn('cert_manager_verified_at');
        });
    }

    /**
     * SQLite (lokal/test) membangun ulang tabel untuk menambah/menghapus foreign key, dan RENAME
     * tabel hasil bangun ulang ditolak karena view v_sertifikasi_kelulusan merujuk tabel ini.
     * legacy_alter_table melewati pengecekan view itu; nama tabelnya tetap sama. MySQL tidak terpengaruh.
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
