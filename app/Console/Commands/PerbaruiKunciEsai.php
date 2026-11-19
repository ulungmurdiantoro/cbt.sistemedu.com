<?php

namespace App\Console\Commands;

use App\Models\Classroom;
use App\Models\Essay;
use App\Models\Exam;
use App\Support\RichText;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Perbarui kunci/panduan jawaban (kolom essays.answer) soal esai yang SUDAH ada, dari dokumen FR.IA.06
 * yang direvisi — tanpa mengubah teks soal, urutan soal, atau jawaban peserta.
 *
 * Data: database/data/kunci-esai/{nama}.json → { classroom_code, sumber, esai: [{ question, answer }] }.
 * Soal dicocokkan lewat teks pertanyaan (huruf/angka saja, tanpa tag/spasi/tanda baca), bukan lewat ID, jadi
 * hasilnya sama di database mana pun. Yang dicari: ujian Essay/Essay Migas di skema itu (atau --exam).
 * Aman dijalankan ulang: kunci yang sudah sama dilewati.
 */
class PerbaruiKunciEsai extends Command
{
    protected $signature = 'kunci-esai:perbarui
        {data : Nama file di database/data/kunci-esai (tanpa .json), mis. tot-obe-rev01}
        {--exam=* : Hanya ujian dengan ID ini}
        {--dry-run : Tampilkan rencana tanpa menyimpan}
        {--force : Jalankan tanpa konfirmasi}';

    protected $description = 'Perbarui kunci jawaban soal esai yang sudah ada dari data dokumen FR.IA.06';

    public function handle(): int
    {
        $path = database_path('data/kunci-esai/' . basename($this->argument('data'), '.json') . '.json');
        if (! is_file($path)) {
            $this->error("Data tidak ditemukan: {$path}");

            return self::FAILURE;
        }

        $data = json_decode(file_get_contents($path), true);
        if (! is_array($data) || empty($data['classroom_code']) || empty($data['esai']) || ! is_array($data['esai'])) {
            $this->error('Format data salah: perlu classroom_code dan daftar esai.');

            return self::FAILURE;
        }

        $keys = [];
        foreach ($data['esai'] as $i => $item) {
            $q = self::normalize($item['question'] ?? '');
            if ($q === '' || ! RichText::hasContent($item['answer'] ?? '')) {
                $this->error('Soal ke-' . ($i + 1) . ' di data tidak punya pertanyaan atau kunci.');

                return self::FAILURE;
            }
            if (isset($keys[$q])) {
                $this->error('Soal ke-' . ($i + 1) . ' di data kembar dengan soal lain.');

                return self::FAILURE;
            }
            // Dibersihkan sekarang (kunci dirender v-html di halaman asesor/admin), supaya perbandingan
            // dengan kunci tersimpan memakai bentuk yang sama dan menjalankan ulang tidak menulis ulang.
            $keys[$q] = RichText::clean($item['answer']);
        }

        $classroom = Classroom::where('classrooms_code', $data['classroom_code'])->first();
        if (! $classroom) {
            $this->error("Skema dengan kode {$data['classroom_code']} tidak ditemukan.");

            return self::FAILURE;
        }

        $exams = Exam::where('classroom_id', $classroom->id)
            ->whereIn('type', ['Essay', 'Essay Migas'])
            ->when($this->option('exam'), fn ($q, $ids) => $q->whereIn('id', $ids))
            ->orderBy('id')
            ->get();

        if ($exams->isEmpty()) {
            $this->warn("Tidak ada ujian esai di skema {$classroom->title}.");

            return self::SUCCESS;
        }

        $this->info("Sumber: " . ($data['sumber'] ?? basename($path)) . " — skema {$classroom->title}");

        // Soal dari dokumen FR.IA.06 adalah uraian: tandai is_essay supaya labelnya "Poin / kisi jawaban",
        // bukan "Jawaban benar" (soal lama dibuat sebelum penanda ini ada). Set "uraian": false di data untuk melewatinya.
        // Kolom is_essay tidak dibuat migrasi (ada di database produksi), jadi dicek dulu — sama seperti paket-soal:pasang.
        $markUraian = (bool) ($data['uraian'] ?? true) && Schema::hasColumn('essays', 'is_essay');

        $updates   = [];   // essay id => kolom yang diubah
        $rows      = [];
        $unmatched = 0;
        $keyCount  = 0;
        $flagCount = 0;
        foreach ($exams as $exam) {
            foreach (Essay::where('exam_id', $exam->id)->orderBy('id')->get()->values() as $no => $essay) {
                $new = $keys[self::normalize($essay->question)] ?? null;
                $old = $essay->answer;

                $status = match (true) {
                    $new === null                           => 'tidak ada di dokumen',
                    trim((string) $old) === trim($new)      => 'sudah sama',
                    RichText::hasContent($old)              => 'ganti kunci lama',
                    default                                 => 'isi kunci',
                };
                $unmatched += $new === null ? 1 : 0;

                $change = [];
                if (in_array($status, ['ganti kunci lama', 'isi kunci'], true)) {
                    $change['answer'] = $new;
                    $keyCount++;
                }
                if ($new !== null && $markUraian && ! $essay->is_essay) {
                    $change['is_essay'] = true;
                    $flagCount++;
                    $status .= ' + tandai uraian';
                }
                if ($change) {
                    $updates[$essay->id] = $change;
                }

                $rows[] = [$exam->id, $no + 1, $essay->id, mb_strimwidth(trim(strip_tags(html_entity_decode((string) $essay->question))), 0, 70, '…'), $status];
            }
        }

        $this->table(['Ujian', 'No', 'ID soal', 'Pertanyaan', 'Status'], $rows);
        $this->line("{$keyCount} kunci akan diperbarui; {$flagCount} soal ditandai uraian; {$unmatched} soal tidak ditemukan di dokumen (dibiarkan).");

        if ($this->option('dry-run') || $updates === []) {
            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('Simpan perubahan kunci jawaban?')) {
            return self::SUCCESS;
        }

        DB::transaction(function () use ($updates) {
            foreach ($updates as $id => $change) {
                Essay::whereKey($id)->firstOrFail()->forceFill($change)->save();
            }
        });

        $this->info("{$keyCount} kunci jawaban diperbarui, {$flagCount} soal ditandai uraian.");

        return self::SUCCESS;
    }

    /** Teks pertanyaan → huruf & angka saja (tanpa tag, entitas, spasi, tanda baca), untuk pencocokan. */
    public static function normalize(?string $html): string
    {
        $text = html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return preg_replace('/[^a-z0-9]+/', '', mb_strtolower($text));
    }
}
