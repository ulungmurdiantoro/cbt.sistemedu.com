<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Audit nomor SP & SK (read-only): cari nomor urut yang hilang/ganda dibanding
 * numbering_counters, dan tunjukkan sesi mana saja yang memakai tiap rentang nomor.
 * Penomoran bersifat global per tahun (lintas sesi), jadi nomor satu sesi wajar
 * "meloncat" kalau ada sesi lain yang difinalisasi di sela-selanya.
 */
class AuditNumbering extends Command
{
    protected $signature = 'numbering:audit
        {--type=* : sp dan/atau sk (default keduanya)}
        {--year= : Batasi ke satu tahun}
        {--list : Tampilkan seluruh nomor satu per satu}';

    protected $description = 'Audit nomor SP & SK: nomor hilang, ganda, dan sebarannya per sesi (tidak mengubah data)';

    private const COLUMNS = ['sp' => 'sp_number', 'sk' => 'sk_number'];

    public function handle(): int
    {
        $types = $this->option('type') ?: array_keys(self::COLUMNS);
        $year  = $this->option('year');

        foreach ($types as $type) {
            if (!isset(self::COLUMNS[$type])) {
                $this->error("Tipe tidak dikenal: {$type} (pakai sp atau sk)");

                return self::FAILURE;
            }
        }

        $rows = DB::table('participant_results as pr')
            ->leftJoin('students as s', 's.id', '=', 'pr.student_id')
            ->leftJoin('exam_sessions as es', 'es.id', '=', 'pr.exam_session_id')
            ->where(fn ($q) => $q->whereNotNull('pr.sp_number')->orWhereNotNull('pr.sk_number'))
            ->get([
                'pr.id', 'pr.exam_session_id', 'es.title as session_title', 's.no_participant', 's.name',
                's.is_active', 'pr.sp_number', 'pr.sk_number', 'pr.is_finalized', 'pr.finalized_at',
                'pr.attempt', 'pr.keputusan',
            ]);

        foreach ($types as $type) {
            $this->auditType($type, self::COLUMNS[$type], $rows, $year);
        }

        $this->auditAnomalies($rows);

        return self::SUCCESS;
    }

    private function auditType(string $type, string $column, Collection $rows, ?string $onlyYear): void
    {
        // "017/SPT/EDUKIA/IX/2026" → seq 17, tahun 2026
        $parsed = $rows->filter(fn ($r) => $r->{$column})->map(function ($r) use ($column) {
            preg_match('/^(\d+)\//', $r->{$column}, $seq);
            preg_match('/(\d{4})$/', $r->{$column}, $yr);
            $r->seq  = isset($seq[1]) ? (int) $seq[1] : null;
            $r->year = $yr[1] ?? '?';

            return $r;
        });

        $counters = DB::table('numbering_counters')->where('type', $type)->whereNull('scope')
            ->pluck('last_number', 'year');

        $years = $parsed->pluck('year')->merge($counters->keys())->unique()->sort()
            ->when($onlyYear, fn ($c) => $c->filter(fn ($y) => (string) $y === (string) $onlyYear));

        foreach ($years as $year) {
            $inYear  = $parsed->where('year', (string) $year)->sortBy([['seq', 'asc'], ['finalized_at', 'asc']])->values();
            $counter = $counters[$year] ?? null;
            $maxUsed = (int) $inYear->max('seq');
            $upper   = max($maxUsed, (int) $counter);
            $used    = $inYear->pluck('seq')->filter()->unique();
            $missing = collect(range(1, max($upper, 1)))->diff($used)->values();
            if ($upper === 0) {
                $missing = collect();
            }

            $this->newLine();
            $this->line('<options=bold>== ' . strtoupper($type) . " {$year} ==</>");
            $this->line('  Counter (numbering_counters.last_number): ' . ($counter ?? 'tidak ada'));
            $this->line("  Nomor tersimpan di participant_results  : {$inYear->count()} (tertinggi {$maxUsed})");

            if ((int) $counter < $maxUsed) {
                $this->error('  Counter (' . ($counter ?? 'tidak ada') . ") LEBIH KECIL dari nomor tertinggi ({$maxUsed}) — nomor berikutnya bisa bentrok!");
            }

            if ($missing->isEmpty()) {
                $this->info('  Tidak ada nomor yang hilang.');
            } else {
                $this->warn("  {$missing->count()} nomor tidak dipakai siapa pun: " . $this->ranges($missing));
                foreach ($this->gapRanges($missing) as [$from, $to]) {
                    $before = $inYear->where('seq', '<', $from)->last();
                    $after  = $inYear->firstWhere(fn ($r) => $r->seq > $to);
                    $label  = $from === $to ? sprintf('%03d', $from) : sprintf('%03d–%03d', $from, $to);
                    $this->line("    {$label}: sesudah " . $this->describe($before) . ' | sebelum ' . $this->describe($after));
                }
            }

            $dupes = $inYear->groupBy('seq')->filter(fn ($g) => $g->count() > 1);
            foreach ($dupes as $seq => $group) {
                $this->error(sprintf('  Nomor urut %03d dipakai %d kali:', $seq, $group->count()));
                foreach ($group as $r) {
                    $this->line('    ' . $r->{$column} . ' — ' . $this->describe($r));
                }
            }

            $this->line('  Sebaran per sesi (urut nomor pertama):');
            $inYear->groupBy('exam_session_id')
                ->sortBy(fn ($g) => $g->min('seq'))
                ->each(function ($g, $sessionId) {
                    $this->line(sprintf(
                        '    Sesi #%s %s — %d nomor: %s',
                        $sessionId,
                        $g->first()->session_title ?? '(sesi terhapus)',
                        $g->count(),
                        $this->ranges($g->pluck('seq')->filter())
                    ));
                });

            if ($this->option('list')) {
                $this->table(
                    ['No', 'Nomor', 'Sesi', 'No Peserta', 'Nama', 'Aktif', 'Final', 'Difinalisasi', 'Attempt', 'Keputusan'],
                    $inYear->map(fn ($r) => [
                        sprintf('%03d', $r->seq), $r->{$column}, $r->exam_session_id, $r->no_participant,
                        $r->name, $r->is_active ? 'ya' : 'TIDAK', $r->is_finalized ? 'ya' : 'TIDAK',
                        $r->finalized_at, $r->attempt, $r->keputusan,
                    ])->all()
                );
            }
        }
    }

    /** Baris yang kemungkinan besar terkait nomor loncat/bermasalah. */
    private function auditAnomalies(Collection $rows): void
    {
        $checks = [
            'Punya nomor tapi belum/tidak lagi final (remidi berjalan?)' => $rows->filter(fn ($r) => !$r->is_finalized),
            'Remidi (attempt 2) — cek nomor lama tidak hangus' => $rows->filter(fn ($r) => $r->attempt >= 2),
            'Nomor mengandung spasi (rapikan: numbering:normalize)' => $rows->filter(fn ($r) => preg_match('/\s/', $r->sp_number . $r->sk_number)),
            'Akun peserta nonaktif (re-issue) tapi memegang nomor' => $rows->filter(fn ($r) => !$r->is_active),
            'Final tapi SP kosong' => $rows->filter(fn ($r) => $r->is_finalized && !$r->sp_number),
            'Final tapi SK kosong' => $rows->filter(fn ($r) => $r->is_finalized && !$r->sk_number),
        ];

        $this->newLine();
        $this->line('<options=bold>== Pemeriksaan tambahan ==</>');
        foreach ($checks as $label => $found) {
            if ($found->isEmpty()) {
                $this->line("  {$label}: -");
                continue;
            }
            $this->warn("  {$label}: {$found->count()}");
            foreach ($found as $r) {
                $this->line('    SP ' . ($r->sp_number ?? '-') . ' | SK ' . ($r->sk_number ?? '-') . ' — ' . $this->describe($r));
            }
        }
    }

    private function describe(?object $r): string
    {
        if (!$r) {
            return '(tidak ada)';
        }

        return sprintf(
            '%s %s, sesi #%s, final %s',
            isset($r->seq) ? sprintf('[%03d]', $r->seq) : '',
            $r->name ?? '(peserta terhapus)',
            $r->exam_session_id,
            $r->finalized_at ?? '-'
        );
    }

    /** [[1,3],[7,7]] dari 1,2,3,7 */
    private function gapRanges(Collection $numbers): array
    {
        $ranges = [];
        foreach ($numbers->sort()->values() as $n) {
            if ($ranges && end($ranges)[1] === $n - 1) {
                $ranges[count($ranges) - 1][1] = $n;
            } else {
                $ranges[] = [$n, $n];
            }
        }

        return $ranges;
    }

    private function ranges(Collection $numbers): string
    {
        return collect($this->gapRanges($numbers->unique()))
            ->map(fn ($r) => $r[0] === $r[1] ? sprintf('%03d', $r[0]) : sprintf('%03d–%03d', $r[0], $r[1]))
            ->implode(', ');
    }
}
