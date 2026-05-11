<?php

namespace App\Http\Controllers\Peserta;

use App\Http\Controllers\Controller;
use App\Models\AssessmentApplication;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $participant = auth()->guard('participant')->user();

        $applications = AssessmentApplication::with([
            'classroom.documentRequirements',
            'examSession.exam',
            'student',
            'documents',
        ])
            ->where('participant_id', $participant->id)
            ->latest()
            ->get();

        $applications->each(function ($app) {
            $app->setAttribute('docs_required', $app->classroom->documentRequirements->where('is_required', true)->count());
            $app->setAttribute('docs_uploaded', $app->documents->count());
        });

        return inertia('Peserta/Dashboard/Index', [
            'applications' => $applications,
        ]);
    }
}
