<?php

namespace App\Http\Controllers\Admin;

use Mpdf\Mpdf;
use Carbon\Carbon;
use App\Models\Exam;
use App\Models\Grade;
use App\Models\ExamSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use App\Exports\GradesExport;
use App\Exports\GradesEssayExport;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{    
    public function index()
    {
        // get all exam sessions
        $exam_sessions = ExamSession::with('exam.classroom')->get();

        return inertia('Admin/Reports/Index', [
            'exam_sessions' => $exam_sessions,
            'grades'        => []
        ]);
    }
    
    public function filter(Request $request)
    {
        $request->validate([
            'exam_session_id' => 'required',
        ]);

        // get all exam sessions
        $exam_sessions = ExamSession::with('exam.classroom')->get();

        // get selected exam session
        $exam_session = ExamSession::with('exam.classroom')
            ->where('id', $request->exam_session_id)
            ->first();

        if ($exam_session) {
            $grades = Grade::with('student', 'exam.classroom', 'exam_session')
                ->where('exam_id', $exam_session->exam_id)
                ->where('exam_session_id', $exam_session->id)
                ->get();
        } else {
            $grades = [];
        }

        return inertia('Admin/Reports/Index', [
            'exam_sessions' => $exam_sessions,
            'grades'        => $grades,
        ]);
    }

    public function show($id)
    {
        $grade = Grade::with('student', 'exam.classroom', 'questions.answers', 'answers', 'exam_session')
            ->findOrFail($id);

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
            'exam_session_id' => 'required'
        ]);

        // get selected exam session
        $exam_session = ExamSession::with('exam.classroom')
            ->where('id', $request->exam_session_id)
            ->first();

        if (!$exam_session) {
            abort(404, 'Exam session not found');
        }

        // get grades / nilai
        $grades = Grade::with([
            'answers',
            'answersEssay',
            'exam.classroom',
            'exam_session',
            'student'
        ])
        ->where('exam_id', $exam_session->exam_id)
        ->where('exam_session_id', $exam_session->id)
        ->get()
        ->sortBy(fn($grade) => $grade->student->no_participant)
        ->values(); // reset index agar urutan dari 0 kembali

        $exam = $exam_session->exam;

        if ($exam->type == 'Essay') {
            return Excel::download(new GradesEssayExport($grades), 'essay_grades_' . $exam->title . ' — ' . Carbon::now() . '.xlsx');
        } else {
            return Excel::download(new GradesExport($grades), 'grades_' . $exam->title . ' — ' . Carbon::now() . '.xlsx');
        }
    }

    public function exportPdf(Request $request)
    {
        $request->validate([
            'exam_session_id' => 'required'
        ]);

        $exam_session = ExamSession::with('exam.classroom')
            ->findOrFail($request->exam_session_id);

        $grades = Grade::with([
            'student',
            'exam.classroom',
            'exam.questions.answers',
            'exam.answers',
            'exam.essays',
            'exam.essaysanswers',
            'exam_session'
        ])->where('exam_session_id', $request->exam_session_id)->get();

        foreach ($grades as $grade) {
            $grade->setRelation('questions', $grade->exam->questions()->get());
            $grade->setRelation('answers', $grade->exam->answers()->where('student_id', $grade->student_id)->get());
            $grade->setRelation('essays', $grade->exam->essays()->get());
            $grade->setRelation('essaysanswers', $grade->exam->essaysanswers()->where('student_id', $grade->student_id)->get());
        }

        $grades = $grades->sortBy(fn($g) => $g->student->no_participant)->values();
        $exam = $exam_session->exam;

        $html = View::make('EssayReportPDF', compact('grades', 'exam', 'exam_session'))->render();

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'P',
        ]);

        $mpdf->WriteHTML($html);

        $filename = 'grades_' . Str::slug($exam->title) . '_' . now()->format('Ymd_His') . '.pdf';
        $tempPath = storage_path('app/public/' . $filename);

        $mpdf->Output($tempPath, \Mpdf\Output\Destination::FILE);

        return response()->download($tempPath)->deleteFileAfterSend();
    }


    public function exportPdf2(Request $request)
    {
        $request->validate([
            'exam_session_id' => 'required'
        ]);

        // get selected exam session
        $exam_session = ExamSession::with('exam.classroom')
            ->where('id', $request->exam_session_id)
            ->first();

        if (!$exam_session) {
            abort(404, 'Exam session not found');
        }

        // get grades
        $grades = Grade::with([
            'student',
            'exam.classroom',
            'exam.questions.answers',
            'exam.answers',
            'exam.essays',
            'exam.essaysanswers',
            'exam_session'
        ])->where('exam_session_id', $request->exam_session_id)->get();

        foreach ($grades as $grade) {
            $grade->setRelation('questions', $grade->exam->questions()->get());
            $grade->setRelation('answers', $grade->exam->answers()->where('student_id', $grade->student_id)->get());
            $grade->setRelation('essays', $grade->exam->essays()->get());
            $grade->setRelation('essaysanswers', $grade->exam->essaysanswers()->where('student_id', $grade->student_id)->get());
        }

        $grades = $grades->sortBy(function ($grade) {
            return $grade->student->no_participant;
        })->values(); 

        $exam = $exam_session->exam;

        // render HTML view
        
            $html = View::make('ReportPDF', [
                'grades' => $grades,
                'exam' => $exam,
                'exam_session' => $exam_session
            ])->render();

        // generate PDF
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A0',
            'orientation' => 'L',
        ]);

        $mpdf->WriteHTML($html);

        $filename = 'grades_' . $exam->title . '_' . now()->format('Ymd_His') . '.pdf';
        return response($mpdf->Output($filename, \Mpdf\Output\Destination::INLINE))
            ->header('Content-Type', 'application/pdf');
    }
}