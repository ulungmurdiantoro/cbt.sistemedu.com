<?php

namespace App\Console\Commands;

use App\Models\Classroom;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\GradingScheme;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

/**
 * Komposisi Nilai beku per sesi (exam_sessions.komposisi_nilai, lihat GradingScheme::forSession):
 *   php artisan komposisi-nilai:sesi                     → daftar sesi yang komposisinya dibekukan
 *   php artisan komposisi-nilai:sesi 86 98               → komposisi yang berlaku untuk sesi itu
 *   php artisan komposisi-nilai:sesi --skema=PSP         → semua sesi satu skema (kode kelas)
 *   php artisan komposisi-nilai:sesi 98 --lepas          → sesi kembali mengikuti Komposisi Nilai skema
 *   php artisan komposisi-nilai:sesi 86 --bekukan        → bekukan dengan Komposisi Nilai skema yang berlaku sekarang
 *   php artisan komposisi-nilai:sesi 86 --bekukan --tulis=70 --pg=65 --lisan=30 --praktik1=0 --praktik2=0 --lulus=60
 *       → bekukan dengan komposisi yang ditulis (mis. komposisi lama yang sudah tertimpa di skema; menimpa yang sudah beku)
 * Hasil yang sudah Final tidak dihitung ulang; yang belum Final memakai komposisi ini saat Rekap dibuka.
 */
class KomposisiNilaiSesi extends Command
{
    protected $signature = 'komposisi-nilai:sesi
        {sesi?* : ID sesi (kosong = semua sesi yang dibekukan, atau semua sesi skema --skema)}
        {--skema= : Kode skema/kelas (mis. PSP) — semua sesi yang memakai ujian skema itu}
        {--lepas : Hapus komposisi beku — sesi mengikuti Komposisi Nilai skema}
        {--bekukan : Bekukan dengan Komposisi Nilai skema yang berlaku sekarang, atau dengan nilai di bawah}
        {--tulis= : Bobot Ujian Tulis (% nilai akhir)}
        {--pg= : Proporsi PG di Ujian Tulis (%), sisanya Esai}
        {--lisan= : Bobot Lisan & Keterampilan / wawancara (% nilai akhir)}
        {--praktik1= : Bobot Ujian Praktik/Keterampilan bagian 1 (% nilai akhir)}
        {--praktik2= : Bobot Ujian Praktik/Keterampilan bagian 2 (% nilai akhir)}
        {--lulus= : Nilai kelulusan}
        {--dry-run : Tampilkan saja tanpa menyimpan}';

    protected $description = 'Lihat / bekukan / lepas Komposisi Nilai per sesi';

    private const NILAI = ['tulis', 'pg', 'lisan', 'praktik1', 'praktik2', 'lulus'];

    public function handle(): int
    {
        if ($this->option('lepas') && $this->option('bekukan')) {
            $this->error('Pilih salah satu: --lepas atau --bekukan.');

            return self::FAILURE;
        }

        $manual = $this->manualValues();
        if ($manual === false) {
            return self::FAILURE;
        }
        if ($manual && !$this->option('bekukan')) {
            $this->error('Nilai komposisi (--tulis, --pg, …) hanya dipakai bersama --bekukan.');

            return self::FAILURE;
        }

        $sessions = $this->sessions();
        if ($sessions === null) {
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
            } elseif ($this->option('bekukan') && ($manual || !$session->komposisi_nilai)) {
                $current = GradingScheme::forSession($session);
                if (!$current && !$manual) {
                    $action = 'skema belum punya Komposisi Nilai — dilewati';
                } else {
                    $snapshot = $this->applyManual($current?->snapshot() ?? array_fill_keys(GradingScheme::SNAPSHOT_FIELDS, null), $manual ?: []);
                    $action = 'dibekukan → ' . $this->text((new GradingScheme())->forceFill($snapshot));
                    if (!$this->option('dry-run')) {
                        $session->update(['komposisi_nilai' => $snapshot]);
                    }
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

    /** Sesi yang dipilih: ID, atau semua sesi skema --skema, atau (tanpa keduanya) semua sesi yang beku. Null = galat. */
    private function sessions(): ?Collection
    {
        $ids = $this->argument('sesi');
        $query = ExamSession::query()->orderBy('id');

        if ($kode = $this->option('skema')) {
            $classroom = Classroom::where('classrooms_code', $kode)->first();
            if (!$classroom) {
                $this->error("Skema {$kode} tidak ditemukan.");

                return null;
            }
            $examIds = Exam::where('classroom_id', $classroom->id)->pluck('id');
            $query->where(fn ($q) => $q->whereIn('exam_id_pg', $examIds)->orWhereIn('exam_id_esai', $examIds)
                ->orWhereIn('exam_id_praktik', $examIds));
        } elseif (!$ids) {
            $query->whereNotNull('komposisi_nilai');
        }

        if ($ids) {
            $query->whereIn('id', $ids);
        }
        $sessions = $query->get();

        if ($ids && $sessions->count() !== count(array_unique($ids))) {
            $missing = array_diff($ids, $sessions->pluck('id')->map(fn ($id) => (string) $id)->all());
            $this->error('Sesi tidak ditemukan' . ($this->option('skema') ? ' di skema ini' : '') . ': ' . implode(', ', $missing));

            return null;
        }

        return $sessions;
    }

    /** Nilai komposisi dari opsi (hanya yang diisi). [] = tidak ada; false = tidak valid. */
    private function manualValues(): array|false
    {
        $values = [];
        foreach (self::NILAI as $key) {
            $v = $this->option($key);
            if ($v === null || $v === '') {
                continue;
            }
            if (!is_numeric($v) || $v < 0 || $v > 100) {
                $this->error("--{$key} harus angka 0–100.");

                return false;
            }
            $values[$key] = (float) $v;
        }

        if ($values && count($values) !== count(self::NILAI)) {
            $this->error('Isi semua nilai komposisi: --tulis --pg --lisan --praktik1 --praktik2 --lulus.');

            return false;
        }
        if ($values) {
            $total = $values['tulis'] + $values['lisan'] + $values['praktik1'] + $values['praktik2'];
            if (abs($total - 100) > 0.01) {
                $this->error("Tulis + Lisan + Praktik harus 100 (sekarang {$total}).");

                return false;
            }
        }

        return $values;
    }

    /** Timpa salinan komposisi dengan nilai manual; bobot PG/Esai dihitung dari Ujian Tulis × proporsi PG (sama dengan form). */
    private function applyManual(array $snapshot, array $m): array
    {
        if (!$m) {
            return $snapshot;
        }

        return array_merge($snapshot, [
            'bobot_ujian_tulis' => $m['tulis'],
            'proporsi_pg'       => $m['pg'],
            'bobot_pg'          => round($m['tulis'] * $m['pg'] / 100, 2),
            'bobot_esai'        => round($m['tulis'] * (100 - $m['pg']) / 100, 2),
            'bobot_wawancara'   => $m['lisan'],
            'bobot_praktik_1'   => $m['praktik1'],
            'bobot_praktik_2'   => $m['praktik2'],
            'nilai_kelulusan'   => $m['lulus'],
        ]);
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
