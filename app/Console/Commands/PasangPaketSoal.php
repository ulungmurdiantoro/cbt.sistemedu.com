<?php

namespace App\Console\Commands;

use App\Models\Classroom;
use App\Models\Essay;
use App\Models\Exam;
use App\Models\Question;
use App\Support\AnswerFile;
use Illuminate\Console\Command;
use Illuminate\Http\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Pasang paket soal (hasil ekstraksi dokumen FR.IA.04/05/06) sebagai ujian BARU per skema — tanpa import Excel.
 *
 * Data: database/data/paket-soal/{versi}/{skema}.json (+ images/ untuk gambar soal PG).
 * Per skema dibuat: Pilihan Ganda, Esai (bila ada di data), dan Ujian Praktik (panduan + template).
 * Ujian & sesi lama tidak disentuh. Ujian dikenali lewat exams_code "exms-{versi}-{skema}-{pg|esai|praktik}",
 * jadi perintah aman dijalankan ulang: ujian yang sudah ada dilewati, template praktik yang belum ada dilengkapi.
 *
 * Template Ujian Praktik (bisa > 20 MB, jadi tidak lewat upload) dibaca dari folder --templates
 * (default storage/app/paket-soal/{versi}/templates) lalu disalin ke disk private.
 */
class PasangPaketSoal extends Command
{
    private const TYPE_PG = 'Pilihan Ganda';

    private const TYPE_ESAI = 'Essay';

    /** Bila belum ada ujian sejenis di kelas itu untuk dicontoh pengaturannya. */
    private const DEFAULTS = [
        self::TYPE_PG   => ['duration' => 120, 'random_question' => 'Y', 'random_answer' => 'N', 'show_answer' => 'N'],
        self::TYPE_ESAI => ['duration' => 120, 'random_question' => 'N', 'random_answer' => 'N', 'show_answer' => 'N'],
    ];

    protected $signature = 'paket-soal:pasang
        {versi=v2 : Folder paket di database/data/paket-soal}
        {--skema=* : Hanya skema tertentu (nama file tanpa .json, mis. lifting)}
        {--templates= : Folder template Ujian Praktik (default storage/app/paket-soal/{versi}/templates)}
        {--dry-run : Periksa data dan tampilkan rencana tanpa menyimpan}
        {--force : Jalankan tanpa konfirmasi}';

    protected $description = 'Pasang paket soal (PG, esai, ujian praktik) sebagai ujian baru per skema tanpa import';

    private string $versi;

    private string $dir;

    private string $templates;

    public function handle(): int
    {
        $this->versi = Str::lower($this->argument('versi'));
        $this->dir = database_path("data/paket-soal/{$this->versi}");
        $this->templates = rtrim($this->option('templates') ?: storage_path("app/paket-soal/{$this->versi}/templates"), '/\\');

        if (!is_dir($this->dir)) {
            $this->error("Paket {$this->versi} tidak ditemukan ({$this->dir}).");

            return self::FAILURE;
        }

        $paket = $this->load();
        if ($paket === null) {
            return self::FAILURE;
        }

        $problems = [];
        $rows = [];
        foreach ($paket as $key => $data) {
            foreach ($this->validate($data) as $p) {
                $problems[] = "{$key}: {$p}";
            }
            $rows[] = $this->planRow($key, $data);
        }

        $this->info('Paket Soal ' . $this->label() . " — template praktik dari: {$this->templates}");
        $this->table(['Skema', 'Kelas', 'Pilihan Ganda', 'Esai', 'Ujian Praktik'], $rows);

        if (!$this->praktikReady()) {
            $this->warn('Fitur Ujian Praktik belum terpasang di database ini (migrasi add_ujian_praktik_exam_type) — bagian praktik dilewati.');
        }

        if ($problems) {
            $this->error('Data paket bermasalah, tidak ada yang dipasang:');
            foreach ($problems as $p) {
                $this->line("  - {$p}");
            }

            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            $this->info('Dry run — tidak ada yang disimpan. Data paket lolos pemeriksaan.');

            return self::SUCCESS;
        }

        if (!$this->option('force') && !$this->confirm('Pasang ujian di atas?', true)) {
            return self::SUCCESS;
        }

        foreach ($paket as $key => $data) {
            $this->install($key, $data);
        }

        $this->newLine();
        $this->info('Selesai. Pilih ujian ' . $this->label() . ' di Sesi Ujian (Ujian PG / Esai / Ujian Praktik); sesi lama tidak berubah.');
        $this->line('Skema tanpa ujian esai: kosongkan Ujian Esai di sesi — bobot esai otomatis dibagi ke PG & wawancara.');

        return self::SUCCESS;
    }

    /** @return array<string, array>|null */
    private function load(): ?array
    {
        $only = array_map('strtolower', $this->option('skema'));
        $paket = [];

        foreach (glob("{$this->dir}/*.json") as $file) {
            $key = pathinfo($file, PATHINFO_FILENAME);
            if ($only && !in_array($key, $only, true)) {
                continue;
            }

            try {
                $paket[$key] = json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                $this->error("{$key}.json tidak valid: {$e->getMessage()}");

                return null;
            }
        }

        if (!$paket) {
            $this->error('Tidak ada skema yang cocok' . ($only ? ' dengan --skema=' . implode(',', $only) : '') . '.');

            return null;
        }

        return $paket;
    }

    /** @return string[] */
    private function validate(array $data): array
    {
        $problems = [];

        if (empty($data['classroom_code'])) {
            $problems[] = 'classroom_code kosong';
        }

        $pg = $data['pilihan_ganda'] ?? [];
        if (!$pg) {
            $problems[] = 'tidak ada soal pilihan ganda';
        }
        $seen = [];
        foreach ($pg as $q) {
            $no = $q['no'] ?? '?';
            if (isset($seen[$no])) {
                $problems[] = "PG soal {$no} dobel";
            }
            $seen[$no] = true;

            $options = $q['options'] ?? [];
            if (count($options) < 2 || count($options) > 5 || in_array('', array_map('trim', $options), true)) {
                $problems[] = "PG soal {$no}: opsi harus 2–5 dan tidak kosong";
            }
            $answer = $q['answer'] ?? null;
            if (!is_int($answer) || $answer < 1 || $answer > count($options)) {
                $problems[] = "PG soal {$no}: kunci tidak valid";
            }
            $blocks = $q['blocks'] ?? [];
            if (!collect($blocks)->contains(fn ($b) => trim($b['text'] ?? '') !== '')) {
                $problems[] = "PG soal {$no}: teks soal kosong";
            }
            foreach ($blocks as $b) {
                if (isset($b['image']) && !is_file($this->imagePath($b['image']))) {
                    $problems[] = "PG soal {$no}: gambar {$b['image']} tidak ada";
                }
            }
        }

        foreach ($data['esai'] ?? [] as $e) {
            if (trim(strip_tags($e['question'] ?? '')) === '') {
                $problems[] = 'esai ' . ($e['no'] ?? '?') . ': soal kosong';
            }
        }

        if ($praktik = $data['praktik'] ?? null) {
            if (trim($praktik['panduan'] ?? '') === '' || mb_strlen($praktik['panduan']) > 20000) {
                $problems[] = 'praktik: panduan kosong atau lebih dari 20.000 karakter';
            }
            $ext = strtolower(pathinfo($praktik['template_name'] ?? '', PATHINFO_EXTENSION));
            if (!in_array($ext, AnswerFile::EXTENSIONS, true)) {
                $problems[] = "praktik: tipe template .{$ext} tidak diizinkan";
            }
            $duration = $praktik['duration'] ?? null;
            if (!is_int($duration) || $duration < 0 || $duration > 1440) {
                $problems[] = 'praktik: durasi harus 0–1440 menit';
            }
        }

        return $problems;
    }

    private function planRow(string $key, array $data): array
    {
        $classroom = $this->classroom($data);
        $kelas = $classroom ? "{$classroom->classrooms_code} — {$classroom->title}" : "{$data['classroom_code']} — TIDAK DITEMUKAN";

        $pg = $data['pilihan_ganda'] ?? [];
        $images = collect($pg)->flatMap(fn ($q) => collect($q['blocks'])->pluck('image')->filter())->unique()->count();
        $pgCell = count($pg) . ' soal' . ($images ? ", {$images} gambar" : '') . ' → ' . $this->status($classroom, $key, 'pg', self::TYPE_PG);

        $esaiCell = !empty($data['esai'])
            ? count($data['esai']) . ' soal → ' . $this->status($classroom, $key, 'esai', self::TYPE_ESAI)
            : '— (tanpa esai)';

        $praktikCell = '—';
        if ($praktik = $data['praktik'] ?? null) {
            $file = $this->templateFile($praktik);
            $tpl = $file ? 'template ' . round(filesize($file) / 1048576, 1) . ' MB' : 'TEMPLATE BELUM ADA';
            $durasi = $praktik['duration'] ? "{$praktik['duration']} mnt" : 'tanpa batas waktu';
            $praktikCell = "{$tpl}, {$durasi} → " . ($this->praktikReady() ? $this->status($classroom, $key, 'praktik', null) : 'dilewati');
        }

        return [$key, $kelas, $pgCell, $esaiCell, $praktikCell];
    }

    private function status(?Classroom $classroom, string $key, string $part, ?string $type): string
    {
        if (!$classroom) {
            return 'dilewati';
        }

        $exam = Exam::where('exams_code', $this->code($key, $part))->first();
        if ($exam) {
            if ($part === 'praktik') {
                return $exam->praktik_template_path ? 'sudah ada' : 'sudah ada, template dilengkapi';
            }

            $n = $part === 'pg' ? $exam->questions()->count() : $exam->essays()->count();

            return $n ? "sudah ada ({$n} soal), dilewati" : 'sudah ada (kosong), soal diisi';
        }

        if ($type === null) {
            return 'baru';
        }

        [$settings, $from] = $this->settings($classroom, $type);

        return "baru ({$settings['duration']} mnt, acak soal {$settings['random_question']}; {$from})";
    }

    private function install(string $key, array $data): void
    {
        $classroom = $this->classroom($data);
        if (!$classroom) {
            $this->warn("{$key}: kelas {$data['classroom_code']} tidak ditemukan — dilewati.");

            return;
        }

        $this->copyImages($data);
        $label = $this->label();

        $this->installPart($key, 'pg', $classroom, self::TYPE_PG, "{$classroom->title} - Pilihan Ganda {$label}",
            'Paket Soal ' . $label . ' — FR.IA.05 Pilihan Ganda (' . count($data['pilihan_ganda']) . ' soal).',
            function (Exam $exam) use ($data) {
                foreach ($data['pilihan_ganda'] as $q) {
                    $options = array_pad(array_map(fn ($o) => e($o), $q['options']), 5, null);
                    Question::create([
                        'exam_id'  => $exam->id,
                        'question' => $this->questionHtml($q),
                        'option_1' => $options[0],
                        'option_2' => $options[1],
                        'option_3' => $options[2],
                        'option_4' => $options[3],
                        'option_5' => $options[4],
                        'answer'   => $q['answer'],
                    ]);
                }

                return count($data['pilihan_ganda']);
            });

        if (!empty($data['esai'])) {
            $isEssay = Schema::hasColumn('essays', 'is_essay');
            $this->installPart($key, 'esai', $classroom, self::TYPE_ESAI, "{$classroom->title} - Esai {$label}",
                'Paket Soal ' . $label . ' — FR.IA.06 Esai (' . count($data['esai']) . ' soal).',
                function (Exam $exam) use ($data, $isEssay) {
                    foreach ($data['esai'] as $e) {
                        Essay::create([
                            'exam_id'     => $exam->id,
                            'essays_code' => 'essy-' . Str::ulid(),
                            'question'    => $e['question'],
                            'answer'      => $e['answer'] ?? '',
                        ] + ($isEssay ? ['is_essay' => true] : []));
                    }

                    return count($data['esai']);
                });
        }

        if (!empty($data['praktik']) && $this->praktikReady()) {
            $this->installPraktik($key, $classroom, $data['praktik']);
        }
    }

    private function installPart(string $key, string $part, Classroom $classroom, string $type, string $title, string $description, \Closure $fill): void
    {
        $code = $this->code($key, $part);
        $exam = Exam::where('exams_code', $code)->first();
        $count = $exam ? ($part === 'pg' ? $exam->questions()->count() : $exam->essays()->count()) : 0;

        if ($exam && $count) {
            $this->line("{$key}: {$title} sudah ada ({$count} soal) — dilewati.");

            return;
        }

        DB::transaction(function () use (&$exam, $code, $classroom, $type, $title, $description, $fill, $key) {
            if (!$exam) {
                [$settings] = $this->settings($classroom, $type);
                $exam = Exam::create([
                    'exams_code'   => $code,
                    'title'        => $title,
                    'type'         => $type,
                    'classroom_id' => $classroom->id,
                    'description'  => $description,
                ] + $settings);
            }

            $n = $fill($exam);
            $this->info("{$key}: {$title} — {$n} soal dipasang (ujian #{$exam->id}).");
        });
    }

    private function installPraktik(string $key, Classroom $classroom, array $praktik): void
    {
        $code = $this->code($key, 'praktik');
        $title = "{$classroom->title} - Ujian Praktik {$this->label()}";
        $exam = Exam::where('exams_code', $code)->first();

        if (!$exam) {
            $exam = Exam::create([
                'exams_code'      => $code,
                'title'           => $title,
                'type'            => Exam::TYPE_PRAKTIK,
                'classroom_id'    => $classroom->id,
                'duration'        => $praktik['duration'],
                'description'     => 'Paket Soal ' . $this->label() . ' — FR.IA.04 Ujian Praktik.',
                'random_question' => 'N',
                'random_answer'   => 'N',
                'show_answer'     => 'N',
                'praktik_panduan' => $praktik['panduan'],
            ]);
            $this->info("{$key}: {$title} dibuat (ujian #{$exam->id}).");
        }

        if ($exam->praktik_template_path) {
            $this->line("{$key}: {$title} sudah punya template — dilewati.");

            return;
        }

        $file = $this->templateFile($praktik);
        if (!$file) {
            $this->warn("{$key}: template {$praktik['template']} tidak ada di {$this->templates} — unggah folder template lalu jalankan ulang perintah ini.");

            return;
        }

        $ext = strtolower(pathinfo($praktik['template_name'], PATHINFO_EXTENSION));
        $name = (Str::slug(pathinfo($praktik['template_name'], PATHINFO_FILENAME)) ?: 'template')
            . '-' . now()->format('YmdHis') . '-' . Str::random(6) . ".{$ext}";
        $path = Storage::disk(AnswerFile::DISK)->putFileAs("praktik_templates/{$exam->id}", new File($file), $name);

        $exam->praktik_template_path = $path;
        $exam->praktik_template_name = $praktik['template_name'];
        $exam->save();
        $this->info("{$key}: template {$praktik['template_name']} dipasang.");
    }

    private function questionHtml(array $q): string
    {
        return collect($q['blocks'])->map(function (array $b) use ($q) {
            if (isset($b['image'])) {
                $src = e("/storage/paket-soal/{$this->versi}/{$b['image']}");

                return "<p><img src=\"{$src}\" alt=\"Gambar soal {$q['no']}\" style=\"max-width:100%;height:auto\"></p>";
            }

            return '<p>' . e($b['text']) . '</p>';
        })->implode('');
    }

    /** Gambar soal disajikan dari disk public (/storage/paket-soal/{versi}/...). */
    private function copyImages(array $data): void
    {
        $disk = Storage::disk('public');
        foreach ($data['pilihan_ganda'] as $q) {
            foreach ($q['blocks'] as $b) {
                if (!isset($b['image'])) {
                    continue;
                }
                $target = "paket-soal/{$this->versi}/{$b['image']}";
                if (!$disk->exists($target)) {
                    $disk->put($target, file_get_contents($this->imagePath($b['image'])));
                }
            }
        }

        if (!is_dir(public_path('storage'))) {
            $this->warn('public/storage belum ada — jalankan php artisan storage:link supaya gambar soal tampil.');
        }
    }

    /** Pengaturan ujian = ujian sejenis terakhir di kelas itu (selain paket ini), atau bawaan. */
    private function settings(Classroom $classroom, string $type): array
    {
        $prev = Exam::where('classroom_id', $classroom->id)
            ->where('type', $type)
            ->where('exams_code', 'not like', "exms-{$this->versi}-%")
            ->latest('id')
            ->first();

        if (!$prev) {
            return [self::DEFAULTS[$type], 'bawaan'];
        }

        return [[
            'duration'        => (int) $prev->duration,
            'random_question' => $prev->random_question,
            'random_answer'   => $prev->random_answer,
            'show_answer'     => $prev->show_answer,
        ], "contoh ujian #{$prev->id}"];
    }

    private function classroom(array $data): ?Classroom
    {
        return Classroom::where('classrooms_code', $data['classroom_code'])->first();
    }

    private function code(string $key, string $part): string
    {
        return "exms-{$this->versi}-{$key}-{$part}";
    }

    private function label(): string
    {
        return Str::upper($this->versi);
    }

    private function imagePath(string $rel): string
    {
        return "{$this->dir}/images/{$rel}";
    }

    private function templateFile(array $praktik): ?string
    {
        $file = "{$this->templates}/{$praktik['template']}";

        return is_file($file) ? $file : null;
    }

    private function praktikReady(): bool
    {
        return defined(Exam::class . '::TYPE_PRAKTIK') && Schema::hasColumn('exams', 'praktik_template_path');
    }
}
