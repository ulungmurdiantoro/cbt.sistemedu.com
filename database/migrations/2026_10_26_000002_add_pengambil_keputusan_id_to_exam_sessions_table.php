<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Pengambil Keputusan ditunjuk admin per sesi (bukan per peserta). Hanya PK yang ditunjuk
    // yang melihat & memverifikasi sesi itu di portalnya.
    public function up(): void
    {
        $this->alterBehindView('exam_sessions', function (Blueprint $table) {
            $table->foreignId('pengambil_keputusan_id')->nullable()->after('keputusan_issued_by')
                ->constrained('users')->nullOnDelete();
        });

        // Sesi lama tidak hilang dari portal PK yang sudah mengerjakannya: tunjuk PK yang
        // menerbitkan Keputusan, atau yang terakhir mencentang Verifikasi.
        DB::table('exam_sessions')->select('id', 'keputusan_issued_by')->orderBy('id')->chunkById(200, function ($sessions) {
            foreach ($sessions as $session) {
                $userId = $session->keputusan_issued_by ?? DB::table('participant_results')
                    ->where('exam_session_id', $session->id)
                    ->whereNotNull('manager_verified_by')
                    ->orderByDesc('manager_verified_at')
                    ->value('manager_verified_by');

                if ($userId && DB::table('users')->where('id', $userId)->exists()) {
                    DB::table('exam_sessions')->where('id', $session->id)->update(['pengambil_keputusan_id' => $userId]);
                }
            }
        });
    }

    public function down(): void
    {
        $this->alterBehindView('exam_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pengambil_keputusan_id');
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
