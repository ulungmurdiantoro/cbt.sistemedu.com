<?php

namespace App\Http\Controllers\Peserta;

use App\Http\Controllers\Controller;
use App\Models\AssessmentApplication;
use App\Models\ParticipantResult;
use App\Models\StudentTask;
use App\Support\AnswerFile;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $participant = auth()->guard('participant')->user();

        $applications = AssessmentApplication::with([
            'classroom.documentRequirements',
            'examSession',
            'student.classroom',
            'documents.requirement',
        ])
            ->where('participant_id', $participant->id)
            ->latest()
            ->get();

        $applications->each(function ($app) {
            // Tugas wajib sebelum ujian (sama dengan student/tugas/{sesi}) — bisa diunggah dari dashboard.
            $requiresTugas = $app->isApproved() && $app->exam_session_id && $app->student?->requiresTugasFor($app->exam_session_id);
            $task = $requiresTugas
                ? StudentTask::where('student_id', $app->student_id)->where('exam_session_id', $app->exam_session_id)->where('bagian', 1)->first()
                : null;
            $examPortalOnly = $requiresTugas && \App\Support\UjianPraktik::examPortalOnly($app->exam_session_id);
            $status = $examPortalOnly ? \App\Support\UjianPraktik::tugasStatus($app->exam_session_id, $app->student_id) : null;
            $app->setAttribute('tugas', $requiresTugas ? [
                'file' => $task ? ['name' => $task->original_filename, 'uploaded_at' => $task->uploaded_at] : null,
                // Upload Tugas atau Ujian Praktik/Keterampilan (panduan + template) — diatur admin per skema
                ...\App\Support\UjianPraktik::tugasProps(\App\Support\UjianPraktik::examFor($app->exam_session_id)),
                // Berwaktu / beberapa bagian: dikerjakan di portal ujian — panduan & template tidak dibuka di sini
                'exam_portal_only' => $examPortalOnly,
                ...($examPortalOnly ? [
                    'panduan'       => null,
                    'template_name' => null,
                    'parts'         => array_values(array_map(
                        fn (array $p) => \Illuminate\Support\Arr::only($p, ['bagian', 'title', 'durasi', 'uploaded']),
                        $status['parts']
                    )),
                ] : []),
            ] : null);

            $app->setAttribute('docs_required', $app->classroom->documentRequirements->where('is_required', true)->count());
            $app->setAttribute('docs_uploaded', $app->documents->count());

            $app->setAttribute('rejected_documents', $app->documents
                ->where('status', 'rejected')
                ->map(fn($doc) => [
                    'id'             => $doc->id,
                    'requirement_id' => $doc->classroom_document_requirement_id,
                    'label'          => $doc->requirement?->label ?? 'Dokumen',
                    'reviewer_notes' => $doc->reviewer_notes,
                ])
                ->values());

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
                    'sp_number'        => $result->sp_number,
                    'sp_distributed_at' => $result->sp_distributed_at,
                    'sk_number'        => $result->sk_number,
                    'sertifikat_number'=> $result->sertifikat_number,
                    'valid_until'      => $result->valid_until,
                    'attempt'          => $result->attempt,
                    'fr_ak_14_signed_at'     => $result->fr_ak_14_signed_at,
                    'fr_ak_14_completed'     => $result->frAk14Completed(),
                    'materai_exempt'         => $result->frAk14ExemptFromMaterai(),
                    'materai_status'         => $result->materai_status,
                    'materai_failure_reason' => $result->materai_failure_reason,
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
            'tugas_accept' => AnswerFile::accept(),
        ]);
    }
}
