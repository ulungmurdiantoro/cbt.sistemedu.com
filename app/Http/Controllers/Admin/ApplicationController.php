<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\RejectApplicationRequest;
use App\Http\Requests\VerifyDocumentRequest;
use App\Models\AssessmentApplication;
use App\Models\ExamGroup;
use App\Models\Student;
use App\Models\StudentReissueLog;
use Illuminate\Http\Request;
use App\Mail\ApplicationApprovedMail;
use App\Mail\ApplicationRejectedMail;
use App\Mail\DocumentRejectedMail;
use App\Models\ApplicationDocument;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ApplicationController extends Controller
{
    public function index(Request $request)
    {
        $applications = AssessmentApplication::with(['participant', 'classroom', 'examSession', 'student'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->classroom_id, fn($q) => $q->where('classroom_id', $request->classroom_id))
            ->when($request->q, fn($q) => $q->whereHas('participant', function ($sub) use ($request) {
                $sub->where('name', 'like', '%' . $request->q . '%')
                    ->orWhere('email', 'like', '%' . $request->q . '%');
            }))
            ->orderByRaw("status = 'submitted' DESC")
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return inertia('Admin/Applications/Index', [
            'applications' => $applications,
            'filters'      => $request->only('status', 'classroom_id', 'q'),
        ]);
    }

    public function show(AssessmentApplication $application)
    {
        $application->load([
            'participant',
            'classroom.documentRequirements',
            'examSession.examPg',
            'examSession.examEsai',
            'student',
            'approver',
            'documents.requirement',
            'reissueLogs.oldStudent',
            'reissueLogs.newStudent',
            'reissueLogs.reissuedBy',
        ]);

        $admin = auth()->user();
        $admin->makeVisible(['signature_path', 'signature_name']);

        return inertia('Admin/Applications/Show', [
            'application' => $application,
            'auth_admin'  => $admin->only(['id', 'name', 'signature_path', 'signature_name']),
        ]);
    }

    public function approve(Request $request, AssessmentApplication $application)
    {
        abort_if(!$application->isSubmitted(), 422, 'Hanya permohonan berstatus submitted yang dapat disetujui.');

        $admin         = auth()->user();
        $hasSavedSig   = $admin->signature_path && Storage::disk('private')->exists($admin->signature_path);
        $hasNewSig     = $request->admin_signature_data || $request->hasFile('admin_signature_file');
        $useNewName    = $request->filled('admin_signature_name');

        // Jika admin belum punya TTD tersimpan, wajib input baru
        if (!$hasSavedSig && !$hasNewSig) {
            return back()->withErrors(['admin_signature_data' => 'Tanda tangan wajib diisi (gambar atau upload).']);
        }
        if (!$hasSavedSig && !$useNewName) {
            return back()->withErrors(['admin_signature_name' => 'Nama penandatangan wajib diisi.']);
        }

        $request->validate([
            'admin_signature_name' => 'nullable|string|max:255',
            'admin_signature_data' => 'nullable|string',
            'admin_signature_file' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
        ]);

        // Pakai TTD lama jika tidak ada input baru
        if ($hasNewSig) {
            $sigPath  = $this->storeAdminSignature($request, $application);
            $sigName  = $request->admin_signature_name ?: $admin->signature_name ?: $admin->name;
            // Simpan TTD ini sebagai default admin (akan dipakai di approve berikutnya)
            $admin->update(['signature_path' => $sigPath, 'signature_name' => $sigName]);
        } else {
            // Reuse TTD default yang sudah tersimpan
            $sigPath = $admin->signature_path;
            $sigName = $request->admin_signature_name ?: $admin->signature_name ?: $admin->name;
            if ($useNewName && $sigName !== $admin->signature_name) {
                $admin->update(['signature_name' => $sigName]);
            }
        }

        DB::transaction(function () use ($application, $sigPath, $sigName) {
            $participant = $application->participant;

            // cek apakah sudah ada student aktif untuk participant + classroom yang sama
            // (misal: skema sama, sesi berbeda → reuse student yang sama)
            $student = Student::where('participant_id', $participant->id)
                ->where('classroom_id', $application->classroom_id)
                ->where('is_active', true)
                ->first();

            if (!$student) {
                $student = Student::create([
                    'participant_id' => $participant->id,
                    'classroom_id'   => $application->classroom_id,
                    'no_participant' => $this->generateNoParticipant($application),
                    'name'           => $participant->name,
                    'position'       => $participant->jabatan ?? '-',
                    'institution'    => $participant->institusi ?? '-',
                    'gender'         => $participant->jenis_kelamin ?? 'L',
                    'is_active'      => true,
                ]);
            }

            // buat exam_group untuk semua ujian di sesi ini (PG dan/atau Esai)
            $examIds = array_filter([
                $application->examSession->exam_id_pg,
                $application->examSession->exam_id_esai,
            ]);

            $firstExamGroup = null;
            foreach ($examIds as $examId) {
                $eg = ExamGroup::create([
                    'exam_groups_code' => 'EG-' . strtoupper(Str::random(8)),
                    'exam_id'          => $examId,
                    'exam_session_id'  => $application->exam_session_id,
                    'student_id'       => $student->id,
                ]);
                $firstExamGroup ??= $eg;
            }
            $examGroup = $firstExamGroup;

            $application->update([
                'student_id'           => $student->id,
                'exam_group_id'        => $examGroup?->id,
                'status'               => ApplicationStatus::Approved,
                'approved_at'          => now(),
                'approved_by'          => auth()->id(),
                'admin_notes'          => null,
                'admin_signature_path' => $sigPath,
                'admin_signature_name' => $sigName,
            ]);
        });

        try {
            $application->load(['participant', 'classroom', 'examSession', 'student']);
            Mail::to($application->participant->email)->send(new ApplicationApprovedMail($application));
        } catch (\Exception) {
            // email gagal, tidak menghentikan alur
        }

        return back()->with('success', 'Permohonan disetujui. Akun ujian berhasil dibuat.');
    }

    public function reject(RejectApplicationRequest $request, AssessmentApplication $application)
    {
        abort_if(!$application->isSubmitted(), 422, 'Hanya permohonan berstatus submitted yang dapat ditolak.');

        $application->update([
            'status'      => ApplicationStatus::Rejected,
            'admin_notes' => $request->admin_notes,
        ]);

        try {
            $application->load(['participant', 'classroom']);
            Mail::to($application->participant->email)->send(new ApplicationRejectedMail($application));
        } catch (\Exception) {
            // email gagal, tidak menghentikan alur
        }

        return back()->with('success', 'Permohonan ditolak. Peserta akan diberitahu.');
    }

    public function reissueStudent(Request $request, AssessmentApplication $application)
    {
        abort_if(!$application->isApproved(), 422, 'Hanya permohonan yang sudah disetujui yang dapat di-reissue.');

        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($application, $request) {
            $oldStudent = $application->student;

            // nonaktifkan student lama
            if ($oldStudent) {
                $oldStudent->update(['is_active' => false]);
            }

            $participant   = $application->participant;
            $noParticipant = $this->generateNoParticipant($application);

            // student baru
            $newStudent = Student::create([
                'participant_id' => $participant->id,
                'classroom_id'   => $application->classroom_id,
                'no_participant' => $noParticipant,
                'name'           => $participant->name,
                'position'       => $participant->jabatan ?? '-',
                'institution'    => $participant->institusi ?? '-',
                'gender'         => $participant->jenis_kelamin ?? 'L',
                'is_active'      => true,
            ]);

            // exam_group baru untuk semua ujian di sesi ini (PG dan/atau Esai)
            $examIds = array_filter([
                $application->examSession->exam_id_pg,
                $application->examSession->exam_id_esai,
            ]);

            $firstExamGroup = null;
            foreach ($examIds as $examId) {
                $eg = ExamGroup::create([
                    'exam_groups_code' => 'EG-' . strtoupper(Str::random(8)),
                    'exam_id'          => $examId,
                    'exam_session_id'  => $application->exam_session_id,
                    'student_id'       => $newStudent->id,
                ]);
                $firstExamGroup ??= $eg;
            }
            $newExamGroup = $firstExamGroup;

            // log reissue
            StudentReissueLog::create([
                'assessment_application_id' => $application->id,
                'old_student_id'            => $oldStudent?->id,
                'new_student_id'            => $newStudent->id,
                'reason'                    => $request->reason,
                'reissued_by'               => auth()->id(),
            ]);

            $application->update([
                'student_id'    => $newStudent->id,
                'exam_group_id' => $newExamGroup?->id,
            ]);
        });

        return back()->with('success', 'Akun ujian baru berhasil dibuat.');
    }

    public function verifyDocument(VerifyDocumentRequest $request, AssessmentApplication $application, int $docId)
    {
        $doc = $application->documents()->findOrFail($docId);

        $doc->update([
            'status'         => $request->status,
            'reviewer_notes' => $request->reviewer_notes,
        ]);

        // Notifikasi email hanya saat dokumen DITOLAK. Verifikasi (verified) tidak perlu notifikasi.
        if ($request->status === 'rejected') {
            try {
                $application->loadMissing('participant', 'classroom');
                $doc->loadMissing('requirement');
                Mail::to($application->participant->email)->send(new DocumentRejectedMail($application, $doc));
            } catch (\Exception) {
                // email gagal, tidak menghentikan alur
            }
        }

        return back()->with('success', 'Status dokumen diperbarui.');
    }

    public function downloadDocument(AssessmentApplication $application, ApplicationDocument $document)
    {
        abort_if($document->assessment_application_id !== $application->id, 403);
        abort_if(!Storage::disk('private')->exists($document->file_path), 404);

        return response()->download(Storage::disk('private')->path($document->file_path), $document->original_filename);
    }

    public function previewDocument(AssessmentApplication $application, ApplicationDocument $document)
    {
        abort_if($document->assessment_application_id !== $application->id, 403);
        abort_if(!Storage::disk('private')->exists($document->file_path), 404);

        $headers = ['Content-Disposition' => 'inline; filename="' . $document->original_filename . '"'];
        if ($document->mime_type) {
            $headers['Content-Type'] = $document->mime_type;
        }

        return response()->file(Storage::disk('private')->path($document->file_path), $headers);
    }

    public function serveSignature(AssessmentApplication $application, string $type)
    {
        $path = match ($type) {
            'form'  => $application->signature_form_path,
            'pakta' => $application->signature_path,
            'admin' => $application->admin_signature_path,
            default => null,
        };

        abort_if(!$path || !Storage::disk('private')->exists($path), 404);

        return response()->file(Storage::disk('private')->path($path));
    }

    public function serveAdminDefaultSignature()
    {
        $user = auth()->user();
        abort_if(!$user->signature_path || !Storage::disk('private')->exists($user->signature_path), 404);
        return response()->file(Storage::disk('private')->path($user->signature_path));
    }

    /**
     * Simpan TTD admin dari base64 data-URL atau uploaded file ke disk privat.
     */
    private function storeAdminSignature(Request $request, AssessmentApplication $application): string
    {
        $disk    = Storage::disk('private');
        $dir     = 'admin-signatures/' . $application->id;
        $now     = now()->format('YmdHis');

        if ($request->hasFile('admin_signature_file')) {
            $ext  = $request->file('admin_signature_file')->getClientOriginalExtension() ?: 'png';
            $path = $dir . '/admin_' . $now . '.' . strtolower($ext);
            $disk->put($path, file_get_contents($request->file('admin_signature_file')->getRealPath()));
            return $path;
        }

        // Format data URL: "data:image/png;base64,iVBORw0..."
        $data = $request->admin_signature_data;
        if (preg_match('/^data:image\/(png|jpe?g);base64,(.+)$/', $data, $m)) {
            $ext     = $m[1] === 'jpg' ? 'jpeg' : $m[1];
            $decoded = base64_decode($m[2]);
            $path    = $dir . '/admin_' . $now . '.' . $ext;
            $disk->put($path, $decoded);
            return $path;
        }

        abort(422, 'Format tanda tangan tidak valid.');
    }

    private function generateNoParticipant(AssessmentApplication $application): string
    {
        $kodeSkema = $application->classroom->kode_skema ?? '';
        $kode      = substr($kodeSkema, 7, 3) ?: 'SKM';
        $batch     = $application->kode_batch ?? '-';
        $year      = now()->year;

        // counter reset per batch: hitung permohonan yang sudah approved di classroom + batch yang sama
        $count = AssessmentApplication::where('classroom_id', $application->classroom_id)
            ->where('kode_batch', $batch)
            ->whereNotNull('student_id')
            ->count() + 1;

        return $kode . '.' . $batch . '.' . $year . '.' . str_pad($count, 5, '0', STR_PAD_LEFT);
    }
}
