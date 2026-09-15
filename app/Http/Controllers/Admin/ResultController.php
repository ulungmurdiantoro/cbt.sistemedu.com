<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamSession;
use App\Models\ParticipantResult;
use App\Models\Student;
use App\Services\DocumentGeneratorService;
use App\Services\ResultCalculatorService;

class ResultController extends Controller
{
    public function __construct(
        private ResultCalculatorService $calculator,
    ) {}

    public function index()
    {
        $sessions = ExamSession::withCount('participantResults')
            ->orderByRaw('CASE WHEN end_time > NOW() THEN 0 ELSE 1 END ASC')
            ->orderByRaw('CASE WHEN end_time > NOW() THEN end_time END ASC')
            ->orderBy('end_time', 'desc')
            ->paginate(15);

        return inertia('Admin/Results/Index', ['sessions' => $sessions]);
    }

    public function show(ExamSession $examSession)
    {
        $examSession->load(['examPg.classroom', 'examEsai.classroom']);

        $results = $this->calculator->recalcForSession($examSession);

        $studentIds = collect($results)->pluck('student_id');
        $students   = Student::whereIn('id', $studentIds)->with('participant')->get()->keyBy('id');

        $rows = collect($results)->map(function ($r) use ($students) {
            $student = $students->get($r->student_id);
            return [
                'result_id'         => $r->id,
                'student_id'        => $r->student_id,
                'no_participant'    => $student?->no_participant,
                'name'              => $student?->name,
                'nilai_pg'          => $r->nilai_pg,
                'nilai_esai'        => $r->nilai_esai,
                'nilai_wawancara'   => $r->nilai_wawancara,
                'nilai_akhir'       => $r->nilai_akhir,
                'keputusan'         => $r->keputusan,
                'is_finalized'      => $r->is_finalized,
                'sk_number'         => $r->sk_number,
                'sp_number'         => $r->sp_number,
                'sertifikat_number' => $r->sertifikat_number,
                'distributed_at'    => $r->distributed_at,
                'sp_distributed_at' => $r->sp_distributed_at,
                'attempt'           => $r->attempt,
            ];
        })->sortBy('no_participant')->values();

        $classroomId = $examSession->referenceExam?->classroom_id;
        $scheme = $classroomId
            ? \App\Models\GradingScheme::where('classroom_id', $classroomId)->first()
            : null;

        return inertia('Admin/Results/Show', [
            'exam_session' => $examSession,
            'rows'         => $rows,
            'scheme'       => $scheme,
        ]);
    }

    // Finalisasi nilai dipindah ke Manager\SertifikasiController — kelulusan
    // & penerbitan sertifikat sekarang jadi wewenang Pengambil Keputusan,
    // bukan admin biasa.

    public function downloadSp(ExamSession $examSession, Student $student, DocumentGeneratorService $generator)
    {
        $result = ParticipantResult::where('exam_session_id', $examSession->id)
            ->where('student_id', $student->id)
            ->where('is_finalized', true)
            ->firstOrFail();

        $pdf      = $generator->spPdf($result);
        $filename = 'SP_' . $student->no_participant . '.pdf';

        return response($pdf, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', "inline; filename=\"{$filename}\"");
    }

    public function downloadSk(ExamSession $examSession, Student $student, DocumentGeneratorService $generator)
    {
        $result = ParticipantResult::where('exam_session_id', $examSession->id)
            ->where('student_id', $student->id)
            ->where('is_finalized', true)
            ->firstOrFail();

        $withKan  = request()->boolean('kan');
        $pdf      = $generator->skPdf($result, $withKan);
        $filename = 'SK_' . $student->no_participant . '.pdf';

        return response($pdf, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', "inline; filename=\"{$filename}\"");
    }

    public function downloadSertifikat(ExamSession $examSession, Student $student, DocumentGeneratorService $generator)
    {
        $result = ParticipantResult::where('exam_session_id', $examSession->id)
            ->where('student_id', $student->id)
            ->where('is_finalized', true)
            ->where('keputusan', 'LULUS')
            ->firstOrFail();

        $withKan  = request()->boolean('kan');
        $pdf      = $generator->sertifikatPdf($result, $withKan);
        $filename = 'Sertifikat_' . $student->no_participant . '.pdf';

        return response($pdf, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', "inline; filename=\"{$filename}\"");
    }

    /** Tahap 1 — kirim SP saja, supaya peserta bisa mengajukan revisi (mis. typo nama) sebelum SK/Sertifikat resmi terbit. */
    public function distributeSp(ExamSession $examSession)
    {
        // Abaikan akun peserta yang sudah dinonaktifkan (akun lama hasil
        // re-issue/merge duplikat) — baris participant_results-nya bisa saja
        // masih is_finalized=false dan tidak boleh ikut memblokir pengiriman
        // untuk seluruh sesi.
        $unfinalized = ParticipantResult::where('exam_session_id', $examSession->id)
            ->where('is_finalized', false)
            ->whereHas('student', fn ($q) => $q->where('is_active', true))
            ->count();

        if ($unfinalized > 0) {
            return redirect()->back()->withErrors(['distribute' => 'Finalisasi semua peserta terlebih dahulu.']);
        }

        \App\Jobs\DistributeSpJob::dispatch($examSession->id);

        return redirect()->back()->with('success', 'Pengiriman SP dijadwalkan dan akan dikirim segera.');
    }

    /** Tahap 2 — kirim SK & Sertifikat (dokumen final). */
    public function distribute(ExamSession $examSession)
    {
        $unfinalized = ParticipantResult::where('exam_session_id', $examSession->id)
            ->where('is_finalized', false)
            ->whereHas('student', fn ($q) => $q->where('is_active', true))
            ->count();

        if ($unfinalized > 0) {
            return redirect()->back()->withErrors(['distribute' => 'Finalisasi semua peserta terlebih dahulu.']);
        }

        \App\Jobs\DistributeResultsJob::dispatch($examSession->id);

        return redirect()->back()->with('success', 'Distribusi SK & Sertifikat dijadwalkan dan akan dikirim segera.');
    }
}
