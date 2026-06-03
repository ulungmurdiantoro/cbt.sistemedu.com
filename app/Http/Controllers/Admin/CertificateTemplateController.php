<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamSession;
use App\Models\ParticipantResult;
use App\Models\Student;
use App\Services\DocumentGeneratorService;
use Carbon\Carbon;

class CertificateTemplateController extends Controller
{
    public function show()
    {
        return inertia('Admin/CertificateTemplate/Show');
    }

    /**
     * Preview salah satu dari 3 jenis dokumen.
     * type: sp | sk | sertifikat
     */
    public function preview(string $type)
    {
        abort_if(!in_array($type, ['sp', 'sk', 'sertifikat']), 404);

        $generator = app(DocumentGeneratorService::class);

        // Cari result nyata jika ada
        $result = match ($type) {
            'sertifikat' => ParticipantResult::where('is_finalized', true)
                ->where('keputusan', 'LULUS')
                ->with(['student', 'examSession.examPg.classroom', 'examSession.examEsai.classroom'])
                ->latest()->first(),
            default => ParticipantResult::where('is_finalized', true)
                ->with(['student', 'examSession.examPg.classroom', 'examSession.examEsai.classroom'])
                ->latest()->first(),
        };

        // Fallback dummy jika belum ada result
        if (!$result) {
            $result = $this->makeDummyResult($type);
        }

        $pdf = match ($type) {
            'sp'         => $generator->generateSp($result),
            'sk'         => $generator->generateSk($result),
            'sertifikat' => $generator->generateSertifikat($result),
        };

        $labels = [
            'sp'         => 'Preview_SP',
            'sk'         => 'Preview_SK',
            'sertifikat' => 'Preview_Sertifikat',
        ];

        return response($pdf, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $labels[$type] . '.pdf"');
    }

    private function makeDummyResult(string $type): ParticipantResult
    {
        $classroom = new \App\Models\Classroom();
        $classroom->id         = 0;
        $classroom->title      = 'Lead Implementer Standar Laboratorium ISO IEC 17025 2017';
        $classroom->title_en   = 'Lead Implementer Laboratory Standard ISO IEC 17025 2017';
        $classroom->kode_skema = 'LSP-EGC-001';

        $examPg = new \App\Models\Exam();
        $examPg->id   = 0;
        $examPg->type = 'Pilihan Ganda';
        $examPg->setRelation('classroom', $classroom);

        $session = new ExamSession();
        $session->id             = 0;
        $session->title          = 'Lead Implementer ISO 17025:2017 — Batch 001';
        $session->start_time     = Carbon::now()->subDays(3);
        $session->end_time       = Carbon::now()->subDay();
        $session->tempat_ujian   = 'Online (Zoom Meeting)';
        $session->konteks_asesmen = 'Sertifikasi Person';
        $session->kode_batch     = '001';
        $session->setRelation('examPg', $examPg);
        $session->setRelation('examEsai', null);

        $student = new Student();
        $student->id             = 0;
        $student->name           = 'Dr. Budi Santoso, M.Sc.';
        $student->no_participant = 'PREVIEW-001';

        $result = new ParticipantResult();
        $result->id                = 0;
        $result->nilai_pg          = 85.0;
        $result->nilai_esai        = 80.0;
        $result->nilai_wawancara   = 22.5;
        $result->nilai_akhir       = 82.5;
        $result->keputusan         = 'LULUS';
        $result->is_finalized      = true;
        $result->finalized_at      = Carbon::now();
        $result->valid_until       = Carbon::now()->addYears(3);
        $result->sk_number         = 'PREVIEW/SK-SP/LSP-EDUKIA/VI/2026';
        $result->sertifikat_number = $type === 'sertifikat' ? 'PRV-001-06-2026-00001' : null;
        $result->sp_number         = $type === 'sp' ? 'PREVIEW/SP/LSP-EDUKIA/VI/2026' : null;
        $result->attempt           = 1;
        $result->setRelation('student', $student);
        $result->setRelation('examSession', $session);

        return $result;
    }
}
