<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApplicationDocument;
use App\Models\AsesorAssignment;
use App\Models\AssessmentApplication;
use App\Models\ExamGroup;
use App\Models\ExamSession;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

// Versi admin dari Asesor\DocumentVerificationController — admin bisa membuka
// verifikasi dokumen peserta manapun di sesi ini (tidak dibatasi AsesorAssignment
// milik sendiri seperti di portal asesor). Saat menandatangani "Verifikasi Akhir",
// yang tercatat & dibubuhkan TTD-nya adalah asesor yang SUDAH ditugaskan lewat
// menu Penugasan Asesor — bukan admin yang sedang login — supaya dokumen tetap
// mencerminkan asesor yang benar-benar berwenang.
class PenilaianDokumenController extends Controller
{
    private function sessionStudentIds(int $examSessionId): \Illuminate\Support\Collection
    {
        return ExamGroup::where('exam_session_id', $examSessionId)->pluck('student_id')->unique();
    }

    private function assignedAsesor(int $examSessionId, int $studentId): ?AsesorAssignment
    {
        return AsesorAssignment::where('exam_session_id', $examSessionId)
            ->where('student_id', $studentId)
            ->with('asesor')
            ->first();
    }

    public function index(int $examSessionId)
    {
        $examSession = ExamSession::with('examPg.classroom', 'examEsai.classroom')->findOrFail($examSessionId);
        $studentIds  = $this->sessionStudentIds($examSessionId);

        $students = Student::whereIn('id', $studentIds)->orderBy('no_participant')->get();

        $applications = AssessmentApplication::whereIn('student_id', $studentIds)
            ->where('exam_session_id', $examSessionId)
            ->with(['documents.requirement', 'classroom.documentRequirements'])
            ->get()
            ->keyBy('student_id');

        $assignments = AsesorAssignment::where('exam_session_id', $examSessionId)
            ->with('asesor:id,name')
            ->get()
            ->keyBy('student_id');

        $rows = $students->map(function ($student) use ($applications, $assignments) {
            $app    = $applications->get($student->id);
            $assign = $assignments->get($student->id);

            $total    = $app?->classroom?->documentRequirements?->count() ?? 0;
            $verified = $app?->documents?->where('asesor_status', 'verified')->count() ?? 0;
            $rejected = $app?->documents?->where('asesor_status', 'rejected')->count() ?? 0;
            $pending  = $app?->documents?->where('asesor_status', 'pending')->count() ?? 0;
            $uploaded = $app?->documents?->count() ?? 0;

            return [
                'student_id'         => $student->id,
                'no_participant'     => $student->no_participant,
                'name'               => $student->name,
                'app_id'             => $app?->id,
                'app_status'         => $app?->status,
                'total_req'          => $total,
                'uploaded'           => $uploaded,
                'verified'           => $verified,
                'rejected'           => $rejected,
                'pending'            => $pending,
                'asesor_verified_at' => $app?->asesor_verified_at,
                'assigned_asesor'    => $assign?->asesor?->name,
            ];
        });

        return inertia('Admin/Penilaian/Dokumen/Index', [
            'exam_session' => $examSession,
            'rows'         => $rows,
        ]);
    }

    public function show(int $examSessionId, int $studentId)
    {
        abort_unless($this->sessionStudentIds($examSessionId)->contains($studentId), 404);

        $examSession = ExamSession::with('examPg.classroom', 'examEsai.classroom')->findOrFail($examSessionId);
        $student     = Student::findOrFail($studentId);

        $application = AssessmentApplication::where('student_id', $studentId)
            ->where('exam_session_id', $examSessionId)
            ->with(['documents.requirement', 'classroom.documentRequirements'])
            ->first();

        $assignment = $this->assignedAsesor($examSessionId, $studentId);

        return inertia('Admin/Penilaian/Dokumen/Show', [
            'exam_session'    => $examSession,
            'student'         => $student,
            'application'     => $application,
            'assigned_asesor' => $assignment?->asesor?->only(['id', 'name', 'signature_path', 'signature_name']),
        ]);
    }

    public function verify(Request $request, int $examSessionId, int $studentId, int $docId)
    {
        abort_unless($this->sessionStudentIds($examSessionId)->contains($studentId), 404);

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
            'asesor_status'         => $request->status,
            'asesor_reviewer_notes' => $request->reviewer_notes,
        ]);

        return back()->with('success', 'Status dokumen berhasil diperbarui.');
    }

    public function download(int $examSessionId, int $studentId, int $docId)
    {
        abort_unless($this->sessionStudentIds($examSessionId)->contains($studentId), 404);

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

    public function finalVerify(int $examSessionId, int $studentId)
    {
        abort_unless($this->sessionStudentIds($examSessionId)->contains($studentId), 404);

        $application = AssessmentApplication::where('student_id', $studentId)
            ->where('exam_session_id', $examSessionId)
            ->firstOrFail();

        abort_if($application->asesor_verified_at, 422, 'Verifikasi akhir sudah ditandatangani sebelumnya.');

        $assignment = $this->assignedAsesor($examSessionId, $studentId);
        abort_if(!$assignment, 422, 'Peserta ini belum memiliki penugasan asesor. Atur di menu Penugasan Asesor.');

        $asesor = $assignment->asesor;
        abort_if(
            !$asesor->signature_path || !Storage::disk('private')->exists($asesor->signature_path),
            422,
            'Asesor yang ditugaskan (' . $asesor->name . ') belum memiliki TTD tersimpan. Atur TTD asesor tersebut di menu Kelola User.'
        );

        $application->update([
            'asesor_verified_by'    => $asesor->id,
            'asesor_verified_at'    => now(),
            'asesor_signature_path' => $asesor->signature_path,
            'asesor_signature_name' => $asesor->signature_name ?: $asesor->name,
        ]);

        return redirect()->route('admin.penilaian.dokumen.index', $examSessionId)
            ->with('success', 'Verifikasi akhir berhasil ditandatangani atas nama ' . $asesor->name . '.');
    }

    public function serveAssignedSignature(int $examSessionId, int $studentId)
    {
        abort_unless($this->sessionStudentIds($examSessionId)->contains($studentId), 404);

        $assignment = $this->assignedAsesor($examSessionId, $studentId);
        $asesor     = $assignment?->asesor;

        abort_if(!$asesor?->signature_path || !Storage::disk('private')->exists($asesor->signature_path), 404);

        return response()->file(Storage::disk('private')->path($asesor->signature_path), [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma'        => 'no-cache',
        ]);
    }
}
