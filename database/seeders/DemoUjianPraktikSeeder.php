<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\AsesorAssignment;
use App\Models\Exam;
use App\Models\ExamGroup;
use App\Models\ExamSession;
use App\Models\Student;
use App\Models\User;
use App\Services\StudentEnrollmentService;
use Illuminate\Database\Seeder;

/**
 * Data dummy untuk mencoba alur peserta & asesor paket soal V2 di LOKAL:
 * satu sesi "DEMO V2 - {skema}" per skema yang ujian V2-nya sudah terpasang (paket-soal:pasang v2), memakai PG V2,
 * Esai V2 dan Ujian Praktik/Keterampilan V2 (Lifting: dua bagian), plus dua peserta per sesi (login ujian di "/" dengan
 * No. Peserta DEMO-{SKEMA}-01 / -02) yang ditugaskan ke satu asesor demo.
 *
 *   php artisan db:seed --class=DemoUjianPraktikSeeder
 *
 * Aman dijalankan ulang (dikenali lewat kode sesi/No. Peserta/email); ujian sesi disamakan dengan ujian V2 terbaru.
 * Tidak berjalan di production. Akun asesor demo: email & password ada di konstanta di bawah (hanya untuk lokal).
 */
class DemoUjianPraktikSeeder extends Seeder
{
    private const ASESOR_EMAIL = 'asesor.demo@cbt.test';
    private const ASESOR_PASSWORD = 'password';

    private const SKEMA = ['lifting', 'hse', 'legal', 'esg', 'glp', 'qc', 'fsms', 'rnd', 'panelis', 'iso17025'];

    public function run(StudentEnrollmentService $enrollment): void
    {
        if (app()->environment('production')) {
            $this->command?->error('DemoUjianPraktikSeeder tidak dijalankan di production.');

            return;
        }

        $asesor = User::firstOrCreate(
            ['email' => self::ASESOR_EMAIL],
            ['users_code' => 'ASR-DEMO', 'name' => 'Asesor Demo', 'password' => bcrypt(self::ASESOR_PASSWORD)],
        );
        if (! $asesor->hasRole(UserRole::Asesor)) {
            $asesor->roleAssignments()->create(['role' => UserRole::Asesor->value]);
        }

        $rows = [];
        foreach (self::SKEMA as $key) {
            $exam = fn (string $part) => Exam::where('exams_code', "exms-v2-{$key}-{$part}")->first();
            $pg = $exam('pg');
            if (! $pg) {
                $rows[] = [$key, '—', 'ujian V2 belum terpasang (jalankan paket-soal:pasang v2)', '—'];

                continue;
            }
            $esai = $exam('esai');
            $praktik = $exam('praktik');
            $praktik2 = $praktik ? $exam('praktik-2') : null;
            $code = strtoupper($key);

            $session = ExamSession::firstOrNew(['exam_sessions_code' => "DEMO-V2-{$code}"]);
            $session->fill([
                'title'             => "DEMO V2 - {$pg->classroom?->title}",
                'exam_id_pg'        => $pg->id,
                'exam_id_esai'      => $esai?->id,
                'exam_id_praktik'   => $praktik?->id,
                'exam_id_praktik_2' => $praktik2?->id,
                'has_wawancara'     => true,
                'tempat_ujian'      => 'TUK Demo (lokal)',
                'kode_batch'        => 'DEMO',
            ]);
            if (! $session->exists) {
                $session->start_time = now()->subHour();
                $session->end_time = now()->addDays(14);
            }
            $session->save();

            $nos = [];
            foreach ([1, 2] as $n) {
                $no = sprintf('DEMO-%s-%02d', $code, $n);
                $student = Student::firstOrCreate(['no_participant' => $no], [
                    'classroom_id' => $pg->classroom_id,
                    'name'         => "Peserta Demo {$code} {$n}",
                    'position'     => 'Peserta uji coba',
                    'institution'  => 'Demo',
                    'gender'       => $n === 1 ? 'L' : 'P',
                    'is_active'    => true,
                ]);
                // Ujian sesi bisa berganti (paket diperbarui): daftarkan ujian yang belum terdaftar saja
                $registered = ExamGroup::where('exam_session_id', $session->id)->where('student_id', $student->id)->pluck('exam_id')->all();
                if (array_diff(array_filter([$session->exam_id_pg, $session->exam_id_esai]), $registered)) {
                    ExamGroup::where('exam_session_id', $session->id)->where('student_id', $student->id)->delete();
                    $enrollment->enroll($student->id, $session);
                }
                AsesorAssignment::firstOrCreate(['user_id' => $asesor->id, 'exam_session_id' => $session->id, 'student_id' => $student->id]);
                $nos[] = $no;
            }

            $praktikText = $praktik ? ($praktik2 ? '2 bagian' : '1 bagian') : 'tidak ada';
            $rows[] = [$key, "#{$session->id}", implode(', ', $nos), $praktikText . ($praktik && ! $praktik->praktik_template_path ? ' (template belum ada)' : '')];
        }

        $this->command?->table(['Skema', 'Sesi', 'No. Peserta (login di /)', 'Ujian Praktik/Keterampilan'], $rows);
        $this->command?->info('Asesor demo: ' . self::ASESOR_EMAIL . ' (password di DemoUjianPraktikSeeder) — login di /login.');
    }
}
