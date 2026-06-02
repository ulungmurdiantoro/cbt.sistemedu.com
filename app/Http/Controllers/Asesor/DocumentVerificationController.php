<?php

namespace App\Http\Controllers\Asesor;

use App\Http\Controllers\Controller;
use App\Models\ApplicationDocument;
use App\Models\AsesorAssignment;
use App\Models\AssessmentApplication;
use App\Models\ExamSession;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentVerificationController extends Controller
{
    private function assignedStudentIds(int $examSessionId): \Illuminate\Support\Collection
    {
        return AsesorAssignment::where('user_id', auth()->id())
            ->where('exam_session_id', $examSessionId)
            ->pluck('student_id');
    }

    public function index(int $examSessionId)
    {
        $examSession       = ExamSession::with('examPg.classroom', 'examEsai.classroom')->findOrFail($examSessionId);
        $assignedStudentIds = $this->assignedStudentIds($examSessionId);

        abort_if($assignedStudentIds->isEmpty(), 403, 'Anda tidak ditugaskan pada sesi ini.');

        $students = Student::whereIn('id', $assignedStudentIds)
            ->orderBy('no_participant')
            ->get();

        // Ambil semua aplikasi + dokumen sekaligus (1 query)
        $applications = AssessmentApplication::whereIn('student_id', $assignedStudentIds)
            ->where('exam_session_id', $examSessionId)
            ->with(['documents.requirement', 'classroom.documentRequirements'])
            ->get()
            ->keyBy('student_id');

        $rows = $students->map(function ($student) use ($applications) {
            $app = $applications->get($student->id);
            $total    = $app?->classroom?->documentRequirements?->count() ?? 0;
            $verified = $app?->documents?->where('status', 'verified')->count() ?? 0;
            $rejected = $app?->documents?->where('status', 'rejected')->count() ?? 0;
            $pending  = $app?->documents?->where('status', 'pending')->count() ?? 0;
            $uploaded = $app?->documents?->count() ?? 0;

            return [
                'student_id'    => $student->id,
                'no_participant'=> $student->no_participant,
                'name'          => $student->name,
                'app_id'        => $app?->id,
                'app_status'    => $app?->status,
                'total_req'     => $total,
                'uploaded'      => $uploaded,
                'verified'      => $verified,
                'rejected'      => $rejected,
                'pending'       => $pending,
            ];
        });

        return inertia('Asesor/Dokumen/Index', [
            'exam_session' => $examSession,
            'rows'         => $rows,
        ]);
    }

    public function show(int $examSessionId, int $studentId)
    {
        $assignedStudentIds = $this->assignedStudentIds($examSessionId);
        abort_unless($assignedStudentIds->contains($studentId), 403);

        $examSession = ExamSession::with('examPg.classroom', 'examEsai.classroom')->findOrFail($examSessionId);
        $student     = Student::findOrFail($studentId);

        $application = AssessmentApplication::where('student_id', $studentId)
            ->where('exam_session_id', $examSessionId)
            ->with(['documents.requirement', 'classroom.documentRequirements'])
            ->first();

        return inertia('Asesor/Dokumen/Show', [
            'exam_session' => $examSession,
            'student'      => $student,
            'application'  => $application,
        ]);
    }

    public function verify(Request $request, int $examSessionId, int $studentId, int $docId)
    {
        $assignedStudentIds = $this->assignedStudentIds($examSessionId);
        abort_unless($assignedStudentIds->contains($studentId), 403);

        $request->validate([
            'status'         => 'required|in:verified,rejected',
            'reviewer_notes' => 'nullable|string|max:500',
        ]);

        $doc = ApplicationDocument::where('id', $docId)
            ->whereHas('application', fn($q) => $q
                ->where('student_id', $studentId)
                ->where('exam_session_id', $examSessionId))
            ->firstOrFail();

        $doc->update([
            'status'         => $request->status,
            'reviewer_notes' => $request->reviewer_notes,
        ]);

        return back()->with('success', 'Status dokumen berhasil diperbarui.');
    }

    public function download(int $examSessionId, int $studentId, int $docId)
    {
        $assignedStudentIds = $this->assignedStudentIds($examSessionId);
        abort_unless($assignedStudentIds->contains($studentId), 403);

        $doc = ApplicationDocument::where('id', $docId)
            ->whereHas('application', fn($q) => $q
                ->where('student_id', $studentId)
                ->where('exam_session_id', $examSessionId))
            ->firstOrFail();

        abort_if(!Storage::disk('private')->exists($doc->file_path), 404);

        return response()->download(
            Storage::disk('private')->path($doc->file_path),
            $doc->original_filename
        );
    }
}
