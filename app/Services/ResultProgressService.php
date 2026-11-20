<?php

namespace App\Services;

use App\Models\AnswerEssay;
use App\Models\AsesmenReport;
use App\Models\AsesorAssignment;
use App\Models\AssessmentApplication;
use App\Models\ExamSession;
use App\Models\Grade;
use App\Models\InterviewAssessment;
use App\Models\ParticipantResult;
use App\Models\TukVerification;
use App\Models\User;
use App\Support\TukChecklist;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Progres tiap peserta setelah ujian untuk Rekap Hasil (admin.results.show): tahap demi tahap dari
 * TTD AK.01 sampai SK & Sertifikat terkirim, siapa yang mengerjakan & kapan, tahap yang sedang
 * ditunggu, dan riwayat (timeline). Hanya membaca data yang sudah tersimpan — tidak ada tabel baru.
 *
 * State per tahap:
 *  - done     : selesai
 *  - pending  : belum, dan memang ini/akan jadi gilirannya
 *  - progress : sedang berjalan (mis. sudah mulai ujian tapi belum selesai)
 *  - absent   : tidak ikut ujian (sesi sudah berakhir)
 *  - skipped  : belum tercatat padahal tahap sesudahnya sudah selesai (mis. sesi lama sebelum fitur ada)
 *  - na       : tidak berlaku untuk peserta/sesi ini
 */
class ResultProgressService
{
    /** Urutan tahap + label pendek (ikon progres) + kalimat "Menunggu …". */
    public const STEPS = [
        'ak01'   => ['label' => 'AK.01',   'title' => 'TTD AK.01 asesor',            'waiting' => 'Menunggu TTD AK.01 asesor'],
        'tuk'    => ['label' => 'TUK',     'title' => 'Verifikasi TUK (FR.TUK.06)',  'waiting' => 'Menunggu verifikasi TUK'],
        'ujian'  => ['label' => 'Ujian',   'title' => 'Mengerjakan ujian',           'waiting' => 'Belum mulai ujian'],
        'nilai'  => ['label' => 'Nilai',   'title' => 'Dinilai asesor',              'waiting' => 'Menunggu nilai asesor'],
        'kbk'    => ['label' => 'K/BK',    'title' => 'Rekomendasi K/BK asesor',     'waiting' => 'Menunggu rekomendasi K/BK asesor'],
        'pk'     => ['label' => 'PK',      'title' => 'Verifikasi Pengambil Keputusan', 'waiting' => 'Menunggu verifikasi Pengambil Keputusan'],
        'ms'     => ['label' => 'MS',      'title' => 'Verifikasi Manajer Sertifikasi', 'waiting' => 'Menunggu verifikasi Manajer Sertifikasi'],
        'final'  => ['label' => 'Final',   'title' => 'Finalisasi',                  'waiting' => 'Menunggu finalisasi Manajer Sertifikasi'],
        'sp'     => ['label' => 'SP',      'title' => 'SP terkirim',                 'waiting' => 'Siap kirim SP'],
        'sk'     => ['label' => 'SK',      'title' => 'SK & Sertifikat terkirim',    'waiting' => 'Siap kirim SK & Sertifikat'],
    ];

    /** Siapa yang bertindak di tahap itu — ditampilkan di belakang kalimat "Menunggu …". */
    private const ACTOR = [
        'ak01' => 'asesor', 'nilai' => 'asesor', 'kbk' => 'asesor',
        'tuk' => 'admin', 'sp' => 'admin', 'sk' => 'admin',
        'ujian' => 'peserta', 'pk' => 'pk', 'ms' => 'ms', 'final' => 'ms',
    ];

    /**
     * @param  Collection<int, ParticipantResult>  $results  baris Rekap Hasil sesi ini
     * @return Collection<int, array>  per student_id: steps, stage, waiting, absent, timeline, docs
     */
    public function forSession(ExamSession $session, Collection $results): Collection
    {
        $ids       = $results->pluck('student_id')->values();
        $examNames = [];   // exam_id => nama di timeline
        if ($session->exam_id_pg) {
            $examNames[$session->exam_id_pg] = 'PG';
        }
        if ($session->exam_id_esai) {
            $examNames[$session->exam_id_esai] = 'Esai';
        }
        $written = array_keys($examNames);
        $ended     = $session->end_time && Carbon::parse($session->end_time)->isPast();

        $applications = AssessmentApplication::where('exam_session_id', $session->id)
            ->whereIn('student_id', $ids)
            ->get(['id', 'student_id', 'asesor_verified_at', 'asesor_signature_name', 'asesor_rekomendasi'])
            ->keyBy('student_id');

        $tuk = $session->verifikasi_tuk
            ? TukVerification::where('exam_session_id', $session->id)->whereIn('student_id', $ids)
                ->get(['student_id', 'revision', 'verified_at', 'kesimpulan_awal', 'kesimpulan_akhir', 'pengawas_name'])
                ->keyBy('student_id')
            : null;

        $grades = $written
            ? Grade::where('exam_session_id', $session->id)->whereIn('student_id', $ids)
                ->whereIn('exam_id', $written)
                ->get(['student_id', 'exam_id', 'start_time', 'end_time'])
                ->groupBy('student_id')
            : collect();

        $essays = $session->exam_id_esai
            ? AnswerEssay::where('exam_session_id', $session->id)->where('exam_id', $session->exam_id_esai)
                ->whereIn('student_id', $ids)
                ->get(['student_id', 'score', 'assessed_by', 'assessed_at'])
                ->groupBy('student_id')
            : collect();

        // Ujian Praktik dinilai terpisah per bagian (mis. Lifting) — lihat NilaiPraktik
        $praktikBagian = array_keys(\App\Support\NilaiPraktik::weights($session));
        $praktikScores = $praktikBagian
            ? \App\Models\PraktikScore::where('exam_session_id', $session->id)->whereIn('student_id', $ids)
                ->whereIn('bagian', $praktikBagian)->whereNotNull('score')
                ->with('asesor:id,name')
                ->get(['student_id', 'bagian', 'asesor_id', 'updated_at'])
                ->groupBy('student_id')
            : collect();

        $interviews = $session->has_wawancara
            ? InterviewAssessment::where('exam_session_id', $session->id)->whereIn('student_id', $ids)
                ->with('asesor:id,name')
                ->get(['student_id', 'asesor_id', 'total_nilai', 'updated_at'])
                ->keyBy('student_id')
            : collect();

        $assignments = AsesorAssignment::where('exam_session_id', $session->id)->whereIn('student_id', $ids)
            ->with('asesor:id,name')->get()->keyBy('student_id');

        // Asesor yang sudah menyimpan Laporan Asesmen (FR.AK.05) — tautan unduh hanya untuk mereka
        $reported = AsesmenReport::where('exam_session_id', $session->id)->pluck('user_id')->flip();

        $userIds = $results->pluck('manager_verified_by')
            ->merge($results->pluck('cert_manager_verified_by'))
            ->merge($results->pluck('finalized_by'))
            ->merge($essays->flatten(1)->pluck('assessed_by'))
            ->filter()->unique();
        $users = User::whereIn('id', $userIds)->pluck('name', 'id');

        return $results->mapWithKeys(function (ParticipantResult $r) use (
            $session, $written, $examNames, $ended, $applications, $tuk, $grades, $essays, $interviews,
            $assignments, $reported, $users, $praktikBagian, $praktikScores
        ) {
            $sid    = $r->student_id;
            $app    = $applications->get($sid);
            $tv     = $tuk?->get($sid);
            $gs     = $grades->get($sid, collect());
            $ans    = $essays->get($sid, collect());
            $ia     = $interviews->get($sid);
            $asesor = $assignments->get($sid)?->asesor;
            $steps  = [];
            $events = [];

            // AK.01 — ditandatangani asesor (TtdAk01Controller::sign)
            $steps['ak01'] = $app
                ? $this->step($app->asesor_verified_at ? 'done' : 'pending', $app->asesor_verified_at, $app->asesor_signature_name)
                : $this->step('na', note: 'Belum ada permohonan');
            $this->event($events, $app?->asesor_verified_at, 'AK.01 ditandatangani asesor', $app?->asesor_signature_name);

            // Verifikasi TUK Online (bila diaktifkan di sesi)
            if ($tuk === null) {
                $steps['tuk'] = $this->step('na');
            } else {
                $label = $tv?->kesimpulan_awal
                    ? (TukChecklist::definition($tv->revision)['kesimpulan_awal'][$tv->kesimpulan_awal] ?? $tv->kesimpulan_awal)
                    : null;
                $steps['tuk'] = $this->step($tv?->kesimpulan_awal ? 'done' : 'pending', $tv?->verified_at, $tv?->pengawas_name, $label);
                $this->event($events, $tv?->verified_at, 'Verifikasi TUK awal', $tv?->pengawas_name, $label);
            }

            // Ujian tertulis (PG/Esai). Sesi tanpa ujian tertulis → tidak berlaku.
            $started = $gs->whereNotNull('start_time');
            $finished = $gs->whereNotNull('end_time');
            if (!$written) {
                $steps['ujian'] = $this->step('na');
            } elseif ($finished->count() >= count($written)) {
                $steps['ujian'] = $this->step('done', $finished->max('end_time'));
            } elseif ($started->isNotEmpty()) {
                $steps['ujian'] = $this->step('progress', note: 'Belum menyelesaikan semua ujian');
            } else {
                $steps['ujian'] = $this->step($ended ? 'absent' : 'pending', note: $ended ? 'Tidak ikut ujian' : null);
            }
            foreach ($gs as $g) {
                $name = $examNames[$g->exam_id] ?? 'Ujian';
                $this->event($events, $g->start_time, "Mulai ujian {$name}");
                $this->event($events, $g->end_time, "Selesai ujian {$name}");
            }

            // Dinilai asesor: semua jawaban esai bernilai + nilai wawancara terisi + nilai semua bagian praktik (bila dinilai terpisah).
            $needsEsai      = (bool) $session->exam_id_esai && $ans->isNotEmpty();
            $needsWawancara = (bool) $session->has_wawancara;
            $needsPraktik   = $praktikBagian !== [];
            $ps             = $praktikScores->get($r->student_id, collect());
            $esaiDone       = !$needsEsai || $ans->every(fn ($a) => $a->score !== null);
            $wawancaraDone  = !$needsWawancara || $ia?->total_nilai !== null;
            $praktikDone    = !$needsPraktik || $ps->count() >= count($praktikBagian);
            $esaiAt         = $ans->max('assessed_at');
            $esaiBy         = $ans->pluck('assessed_by')->filter()->unique()->map(fn ($id) => $users[$id] ?? null)->filter()->implode(', ');
            if (!$session->exam_id_esai && !$needsWawancara && !$needsPraktik) {
                $steps['nilai'] = $this->step('na');
            } elseif ($steps['ujian']['state'] === 'absent') {
                $steps['nilai'] = $this->step('na', note: 'Tidak ikut ujian');
            } else {
                $missing = array_values(array_filter([!$esaiDone ? 'esai' : null, !$wawancaraDone ? 'wawancara' : null, !$praktikDone ? 'praktik' : null]));
                $steps['nilai'] = $missing
                    ? $this->step('pending', by: $asesor?->name, note: 'Belum dinilai: ' . implode(' & ', $missing))
                    : $this->step('done', collect([$esaiAt, $ia?->updated_at, $ps->max('updated_at')])->map(fn ($t) => $this->iso($t))->filter()->max(), $asesor?->name);
            }
            if ($needsEsai && $esaiDone) {
                $this->event($events, $esaiAt, 'Esai dinilai', $esaiBy ?: null);
            }
            if ($ia?->total_nilai !== null) {
                $this->event($events, $ia->updated_at, 'Wawancara dinilai', $ia->asesor?->name);
            }
            if ($needsPraktik && $praktikDone) {
                $this->event($events, $ps->max('updated_at'), 'Ujian Praktik dinilai', $ps->pluck('asesor.name')->filter()->unique()->implode(', ') ?: null);
            }

            // Rekomendasi K/BK asesor (Laporan Asesmen) — tidak punya waktu tersimpan
            $steps['kbk'] = $app
                ? $this->step($app->asesor_rekomendasi ? 'done' : 'pending', by: $asesor?->name,
                    note: ['K' => 'Kompeten', 'BK' => 'Belum Kompeten'][$app->asesor_rekomendasi] ?? null)
                : $this->step('na', note: 'Belum ada permohonan');

            $name = fn ($id) => $id ? ($users[$id] ?? null) : null;
            $steps['pk']    = $this->step($r->manager_verified_at ? 'done' : 'pending', $r->manager_verified_at, $name($r->manager_verified_by));
            $steps['ms']    = $this->step($r->cert_manager_verified_at ? 'done' : 'pending', $r->cert_manager_verified_at, $name($r->cert_manager_verified_by));
            $steps['final'] = $this->step($r->is_finalized ? 'done' : 'pending', $r->finalized_at, $name($r->finalized_by),
                $r->is_finalized ? ($r->keputusan === 'LULUS' ? 'Lulus' : 'Tidak Lulus') : null);
            $steps['sp']    = $this->step($r->sp_distributed_at ? 'done' : 'pending', $r->sp_distributed_at, note: $r->sp_number);
            $steps['sk']    = $this->step($r->distributed_at ? 'done' : 'pending', $r->distributed_at, note: $r->sk_number);

            $this->event($events, $r->manager_verified_at, 'Diverifikasi Pengambil Keputusan', $name($r->manager_verified_by));
            $this->event($events, $r->cert_manager_verified_at, 'Diverifikasi Manajer Sertifikasi', $name($r->cert_manager_verified_by));
            $this->event($events, $r->finalized_at, 'Difinalisasi', $name($r->finalized_by), $steps['final']['note']);
            $this->event($events, $r->sp_distributed_at, 'SP terkirim ke peserta', note: $r->sp_number);
            $this->event($events, $r->distributed_at, 'SK & Sertifikat terkirim ke peserta', note: $r->sertifikat_number);
            $this->event($events, $r->fr_ak_14_signed_at, 'FR.AK.14 ditandatangani peserta');
            $this->event($events, $r->materai_stamped_at, 'FR.AK.14 bermaterai');

            $steps = $this->markSkipped($steps);

            // Tahap yang sedang ditunggu = tahap pertama yang belum selesai.
            $stage = collect($steps)->search(fn ($s) => in_array($s['state'], ['pending', 'progress'], true)) ?: 'selesai';
            $waiting = $stage === 'selesai' ? 'Selesai' : self::STEPS[$stage]['waiting'];
            if ($stage === 'ujian' && $steps['ujian']['state'] === 'progress') {
                $waiting = 'Belum menyelesaikan ujian';
            }
            $actor = match (self::ACTOR[$stage] ?? null) {
                'asesor' => $asesor?->name ? "asesor: {$asesor->name}" : 'asesor belum ditugaskan',
                'pk'     => $session->pengambilKeputusan?->name,
                default  => null,
            };

            usort($events, fn ($a, $b) => strcmp($a['at'], $b['at']));

            return [$sid => [
                'steps'    => $steps,
                'stage'    => $stage,
                'waiting'  => $waiting,
                'actor'    => $actor,
                'absent'   => $steps['ujian']['state'] === 'absent',
                'timeline' => $events,
                'docs'     => [
                    'application_id' => $app?->id,
                    'ak01_signed'    => (bool) $app?->asesor_verified_at,
                    'tuk'            => (bool) $tv,
                    'asesor_id'      => $asesor?->id,
                    'ak05_ready'     => $asesor && $reported->has($asesor->id),
                ],
            ]];
        });
    }

    /** Tahap yang belum tercatat padahal tahap sesudahnya sudah selesai → 'skipped' (bukan lagi ditunggu). */
    private function markSkipped(array $steps): array
    {
        $laterDone = false;
        foreach (array_reverse(array_keys($steps)) as $key) {
            if ($steps[$key]['state'] === 'done') {
                $laterDone = true;
            } elseif ($laterDone && in_array($steps[$key]['state'], ['pending', 'progress'], true)) {
                $steps[$key]['state'] = 'skipped';
            }
        }

        return $steps;
    }

    private function step(string $state, $at = null, ?string $by = null, ?string $note = null): array
    {
        return ['state' => $state, 'at' => $this->iso($at), 'by' => $by ?: null, 'note' => $note ?: null];
    }

    private function event(array &$events, $at, string $title, ?string $by = null, ?string $note = null): void
    {
        if ($at = $this->iso($at)) {
            $events[] = ['at' => $at, 'title' => $title, 'by' => $by ?: null, 'note' => $note ?: null];
        }
    }

    /** Waktu dalam format ISO yang sama dengan atribut ber-cast (zona waktu aplikasi). */
    private function iso($value): ?string
    {
        return $value ? Carbon::parse($value)->toIso8601String() : null;
    }
}
