<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamSession;
use App\Models\ParticipantResult;
use App\Models\Student;
use App\Services\DocumentGeneratorService;
use App\Services\NumberingService;
use App\Services\ResultCalculatorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ResultController extends Controller
{
    public function __construct(
        private ResultCalculatorService $calculator,
        private NumberingService $numbering,
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
                'result_id'       => $r->id,
                'student_id'      => $r->student_id,
                'no_participant'  => $student?->no_participant,
                'name'            => $student?->name,
                'nilai_pg'        => $r->nilai_pg,
                'nilai_esai'      => $r->nilai_esai,
                'nilai_wawancara' => $r->nilai_wawancara,
                'nilai_akhir'     => $r->nilai_akhir,
                'keputusan'       => $r->keputusan,
                'is_finalized'    => $r->is_finalized,
                'sk_number'       => $r->sk_number,
                'sertifikat_number' => $r->sertifikat_number,
                'distributed_at'  => $r->distributed_at,
                'attempt'         => $r->attempt,
            ];
        })->sortBy('name')->values();

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

    public function finalize(ExamSession $examSession)
    {
        $this->authorize('finalize', ParticipantResult::class);

        $examSession->load(['examPg.classroom', 'examEsai.classroom']);

        $classroomId = $examSession->referenceExam?->classroom_id;
        $classroom   = $classroomId ? \App\Models\Classroom::find($classroomId) : null;

        $results = ParticipantResult::where('exam_session_id', $examSession->id)
            ->where('is_finalized', false)
            ->get();

        foreach ($results as $result) {
            if ($result->is_finalized) continue;

            $skNum = $this->numbering->nextSkNumber();

            $sertifikatNum = null;
            if ($result->keputusan === 'LULUS' && $classroom) {
                $sertifikatNum = $this->numbering->nextSertifikatNumber(
                    $classroom->kode_skema ?? '',
                    $examSession->kode_batch ?? '',
                    $classroomId
                );
            }

            $result->update([
                'is_finalized'     => true,
                'finalized_at'     => now(),
                'finalized_by'     => Auth::id(),
                'sk_number'        => $skNum,
                'sertifikat_number'=> $sertifikatNum,
                'valid_until'      => now()->addYears(config('lsp.sertifikat_valid_years', 3)),
            ]);
        }

        return redirect()->back()->with('success', 'Hasil berhasil difinalisasi.');
    }

    public function downloadSk(ExamSession $examSession, Student $student, DocumentGeneratorService $generator)
    {
        $this->authorize('download', ParticipantResult::class);
        $result = ParticipantResult::where('exam_session_id', $examSession->id)
            ->where('student_id', $student->id)
            ->where('is_finalized', true)
            ->firstOrFail();

        $pdf      = $generator->generateSk($result, 'with_kop');
        $filename = 'SK_' . $student->no_participant . '.pdf';

        return response($pdf, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', "inline; filename=\"{$filename}\"");
    }

    public function downloadSertifikat(ExamSession $examSession, Student $student, DocumentGeneratorService $generator)
    {
        $this->authorize('download', ParticipantResult::class);
        $result = ParticipantResult::where('exam_session_id', $examSession->id)
            ->where('student_id', $student->id)
            ->where('is_finalized', true)
            ->where('keputusan', 'LULUS')
            ->firstOrFail();

        $pdf      = $generator->generateSertifikat($result, 'with_kop');
        $filename = 'Sertifikat_' . $student->no_participant . '.pdf';

        return response($pdf, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', "inline; filename=\"{$filename}\"");
    }

    public function distribute(ExamSession $examSession)
    {
        $this->authorize('distribute', ParticipantResult::class);
        $unfinalized = ParticipantResult::where('exam_session_id', $examSession->id)
            ->where('is_finalized', false)
            ->count();

        if ($unfinalized > 0) {
            return redirect()->back()->withErrors(['distribute' => 'Finalisasi semua peserta terlebih dahulu.']);
        }

        \App\Jobs\DistributeResultsJob::dispatch($examSession->id);

        return redirect()->back()->with('success', 'Distribusi dokumen dijadwalkan dan akan dikirim segera.');
    }
}
