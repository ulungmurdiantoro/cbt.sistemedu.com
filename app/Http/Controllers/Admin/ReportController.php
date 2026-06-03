<?php

namespace App\Http\Controllers\Admin;

use Mpdf\Mpdf;
use Carbon\Carbon;
use App\Models\Grade;
use App\Models\Answer;
use App\Models\AnswerEssay;
use App\Models\ExamSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use App\Exports\GradesExport;
use App\Exports\GradesEssayExport;
use App\Exports\GradesEssayMigasExport;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function index()
    {
        $exam_sessions = ExamSession::with('examPg.classroom', 'examEsai.classroom')
            ->orderBy('id', 'desc')
            ->get();

        return inertia('Admin/Reports/Index', [
            'exam_sessions' => $exam_sessions,
            'grades'        => [],
        ]);
    }

    public function filter(Request $request)
    {
        $request->validate([
            'exam_session_id' => 'required',
        ]);

        $exam_sessions = ExamSession::with('examPg.classroom', 'examEsai.classroom')
            ->orderBy('id', 'desc')
            ->get();

        $exam_session = ExamSession::with('examPg.classroom', 'examEsai.classroom')
            ->find($request->exam_session_id);

        $grades = $exam_session
            ? Grade::with('student', 'exam.classroom', 'exam_session')
                ->where('exam_session_id', $exam_session->id)
                ->get()
            : [];

        return inertia('Admin/Reports/Index', [
            'exam_sessions' => $exam_sessions,
            'grades'        => $grades,
        ]);
    }

    public function show($id)
    {
        $grade = Grade::with('student', 'exam.classroom', 'exam_session')->findOrFail($id);

        $grade->setRelation('questions', $grade->exam->questions()->paginate(10));
        $grade->setRelation('answers', $grade->exam->answers()->where('student_id', $grade->student_id)->paginate(10));
        $grade->setRelation('essays', $grade->exam->essays()->paginate(10));
        $grade->setRelation('essaysanswers', $grade->exam->essaysanswers()->where('student_id', $grade->student_id)->paginate(10));

        return inertia('Admin/Reports/Show', [
            'grade' => $grade,
        ]);
    }

    public function export(Request $request)
    {
        $request->validate([
            'exam_session_id' => 'required',
        ]);

        $exam_session = ExamSession::with('examPg.classroom', 'examEsai.classroom')
            ->findOrFail($request->exam_session_id);

        $grades = Grade::with(['answers', 'answersEssay', 'exam.classroom', 'exam_session', 'student'])
            ->where('exam_session_id', $exam_session->id)
            ->get()
            ->sortBy(fn($g) => $g->student->no_participant)
            ->values();

        $exam = $exam_session->referenceExam;

        return match ($exam->type) {
            'Essay'       => Excel::download(new GradesEssayExport($grades), 'essay_grades_' . $exam->title . ' — ' . Carbon::now() . '.xlsx'),
            'Essay Migas' => Excel::download(new GradesEssayMigasExport($grades), 'essay_migas_grades_' . $exam->title . ' — ' . Carbon::now() . '.xlsx'),
            default       => Excel::download(new GradesExport($grades), 'grades_' . $exam->title . ' — ' . Carbon::now() . '.xlsx'),
        };
    }

    /**
     * Export PDF laporan nilai.
     *
     * Query param `layout`:
     *   - "ringkas" → view EssayReportPDF, A4 Portrait, download file
     *   - "lebar"   → view ReportPDF, A0 Landscape, inline (default)
     */
    public function exportPdf(Request $request)
    {
        $request->validate([
            'exam_session_id' => 'required',
        ]);

        $layouts = [
            'ringkas' => ['view' => 'EssayReportPDF', 'format' => 'A4', 'orientation' => 'P', 'inline' => false],
            'lebar'   => ['view' => 'ReportPDF',      'format' => 'A0', 'orientation' => 'L', 'inline' => true],
        ];
        $config = $layouts[$request->input('layout', 'lebar')];

        $exam_session = ExamSession::with('examPg.classroom', 'examEsai.classroom')
            ->findOrFail($request->exam_session_id);

        $grades = Grade::with(['student', 'exam.classroom', 'exam_session'])
            ->where('exam_session_id', $request->exam_session_id)
            ->get()
            ->sortBy(fn($g) => $g->student->no_participant)
            ->values();

        $exam = $exam_session->referenceExam;

        // Muat relasi soal & jawaban sekali, bukan per-baris (hindari N+1)
        $examQuestions   = $exam->questions()->get();
        $examEssays      = $exam->essays()->get();
        $allAnswers      = Answer::where('exam_id', $exam->id)
            ->where('exam_session_id', $request->exam_session_id)
            ->get()->groupBy('student_id');
        $allEssayAnswers = AnswerEssay::where('exam_id', $exam->id)
            ->where('exam_session_id', $request->exam_session_id)
            ->get()->groupBy('student_id');

        foreach ($grades as $grade) {
            $grade->setRelation('questions',    $examQuestions);
            $grade->setRelation('answers',      $allAnswers->get($grade->student_id, collect()));
            $grade->setRelation('essays',       $examEssays);
            $grade->setRelation('essaysanswers', $allEssayAnswers->get($grade->student_id, collect()));
        }

        $html = View::make($config['view'], compact('grades', 'exam', 'exam_session'))->render();

        $mpdf = new Mpdf([
            'mode'        => 'utf-8',
            'format'      => $config['format'],
            'orientation' => $config['orientation'],
        ]);
        $mpdf->WriteHTML($html);

        $filename = 'grades_' . Str::slug($exam->title) . '_' . now()->format('Ymd_His') . '.pdf';

        if ($config['inline']) {
            return response($mpdf->Output($filename, \Mpdf\Output\Destination::INLINE))
                ->header('Content-Type', 'application/pdf');
        }

        $tempPath = storage_path('app/public/' . $filename);
        $mpdf->Output($tempPath, \Mpdf\Output\Destination::FILE);

        return response()->download($tempPath)->deleteFileAfterSend();
    }
}
