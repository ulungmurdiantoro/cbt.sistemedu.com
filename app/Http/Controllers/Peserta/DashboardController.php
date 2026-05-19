<?php

namespace App\Http\Controllers\Peserta;

use App\Http\Controllers\Controller;
use App\Models\AssessmentApplication;
use App\Models\ParticipantResult;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $participant = auth()->guard('participant')->user();

        $applications = AssessmentApplication::with([
            'classroom.documentRequirements',
            'examSession',
            'student',
            'documents',
        ])
            ->where('participant_id', $participant->id)
            ->latest()
            ->get();

        $applications->each(function ($app) {
            $app->setAttribute('docs_required', $app->classroom->documentRequirements->where('is_required', true)->count());
            $app->setAttribute('docs_uploaded', $app->documents->count());

            if ($app->student && $app->exam_session_id) {
                $result = ParticipantResult::where('exam_session_id', $app->exam_session_id)
                    ->where('student_id', $app->student_id)
                    ->first();

                $app->setAttribute('result', $result ? [
                    'id'               => $result->id,
                    'nilai_akhir'      => $result->nilai_akhir,
                    'keputusan'        => $result->keputusan,
                    'is_finalized'     => $result->is_finalized,
                    'distributed_at'   => $result->distributed_at,
                    'sk_number'        => $result->sk_number,
                    'sertifikat_number'=> $result->sertifikat_number,
                    'valid_until'      => $result->valid_until,
                    'attempt'          => $result->attempt,
                ] : null);

                // Apakah window remidi masih terbuka
                $remidiOpen = false;
                if ($result && $result->keputusan === 'TIDAK_LULUS' && $result->attempt < 2) {
                    $session    = $app->examSession;
                    $remidiOpen = $session
                        && $session->remidi_start_at
                        && now()->between($session->remidi_start_at, $session->remidi_end_at ?? now()->addYear());
                }
                $app->setAttribute('remidi_open', $remidiOpen);
            } else {
                $app->setAttribute('result', null);
                $app->setAttribute('remidi_open', false);
            }
        });

        return inertia('Peserta/Dashboard/Index', [
            'applications' => $applications,
        ]);
    }
}
