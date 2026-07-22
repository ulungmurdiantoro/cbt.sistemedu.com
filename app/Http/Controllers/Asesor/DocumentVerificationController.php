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
            'auth_asesor'  => auth()->user()->only(['id', 'name', 'signature_path', 'signature_name']),
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

    public function finalVerify(Request $request, int $examSessionId, int $studentId)
    {
        $assignedStudentIds = $this->assignedStudentIds($examSessionId);
        abort_unless($assignedStudentIds->contains($studentId), 403);

        $application = AssessmentApplication::where('student_id', $studentId)
            ->where('exam_session_id', $examSessionId)
            ->firstOrFail();

        abort_if($application->asesor_verified_at, 422, 'Verifikasi akhir sudah ditandatangani sebelumnya.');

        $asesor      = auth()->user();
        $hasSavedSig = $asesor->signature_path && Storage::disk('private')->exists($asesor->signature_path);
        $hasNewSig   = $request->signature_data || $request->hasFile('signature_file');
        $useNewName  = $request->filled('signature_name');

        if (!$hasSavedSig && !$hasNewSig) {
            return back()->withErrors(['signature_data' => 'Tanda tangan wajib diisi (gambar atau upload).']);
        }
        if (!$hasSavedSig && !$useNewName) {
            return back()->withErrors(['signature_name' => 'Nama penandatangan wajib diisi.']);
        }

        $request->validate([
            'signature_name' => 'nullable|string|max:255',
            'signature_data' => 'nullable|string',
            'signature_file' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
        ]);

        if ($hasNewSig) {
            $sigPath = $this->storeAsesorSignature($request, $application);
            $sigName = $request->signature_name ?: $asesor->signature_name ?: $asesor->name;
            $asesor->update(['signature_path' => $sigPath, 'signature_name' => $sigName]);
        } else {
            $sigPath = $asesor->signature_path;
            $sigName = $request->signature_name ?: $asesor->signature_name ?: $asesor->name;
            if ($useNewName && $sigName !== $asesor->signature_name) {
                $asesor->update(['signature_name' => $sigName]);
            }
        }

        $application->update([
            'asesor_verified_by'    => $asesor->id,
            'asesor_verified_at'    => now(),
            'asesor_signature_path' => $sigPath,
            'asesor_signature_name' => $sigName,
        ]);

        return back()->with('success', 'Verifikasi akhir berhasil ditandatangani.');
    }

    public function serveFinalSignature(int $examSessionId, int $studentId)
    {
        $assignedStudentIds = $this->assignedStudentIds($examSessionId);
        abort_unless($assignedStudentIds->contains($studentId), 403);

        $application = AssessmentApplication::where('student_id', $studentId)
            ->where('exam_session_id', $examSessionId)
            ->firstOrFail();

        abort_if(
            !$application->asesor_signature_path || !Storage::disk('private')->exists($application->asesor_signature_path),
            404
        );

        return response()->file(Storage::disk('private')->path($application->asesor_signature_path));
    }

    public function serveDefaultSignature()
    {
        $user = auth()->user();
        abort_if(!$user->signature_path || !Storage::disk('private')->exists($user->signature_path), 404);

        return response()->file(Storage::disk('private')->path($user->signature_path));
    }

    private function storeAsesorSignature(Request $request, AssessmentApplication $application): string
    {
        $disk = Storage::disk('private');
        $dir  = 'asesor-signatures/' . $application->id;
        $now  = now()->format('YmdHis');

        if ($request->hasFile('signature_file')) {
            $ext  = $request->file('signature_file')->getClientOriginalExtension() ?: 'png';
            $path = $dir . '/asesor_' . $now . '.' . strtolower($ext);
            $disk->put($path, file_get_contents($request->file('signature_file')->getRealPath()));
            return $path;
        }

        $data = $request->signature_data;
        if (preg_match('/^data:image\/(png|jpe?g);base64,(.+)$/', $data, $m)) {
            $ext     = $m[1] === 'jpg' ? 'jpeg' : $m[1];
            $decoded = base64_decode($m[2]);
            $path    = $dir . '/asesor_' . $now . '.' . $ext;
            $disk->put($path, $decoded);
            return $path;
        }

        abort(422, 'Format tanda tangan tidak valid.');
    }
}
