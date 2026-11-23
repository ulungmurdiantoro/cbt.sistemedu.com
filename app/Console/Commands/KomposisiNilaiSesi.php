<?php

namespace App\Console\Commands;

use App\Models\ExamSession;
use App\Models\GradingScheme;
use Illuminate\Console\Command;

/**
 * Komposisi Nilai beku per sesi (exam_sessions.komposisi_nilai, lihat GradingScheme::forSession):
 *   php artisan komposisi-nilai:sesi                 → daftar sesi yang komposisinya dibekukan
 *   php artisan komposisi-nilai:sesi 86 98           → komposisi yang berlaku untuk sesi itu
 *   php artisan komposisi-nilai:sesi 98 --lepas      → sesi kembali mengikuti Komposisi Nilai skema
 *   php artisan komposisi-nilai:sesi 86 --bekukan    → bekukan dengan Komposisi Nilai skema yang berlaku sekarang
 * Hasil yang sudah Final tidak dihitung ulang; yang belum Final memakai komposisi baru saat Rekap dibuka.
 */
class KomposisiNilaiSesi extends Command
{
    protected $signature = 'komposisi-nilai:sesi
        {sesi?* : ID sesi (kosong = semua sesi yang dibekukan)}
        {--lepas : Hapus komposisi beku — sesi mengikuti Komposisi Nilai skema}
        {--bekukan : Bekukan dengan Komposisi Nilai skema yang berlaku sekarang}
        {--dry-run : Tampilkan saja tanpa menyimpan}';

    protected $description = 'Lihat / bekukan / lepas Komposisi Nilai per sesi';

    public function handle(): int
    {
        if ($this->option('lepas') && $this->option('bekukan')) {
            $this->error('Pilih salah satu: --lepas atau --bekukan.');

            return self::FAILURE;
        }

        $ids = $this->argument('sesi');
        $sessions = $ids
            ? ExamSession::whereIn('id', $ids)->orderBy('id')->get()
            : ExamSession::whereNotNull('komposisi_nilai')->orderBy('id')->get();

        if ($ids && $sessions->count() !== count(array_unique($ids))) {
            $this->error('Sesi tidak ditemukan: ' . implode(', ', array_diff($ids, $sessions->pluck('id')->map(fn ($id) => (string) $id)->all())));

            return self::FAILURE;
        }

        $rows = [];
        foreach ($sessions as $session) {
            $before = $this->text(GradingScheme::forSession($session));
            $action = '';

            if ($this->option('lepas') && $session->komposisi_nilai) {
                $action = 'dilepas → ikut skema';
                if (!$this->option('dry-run')) {
                    $session->update(['komposisi_nilai' => null]);
                }
            } elseif ($this->option('bekukan') && !$session->komposisi_nilai) {
                $session->komposisi_nilai = null;
                $scheme = GradingScheme::forSession($session);
                if ($scheme) {
                    $action = 'dibekukan';
                    if (!$this->option('dry-run')) {
                        $session->update(['komposisi_nilai' => $scheme->snapshot()]);
                    }
                } else {
                    $action = 'skema belum punya Komposisi Nilai — dilewati';
                }
            }

            $rows[] = [
                "#{$session->id}",
                $session->title,
                $session->komposisi_nilai ? 'beku' : 'ikut skema',
                $before,
                $action ?: '—',
            ];
        }

        $this->table(['Sesi', 'Judul', 'Status', 'Komposisi berlaku', 'Tindakan'], $rows);
        if ($this->option('dry-run')) {
            $this->warn('Dry run — tidak ada yang disimpan.');
        }

        return self::SUCCESS;
    }

    private function text(?GradingScheme $s): string
    {
        if (!$s) {
            return '—';
        }
        $praktik = (float) $s->bobot_praktik_1 + (float) $s->bobot_praktik_2;

        return sprintf('Tulis %s (PG %s%%) + Lisan %s%s, lulus ≥%s',
            $this->n($s->bobot_ujian_tulis), $this->n($s->proporsi_pg), $this->n($s->bobot_wawancara),
            $praktik > 0 ? ' + Praktik ' . $this->n($praktik) : '', $this->n($s->nilai_kelulusan));
    }

    private function n($v): string
    {
        return rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
    }
}
