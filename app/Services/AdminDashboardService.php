<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Asesor\DashboardController as AsesorDashboard;
use App\Models\AnswerEssay;
use App\Models\ApplicationDocument;
use App\Models\AsesorAssignment;
use App\Models\AssessmentApplication;
use App\Models\ExamSession;
use App\Models\Grade;
use App\Models\InterviewAssessment;
use App\Models\ParticipantResult;
use App\Models\TukVerification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Isi Dashboard admin: pekerjaan tertunda, sesi aktif & kesiapannya, pipeline sertifikasi sesi
 * yang sudah selesai, statistik, dan aktivitas terbaru. Hanya membaca.
 *
 * Pipeline & hitungan "perlu tindakan" hanya untuk sesi ID ≥ TRACK_FROM_SESSION_ID (sama dengan
 * batas progres dashboard asesor) — sesi lama dari sebelum alur dua tahap tidak pernah lengkap
 * centangnya dan hanya akan jadi derau.
 */
class AdminDashboardService
{
    public const TRACK_FROM_SESSION_ID = AsesorDashboard::PROGRESS_FROM_SESSION_ID;

    /** Batas sesi yang ditampilkan di tabel sesi aktif / pipeline. */
    private const LIST_LIMIT = 10;

    public function build(): array
    {
        $active   = $this->activeSessions();
        $pipeline = $this->pipelineSessions();

        return [
            'actions'        => $this->actions($active, $pipeline),
            'activeSessions' => $active->take(self::LIST_LIMIT)->values(),
            'pipeline'       => $pipeline->take(self::LIST_LIMIT)->values(),
            'pipelineTotal'  => $pipeline->count(),
            'materai'        => $this->materai(),
            'stats'          => $this->monthlyStats(),
            'monthly'        => $this->participantsPerMonth(),
            'passRates'      => $this->passRatesPerSkema(),
            'activities'     => $this->recentActivities(),
            'totals'         => [
                'classrooms'    => \App\Models\Classroom::count(),
                'students'      => \App\Models\Student::where('is_active', true)->count(),
                'exams'         => \App\Models\Exam::count(),
                'exam_sessions' => ExamSession::count(),
            ],
        ];
    }

    /** Sesi yang belum berakhir (berlangsung / terjadwal), mulai paling awal di atas. */
    private function activeSessions(): Collection
    {
        return $this->baseQuery()
            ->active()
            ->orderBy('start_time')
            ->get()->toBase()
            ->map(fn (ExamSession $s) => $this->sessionRow($s, now()->gte($s->start_time) ? 'berlangsung' : 'terjadwal'));
    }

    /**
     * Sesi yang sudah berakhir tapi SK & Sertifikatnya belum terkirim ke semua peserta —
     * yang terbaru di atas.
     */
    private function pipelineSessions(): Collection
    {
        return $this->baseQuery()
            ->where('id', '>=', self::TRACK_FROM_SESSION_ID)
            ->where('end_time', '<=', now())
            ->withCount(['participantResults as sent_count' => fn ($q) => $q->enrolled()->whereNotNull('distributed_at')])
            ->orderByDesc('end_time')
            ->get()->toBase()
            ->filter(fn (ExamSession $s) => $s->students_count > 0 && $s->sent_count < $s->students_count)
            ->map(fn (ExamSession $s) => $this->sessionRow($s, 'selesai'))
            ->values();
    }

    private function baseQuery()
    {
        return ExamSession::query()
            ->with(['examPg.classroom:id,title', 'examEsai.classroom:id,title', 'pengambilKeputusan:id,name'])
            ->withActiveStudentsCount();
    }

    /** Ringkasan satu sesi + progres per tahap (jumlah peserta). */
    private function sessionRow(ExamSession $s, string $state): array
    {
        $ids      = ExamSession::activeStudentIds($s->id);
        $written  = $s->exam_id_pg || $s->exam_id_esai;
        $needsAsesor = (bool) ($s->exam_id_esai || $s->has_wawancara);

        // Sesi tanpa ujian tertulis (hanya wawancara): semua peserta dianggap ikut.
        $started = $written
            ? Grade::where('exam_session_id', $s->id)->whereIn('student_id', $ids)
                ->whereNotNull('start_time')->distinct()->pluck('student_id')
            : $ids;

        $finished = $written
            ? Grade::where('exam_session_id', $s->id)->whereIn('student_id', $ids)
                ->whereNotNull('end_time')->distinct()->pluck('student_id')->count()
            : $ids->count();

        // Belum dinilai: masih ada jawaban esai tanpa nilai, atau belum punya nilai wawancara.
        $unassessed = collect();
        if ($s->exam_id_esai) {
            $unassessed = $unassessed->merge(AnswerEssay::where('exam_session_id', $s->id)
                ->where('exam_id', $s->exam_id_esai)
                ->whereIn('student_id', $started)
                ->whereNull('score')
                ->distinct()
                ->pluck('student_id'));
        }
        if ($s->has_wawancara) {
            $interviewed = InterviewAssessment::where('exam_session_id', $s->id)
                ->whereIn('student_id', $started)
                ->whereNotNull('total_nilai')
                ->pluck('student_id');
            $unassessed = $unassessed->merge($started->diff($interviewed));
        }

        $results = ParticipantResult::where('exam_session_id', $s->id)->enrolled()
            ->selectRaw('SUM(CASE WHEN manager_verified_at IS NOT NULL THEN 1 ELSE 0 END) AS pk')
            ->selectRaw('SUM(CASE WHEN cert_manager_verified_at IS NOT NULL THEN 1 ELSE 0 END) AS ms')
            ->selectRaw('SUM(CASE WHEN is_finalized = 1 THEN 1 ELSE 0 END) AS final')
            ->selectRaw('SUM(CASE WHEN sp_distributed_at IS NOT NULL THEN 1 ELSE 0 END) AS sp')
            ->selectRaw('SUM(CASE WHEN distributed_at IS NOT NULL THEN 1 ELSE 0 END) AS sent')
            ->first();

        $tuk = $s->verifikasi_tuk
            ? TukVerification::where('exam_session_id', $s->id)->whereIn('student_id', $ids)
                ->whereNotNull('verified_at')->count()
            : null;

        $classroom = $s->examPg?->classroom ?? $s->examEsai?->classroom;

        return [
            'id'           => $s->id,
            'title'        => $s->title,
            'kode_batch'   => $s->kode_batch,
            'skema'        => $classroom?->title,
            'start_time'   => $s->start_time,
            'end_time'     => $s->end_time,
            'state'        => $state,
            'total'        => (int) $s->students_count,
            'needs_asesor' => $needsAsesor,
            'has_esai'     => (bool) $s->exam_id_esai,
            'has_wawancara'=> (bool) $s->has_wawancara,
            'pk_name'      => $s->pengambilKeputusan?->name,
            'assigned'     => AsesorAssignment::where('exam_session_id', $s->id)->whereIn('student_id', $ids)
                ->distinct()->count('student_id'),
            'tuk'          => $tuk,
            'progress'     => [
                'started'  => $started->count(),
                'finished' => $finished,
                'assessed' => $started->count() - $unassessed->unique()->count(),
                'pk'       => (int) ($results->pk ?? 0),
                'ms'       => (int) ($results->ms ?? 0),
                'final'    => (int) ($results->final ?? 0),
                'sp'       => (int) ($results->sp ?? 0),
                'sent'     => (int) ($results->sent ?? 0),
            ],
        ];
    }

    /** Kartu "Perlu Tindakan" — angka + rincian, dihitung dari sesi yang sudah dimuat. */
    private function actions(Collection $active, Collection $pipeline): array
    {
        $tracked = $active->filter(fn ($s) => $s['id'] >= self::TRACK_FROM_SESSION_ID)->merge($pipeline);

        $noAsesor = $tracked->filter(fn ($s) => $s['needs_asesor'] && $s['total'] > 0 && $s['assigned'] < $s['total'])->count();
        $noPk     = $tracked->filter(fn ($s) => $s['total'] > 0 && !$s['pk_name'])->count();

        $sum = fn (callable $f) => $pipeline->sum(fn ($s) => max(0, $f($s['progress'], $s['total'])));

        return [
            'applications'   => AssessmentApplication::where('status', ApplicationStatus::Submitted)->count(),
            'documents'      => ApplicationDocument::where('status', 'pending')
                ->whereHas('application', fn ($q) => $q->where('status', ApplicationStatus::Submitted))
                ->count(),
            'assignment'     => ['no_asesor' => $noAsesor, 'no_pk' => $noPk],
            'assessment'     => $sum(fn ($p) => $p['started'] - $p['assessed']),
            'pk'             => $sum(fn ($p, $n) => $n - $p['pk']),
            'ms'             => $sum(fn ($p) => $p['pk'] - $p['final']),
            'send'           => $sum(fn ($p) => $p['final'] - $p['sent']),
            'materai_failed' => config('materai.enabled') ? $this->materaiCount(['failed']) : 0,
        ];
    }

    private function materai(): ?array
    {
        if (!config('materai.enabled')) {
            return null;
        }

        $monthStart = now()->startOfMonth();
        $stampedThisMonth = ParticipantResult::where('materai_status', 'stamped')->where('materai_stamped_at', '>=', $monthStart)->count()
            + AssessmentApplication::where('materai_status', 'stamped')->where('materai_stamped_at', '>=', $monthStart)->count();

        return [
            'failed'      => $this->materaiCount(['failed']),
            'proses'      => $this->materaiCount(['processing', 'pending_payment', 'paid']),
            'stamped_mtd' => $stampedThisMonth,
        ];
    }

    private function materaiCount(array $statuses): int
    {
        return ParticipantResult::whereIn('materai_status', $statuses)->count()
            + AssessmentApplication::whereIn('materai_status', $statuses)->count();
    }

    /** Bulan ini vs bulan lalu (sampai hari ini). */
    private function monthlyStats(): array
    {
        $ranges = [
            'current'  => [now()->startOfMonth(), now()],
            'previous' => [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()],
        ];

        $count = fn (array $r) => [
            'applications' => AssessmentApplication::whereBetween('submitted_at', $r)->count(),
            'tested'       => Grade::whereBetween('start_time', $r)->distinct()->count('student_id'),
            'certified'    => ParticipantResult::whereBetween('distributed_at', $r)->where('keputusan', 'LULUS')->count(),
        ];

        return array_map($count, $ranges);
    }

    /**
     * Jumlah peserta per bulan (12 bulan terakhir), menurut bulan mulai sesi. Dikelompokkan di PHP
     * supaya tidak bergantung fungsi tanggal MySQL/SQLite.
     */
    private function participantsPerMonth(): array
    {
        $first = now()->startOfMonth()->subMonthsNoOverflow(11);

        $perMonth = ExamSession::withActiveStudentsCount()
            ->where('start_time', '>=', $first->toDateTimeString())
            ->get(['id', 'start_time'])
            ->groupBy(fn ($s) => substr((string) $s->start_time, 0, 7))
            ->map(fn ($rows) => $rows->sum('students_count'));

        return collect(range(0, 11))->map(function ($i) use ($first, $perMonth) {
            $month = $first->copy()->addMonthsNoOverflow($i);

            return [
                'month' => $month->format('Y-m'),
                'label' => $month->locale('id')->translatedFormat('M y'),
                'count' => (int) ($perMonth[$month->format('Y-m')] ?? 0),
            ];
        })->all();
    }

    /** Kelulusan per skema dari hasil yang difinalisasi dalam 12 bulan terakhir. */
    private function passRatesPerSkema(): array
    {
        return ParticipantResult::query()
            ->enrolled()
            ->join('students as st', 'st.id', '=', 'participant_results.student_id')
            ->join('classrooms as c', 'c.id', '=', 'st.classroom_id')
            ->where('participant_results.is_finalized', true)
            ->where('participant_results.finalized_at', '>=', now()->subYear())
            ->groupBy('c.id', 'c.title')
            ->selectRaw('c.title AS skema, COUNT(*) AS total')
            ->selectRaw("SUM(CASE WHEN participant_results.keputusan = 'LULUS' THEN 1 ELSE 0 END) AS lulus")
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn ($r) => [
                'skema' => $r->skema,
                'total' => (int) $r->total,
                'lulus' => (int) $r->lulus,
                'rate'  => $r->total > 0 ? round($r->lulus / $r->total * 100, 1) : 0,
            ])
            ->all();
    }

    /** 10 kejadian terakhir: permohonan masuk/disetujui, finalisasi sesi, SK & Sertifikat terkirim. */
    private function recentActivities(): array
    {
        $applications = fn (string $column, string $type) => AssessmentApplication::query()
            ->with(['participant:id,name', 'classroom:id,title'])
            ->whereNotNull($column)
            ->latest($column)
            ->limit(10)
            ->get(['id', 'participant_id', 'classroom_id', $column])
            ->map(fn ($a) => [
                'type'  => $type,
                'at'    => $a->{$column}?->toIso8601String(),
                'title' => $a->participant?->name ?? 'Peserta',
                'meta'  => $a->classroom?->title,
                'url'   => "/admin/applications/{$a->id}",
            ]);

        $perSession = function (string $column, string $type) {
            $rows = ParticipantResult::query()
                ->whereNotNull($column)
                ->groupBy('exam_session_id')
                ->selectRaw("exam_session_id, MAX({$column}) AS at, COUNT(*) AS n")
                ->orderByDesc('at')
                ->limit(10)
                ->get();

            $titles = ExamSession::whereIn('id', $rows->pluck('exam_session_id'))->pluck('title', 'id');

            return $rows->map(fn ($r) => [
                'type'  => $type,
                'at'    => Carbon::parse($r->at)->toIso8601String(),
                'title' => $titles[$r->exam_session_id] ?? "Sesi #{$r->exam_session_id}",
                'meta'  => "{$r->n} peserta",
                'url'   => "/admin/results/{$r->exam_session_id}",
            ]);
        };

        return collect()
            ->merge($applications('submitted_at', 'submitted'))
            ->merge($applications('approved_at', 'approved'))
            ->merge($perSession('finalized_at', 'finalized'))
            ->merge($perSession('distributed_at', 'distributed'))
            ->sortByDesc('at')
            ->take(10)
            ->values()
            ->all();
    }
}
