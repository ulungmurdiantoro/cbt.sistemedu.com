<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * View read-only untuk integrasi antar-repo: hanya berisi peserta yang
     * sudah LULUS dan sertifikatnya sudah terbit/terdistribusi. Kolom sengaja
     * dibatasi (tidak SELECT *) supaya data sensitif (NIK, path tanda tangan,
     * status materai, dll) tidak ikut terekspos ke luar, dan skema internal
     * participant_results bisa berubah tanpa memutus kontrak ke repo lain.
     */
    public function up(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_sertifikasi_kelulusan');

        DB::statement(<<<SQL
            CREATE VIEW v_sertifikasi_kelulusan AS
            SELECT
                s.id              AS student_id,
                s.no_participant,
                s.name            AS nama_peserta,
                s.institution,
                c.kode_skema,
                c.title           AS classroom_title,
                es.id             AS exam_session_id,
                es.title          AS nama_sesi,
                es.kode_batch,
                pr.keputusan,
                pr.nilai_akhir,
                pr.sk_number,
                pr.sertifikat_number,
                pr.finalized_at,
                pr.distributed_at AS sertifikat_terbit_at,
                pr.with_kan,
                pr.valid_until
            FROM participant_results pr
            JOIN students s       ON s.id = pr.student_id
            JOIN classrooms c     ON c.id = s.classroom_id
            JOIN exam_sessions es ON es.id = pr.exam_session_id
            WHERE pr.keputusan = 'LULUS'
              AND pr.sertifikat_number IS NOT NULL
              AND pr.distributed_at IS NOT NULL
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_sertifikasi_kelulusan');
    }
};
