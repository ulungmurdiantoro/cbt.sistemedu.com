<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\ApplicationDocument;
use App\Models\AsesorAssignment;
use App\Models\AssessmentApplication;
use App\Models\ExamGroup;
use App\Models\ExamSession;
use App\Models\Student;
use Illuminate\Support\Facades\Storage;

// Versi Pengambil Keputusan (lihat saja) dari Admin\PenilaianDokumenController —
// tidak ada tombol verifikasi/tolak per dokumen atau Verifikasi Akhir di sini,
// itu tetap wewenang admin/asesor. Manager hanya meninjau berkas yang sudah ada.
class DokumenController extends Controller
{
    private function sessionStudentIds(int $examSessionId): \Illuminate\Support\Collection
    {
        return ExamGroup::where('exam_session_id', $examSessionId)->pluck('student_id')->unique();
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

        $assignment = AsesorAssignment::where('exam_session_id', $examSessionId)
            ->where('student_id', $studentId)
            ->with('asesor:id,name')
            ->first();

        return inertia('Manager/Dokumen/Show', [
            'exam_session'    => $examSession,
            'student'         => $student,
            'application'     => $application,
            'assigned_asesor' => $assignment?->asesor?->name,
        ]);
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

        // Preview inline (bukan paksa unduh) — hindari file menumpuk di folder Downloads reviewer.
        return Storage::disk('private')->response($doc->file_path, $doc->original_filename);
    }
}
