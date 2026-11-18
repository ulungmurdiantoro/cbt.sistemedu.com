<?php

namespace App\Console\Commands;

use App\Enums\ApplicationStatus;
use App\Models\InitialAssessment;
use App\Support\InitialAssessmentRubric;
use Illuminate\Console\Command;

/**
 * Hitung ulang skor Penilaian Awal Kelayakan (FR.APL.03) yang tersimpan dengan rubrik revisi
 * terbaru, setelah salah salin skor di kelas revisi itu dibetulkan (mis. Prodi Corporate Legal
 * Sosial/Sains yang sempat tertukar). Jawaban tidak diubah — hanya total_score, threshold dan
 * is_eligible. Penilaian revisi lama (Rev.03) tidak disentuh. Status permohonan juga tidak
 * diubah: permohonan yang sudah disetujui tapi kini tidak layak hanya dilaporkan.
 */
class RescoreInitialAssessments extends Command
{
    protected $signature = 'initial-assessment:rescore
        {--classroom=* : Hanya classroom_id ini (boleh beberapa), mis. --classroom=28}
        {--dry-run : Tampilkan perubahan tanpa menyimpan}
        {--force : Jalankan tanpa konfirmasi}';

    protected $description = 'Hitung ulang skor FR.APL.03 tersimpan (revisi terbaru) sesuai rubrik saat ini';

    public function handle(): int
    {
        $classrooms = array_map('intval', (array) $this->option('classroom'));

        $assessments = InitialAssessment::with('application.participant:id,name')
            ->where('revision', InitialAssessmentRubric::CURRENT)
            ->when($classrooms, fn ($q) => $q->whereIn('classroom_id', $classrooms))
            ->orderBy('id')
            ->get();

        $changes = [];
        foreach ($assessments as $a) {
            $rubric   = $a->rubric();
            $score    = InitialAssessmentRubric::score($rubric, $a->answers ?? []);
            $eligible = $score >= $rubric['threshold'];

            if ($score === (int) $a->total_score && $eligible === (bool) $a->is_eligible && (int) $rubric['threshold'] === (int) $a->threshold) {
                continue;
            }

            $changes[] = [$a, $score, $rubric['threshold'], $eligible];
        }

        $this->info(sprintf('%d penilaian %s diperiksa, %d berubah.', $assessments->count(), InitialAssessmentRubric::CURRENT, count($changes)));
        if (!$changes) {
            return self::SUCCESS;
        }

        $label = fn (bool $v) => $v ? 'Layak' : 'Tidak layak';
        $this->table(
            ['Permohonan', 'Peserta', 'Kelas', 'Skor', 'Kelayakan', 'Status permohonan'],
            array_map(fn ($c) => [
                $c[0]->application?->code ?? "#{$c[0]->assessment_application_id}",
                $c[0]->application?->participant?->name ?? '-',
                $c[0]->classroom_id,
                "{$c[0]->total_score} → {$c[1]}",
                $c[3] === (bool) $c[0]->is_eligible ? $label($c[3]) : $label((bool) $c[0]->is_eligible) . ' → ' . $label($c[3]),
                $c[0]->application?->status?->value ?? '-',
            ], $changes)
        );

        $flipped = array_filter($changes, fn ($c) => !$c[3] && $c[0]->is_eligible && $c[0]->application?->status === ApplicationStatus::Approved);
        if ($flipped) {
            $this->warn(count($flipped) . ' permohonan sudah DISETUJUI tetapi kini tidak layak. Status permohonannya tidak diubah — tinjau manual.');
        }

        if ($this->option('dry-run')) {
            $this->warn('Dry run — tidak ada yang diubah.');

            return self::SUCCESS;
        }

        if (!$this->option('force') && !$this->confirm('Simpan skor baru di atas?')) {
            return self::FAILURE;
        }

        foreach ($changes as [$a, $score, $threshold, $eligible]) {
            $a->forceFill(['total_score' => $score, 'threshold' => $threshold, 'is_eligible' => $eligible])->save();
        }
        $this->info(count($changes) . ' penilaian diperbarui.');

        return self::SUCCESS;
    }
}
