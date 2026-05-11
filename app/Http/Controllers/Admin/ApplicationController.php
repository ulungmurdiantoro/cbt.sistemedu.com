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
            'examSession.exam',
            'student',
            'approver',
            'documents.requirement',
            'reissueLogs.oldStudent',
            'reissueLogs.newStudent',
            'reissueLogs.reissuedBy',
        ]);

        return inertia('Admin/Applications/Show', [
            'application' => $application,
        ]);
    }

    public function approve(AssessmentApplication $application)
    {
        abort_if(!$application->isSubmitted(), 422, 'Hanya permohonan berstatus submitted yang dapat disetujui.');

        DB::transaction(function () use ($application) {
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

            // buat exam_group untuk sesi ini (selalu baru)
            $examGroup = ExamGroup::create([
                'exam_groups_code' => 'EG-' . strtoupper(Str::random(8)),
                'exam_id'          => $application->examSession->exam_id,
                'exam_session_id'  => $application->exam_session_id,
                'student_id'       => $student->id,
            ]);

            $application->update([
                'student_id'    => $student->id,
                'exam_group_id' => $examGroup->id,
                'status'        => ApplicationStatus::Approved,
                'approved_at'   => now(),
                'approved_by'   => auth()->id(),
                'admin_notes'   => null,
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

            // exam_group baru
            $newExamGroup = ExamGroup::create([
                'exam_groups_code' => 'EG-' . strtoupper(Str::random(8)),
                'exam_id'          => $application->examSession->exam_id,
                'exam_session_id'  => $application->exam_session_id,
                'student_id'       => $newStudent->id,
            ]);

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
                'exam_group_id' => $newExamGroup->id,
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

        return back()->with('success', 'Status dokumen diperbarui.');
    }

    public function downloadDocument(AssessmentApplication $application, ApplicationDocument $document)
    {
        abort_if($document->assessment_application_id !== $application->id, 403);
        abort_if(!Storage::disk('private')->exists($document->file_path), 404);

        return response()->download(Storage::disk('private')->path($document->file_path), $document->original_filename);
    }

    public function serveSignature(AssessmentApplication $application, string $type)
    {
        $path = match ($type) {
            'form'  => $application->signature_form_path,
            'pakta' => $application->signature_path,
            default => null,
        };

        abort_if(!$path || !Storage::disk('private')->exists($path), 404);

        return response()->file(Storage::disk('private')->path($path));
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
