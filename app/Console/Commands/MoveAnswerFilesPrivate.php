<?php

namespace App\Console\Commands;

use App\Support\AnswerFile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MoveAnswerFilesPrivate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'answer-files:move-private {--dry-run : Hanya tampilkan file yang akan dipindah}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pindahkan file jawaban Essay Migas lama dari disk public (bisa diakses lewat /storage) ke disk private';

    private const DIRECTORY = 'essay_migas_answers';

    public function handle(): int
    {
        $public  = Storage::disk('public');
        $private = Storage::disk(AnswerFile::DISK);
        $files   = $public->allFiles(self::DIRECTORY);

        if (empty($files)) {
            $this->info('Tidak ada file di disk public — semua sudah private.');

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');
        $moved  = 0;

        foreach ($files as $path) {
            if ($dryRun) {
                $this->line($path);
                continue;
            }

            // Path relatif sama, jadi kolom answer_essays.answer tidak perlu diubah.
            $stream = $public->readStream($path);
            $ok     = $private->writeStream($path, $stream);

            if (is_resource($stream)) {
                fclose($stream);
            }

            if (! $ok) {
                $this->error("Gagal menyalin: {$path}");
                continue;
            }

            $public->delete($path);
            $moved++;
        }

        $dryRun
            ? $this->info(count($files) . ' file akan dipindah (dry run, belum ada yang berubah).')
            : $this->info("{$moved} dari " . count($files) . ' file dipindah ke disk private.');

        return self::SUCCESS;
    }
}
