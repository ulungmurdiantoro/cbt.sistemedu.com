<?php

namespace App\Http\Controllers\Asesor;

use App\Http\Controllers\Controller;
use App\Models\AnswerEssay;
use App\Models\AsesorAssignment;
use App\Models\AssessmentApplication;
use App\Models\ExamSession;
use App\Models\Grade;
use App\Models\InterviewAssessment;
use App\Models\Student;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    /**
     * Mulai sesi ini, sesi baru pindah ke "Sesi Selesai" setelah tugas asesor di sesi itu
     * tuntas (lihat progress()). Sesi sebelumnya tetap memakai jam selesai sesi.
     */
    public const PROGRESS_FROM_SESSION_ID = 75;

    public function __invoke()
    {
        $asesor = auth()->user();

        $session_ids = AsesorAssignment::where('user_id', $asesor->id)
            ->pluck('exam_session_id')
            ->unique();

        $all = ExamSession::with('examPg.classroom', 'examEsai.classroom')
            ->whereIn('id', $session_ids)
            ->orderBy('end_time', 'desc')
            ->get()
            ->map(function ($session) use ($asesor) {
                $studentIds = AsesorAssignment::where('user_id', $asesor->id)
                    ->where('exam_session_id', $session->id)
                    ->pluck('student_id');

                $session->student_count = $studentIds->count();
                $session->progress      = $session->id >= self::PROGRESS_FROM_SESSION_ID
                    ? $this->progress($session, $studentIds)
                    : null;

                return $session;
            });

        $isCompleted = fn ($s) => $s->progress
            ? $s->progress['complete']
            : (!$s->end_time || now()->gte($s->end_time));

        return inertia('Asesor/Dashboard', [
            'active_sessions'    => $all->reject($isCompleted)->values(),
            'completed_sessions' => $all->filter($isCompleted)->values(),
            'asesor'             => $asesor,
        ]);
    }

    /**
     * Kelengkapan tugas asesor di satu sesi, atas peserta aktif yang ditugaskan kepadanya:
     * - ak01      : peserta yang punya permohonan sudah ditandatangani FR.AK.01-nya;
     * - esai      : semua jawaban esai sudah dinilai (peserta yang tidak mengerjakan tidak dihitung);
     * - wawancara : peserta yang sudah mulai ujian punya nilai wawancara.
     * Sesi baru "complete" kalau semuanya lengkap DAN sudah ada peserta yang mulai ujian —
     * supaya sesi yang belum berjalan tidak langsung pindah ke Sesi Selesai.
     */
    private function progress(ExamSession $session, Collection $studentIds): array
    {
        // Akun lama hasil re-issue tidak ikut dihitung (sama dengan halaman Esai/Wawancara/TTD AK.01)
        $studentIds = Student::whereIn('id', $studentIds)->where('is_active', true)->pluck('id');

        // Sesi tanpa ujian tertulis (hanya wawancara): semua peserta dianggap ikut.
        $started = $session->exam_id_pg || $session->exam_id_esai
            ? Grade::where('exam_session_id', $session->id)
                ->whereIn('student_id', $studentIds)
                ->whereNotNull('start_time')
                ->distinct()
                ->pluck('student_id')
            : $studentIds;

        $applications = AssessmentApplication::where('exam_session_id', $session->id)
            ->whereIn('student_id', $studentIds)
            ->get(['student_id', 'asesor_verified_at']);

        $items = ['ak01' => [
            'done'  => $applications->whereNotNull('asesor_verified_at')->count(),
            'total' => $applications->count(),
        ]];

        if ($session->exam_id_esai) {
            $answers = AnswerEssay::where('exam_session_id', $session->id)
                ->where('exam_id', $session->exam_id_esai)
                ->whereIn('student_id', $studentIds)
                ->get(['student_id', 'score'])
                ->groupBy('student_id');

            $items['esai'] = [
                'done'  => $answers->filter(fn ($rows) => $rows->every(fn ($a) => $a->score !== null))->count(),
                'total' => $answers->count(),
            ];
        }

        if ($session->has_wawancara) {
            $items['wawancara'] = [
                'done'  => InterviewAssessment::where('exam_session_id', $session->id)
                    ->whereIn('student_id', $started)
                    ->whereNotNull('total_nilai')
                    ->count(),
                'total' => $started->count(),
            ];
        }

        return [
            'items'    => $items,
            'complete' => $started->isNotEmpty() && collect($items)->every(fn ($i) => $i['done'] >= $i['total']),
        ];
    }
}
