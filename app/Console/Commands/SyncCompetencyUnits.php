<?php

namespace App\Console\Commands;

use App\Models\Classroom;
use Database\Seeders\CompetencyUnitsSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Samakan unit kompetensi kelas yang SUDAH ADA dengan daftar di CompetencyUnitsSeeder::skemas()
 * (mengikuti dokumen skema). Berbeda dengan seeder, perintah ini tidak pernah membuat kelas
 * atau mengubah judul/kode kelas: kelas dicocokkan lewat classrooms_code, karena judul kelas
 * di database produksi sudah berbeda dari judul di seeder. Hanya kode, judul (ID), dan urutan
 * unit yang disentuh; judul_unit_en & kode_unit_asli dibiarkan.
 */
class SyncCompetencyUnits extends Command
{
    /** Kode kelas di seeder => kode lama yang mungkin masih dipakai di database. */
    private const KODE_KELAS_LAMA = [
        'FMO' => ['FSI'],
        'LQO' => ['ISL'],
    ];

    protected $signature = 'competency-units:sync
        {--dry-run : Tampilkan perubahan unit tanpa menyimpan}
        {--force : Jalankan tanpa konfirmasi}';

    protected $description = 'Samakan unit kompetensi kelas yang ada dengan dokumen skema (tanpa membuat kelas baru)';

    public function handle(): int
    {
        $changes = [];
        $skipped = [];

        foreach (CompetencyUnitsSeeder::skemas() as $skema) {
            $codes = [$skema['classrooms_code'], ...(self::KODE_KELAS_LAMA[$skema['classrooms_code']] ?? [])];
            $classroom = Classroom::whereIn('classrooms_code', $codes)->first();

            if (!$classroom) {
                $skipped[] = "{$skema['classrooms_code']} — {$skema['title']}";

                continue;
            }

            $existing = $classroom->competencyUnits()->get()->keyBy('kode_unit');
            $wanted = [];

            foreach ($skema['units'] as $i => $unit) {
                [$kode, $judul] = $unit;
                $order = $i + 1;
                $wanted[] = $kode;
                $current = $existing->get($kode);

                if (!$current) {
                    $changes[] = ['classroom' => $classroom, 'action' => 'tambah', 'kode' => $kode, 'old' => '', 'new' => $judul, 'order' => $order];
                } elseif ($current->judul_unit !== $judul || (int) $current->order !== $order) {
                    $changes[] = ['classroom' => $classroom, 'action' => 'ubah', 'unit' => $current, 'kode' => $kode, 'old' => $current->judul_unit, 'new' => $judul, 'order' => $order];
                }
            }

            // Bukan ->except(): kode unit bertitik (SP.AIL.001.01) dibaca sebagai dot-notation oleh Arr::except.
            foreach ($existing->reject(fn ($unit) => in_array($unit->kode_unit, $wanted, true)) as $unit) {
                $changes[] = ['classroom' => $classroom, 'action' => 'hapus', 'unit' => $unit, 'kode' => $unit->kode_unit, 'old' => $unit->judul_unit, 'new' => ''];
            }
        }

        foreach ($skipped as $s) {
            $this->warn("Kelas tidak ditemukan, dilewati: {$s}");
        }

        if (!$changes) {
            $this->info('Unit kompetensi semua kelas sudah sesuai dokumen skema.');

            return self::SUCCESS;
        }

        $this->table(['Kelas', 'Aksi', 'Kode unit', 'Judul lama', 'Judul baru'], array_map(fn ($c) => [
            $c['classroom']->classrooms_code, $c['action'], $c['kode'], $c['old'], $c['new'],
        ], $changes));

        if ($this->option('dry-run')) {
            $this->warn('Dry run — ' . count($changes) . ' perubahan unit, belum ada yang disimpan.');

            return self::SUCCESS;
        }

        if (!$this->option('force') && !$this->confirm('Terapkan ' . count($changes) . ' perubahan unit di atas?')) {
            $this->line('Dibatalkan.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($changes) {
            foreach ($changes as $c) {
                match ($c['action']) {
                    'tambah' => $c['classroom']->competencyUnits()->create([
                        'kode_unit' => $c['kode'], 'judul_unit' => $c['new'], 'order' => $c['order'],
                    ]),
                    'ubah'  => $c['unit']->update(['judul_unit' => $c['new'], 'order' => $c['order']]),
                    'hapus' => $c['unit']->delete(),
                };
            }
        });

        $this->info('Selesai — ' . count($changes) . ' perubahan unit disimpan. PDF sertifikat/SK yang sudah ter-cache tidak berubah; PDF yang dibuat ulang memakai unit baru.');

        return self::SUCCESS;
    }
}
