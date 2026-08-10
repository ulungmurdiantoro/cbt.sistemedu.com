<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApplicationStatus;
use App\Exports\ApplicationsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\RejectApplicationRequest;
use App\Http\Requests\VerifyDocumentRequest;
use App\Models\Answer;
use App\Models\AnswerEssay;
use App\Models\AssessmentApplication;
use App\Models\Classroom;
use App\Models\ExamGroup;
use App\Models\ExamSession;
use App\Models\Grade;
use App\Models\InitialAssessment;
use App\Models\Student;
use App\Services\DocumentGeneratorService;
use App\Support\InitialAssessmentRubric;
use App\Support\SignatureImageProcessor;
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
use Maatwebsite\Excel\Facades\Excel;

class ApplicationController extends Controller
{
    private function filteredQuery(Request $request)
    {
        return AssessmentApplication::with(['participant', 'classroom', 'examSession', 'student'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->classroom_id, fn($q) => $q->where('classroom_id', $request->classroom_id))
            ->when($request->kode_batch, fn($q) => $q->where('kode_batch', $request->kode_batch))
            ->when($request->q, fn($q) => $q->whereHas('participant', function ($sub) use ($request) {
                $sub->where('name', 'like', '%' . $request->q . '%')
                    ->orWhere('email', 'like', '%' . $request->q . '%');
            }));
    }

    public function index(Request $request)
    {
        $applications = $this->filteredQuery($request)
            ->withCount(['documents as rejected_documents_count' => fn($q) => $q->where('status', 'rejected')])
            ->orderByRaw("status = 'submitted' DESC")
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return inertia('Admin/Applications/Index', [
            'applications' => $applications,
            'filters'      => $request->only('status', 'classroom_id', 'kode_batch', 'q'),
            'classrooms'   => Classroom::orderBy('title')->get(['id', 'title']),
        ]);
    }

    public function export(Request $request)
    {
        $applications = $this->filteredQuery($request)->latest()->get();

        $filenameParts = array_filter([
            'permohonan',
            $request->classroom_id ? Classroom::find($request->classroom_id)?->classrooms_code : null,
            $request->kode_batch ? 'batch' . $request->kode_batch : null,
            now()->format('Ymd_His'),
        ]);

        return Excel::download(new ApplicationsExport($applications), Str::slug(implode('_', $filenameParts)) . '.xlsx');
    }

    public function exportDokumen(Request $request)
    {
        abort_if(!$request->classroom_id, 422, 'Pilih skema terlebih dahulu untuk export dokumen.');

        $applications = $this->filteredQuery($request)
            ->with(['participant', 'classroom.documentRequirements', 'documents', 'examSession', 'approver', 'asesorVerifier', 'initialAssessment.assessor'])
            ->orderBy('id')
            ->get();

        abort_if($applications->isEmpty(), 422, 'Tidak ada permohonan yang cocok dengan filter.');

        $generator = app(DocumentGeneratorService::class);

        $tmpDir = storage_path('app/tmp');
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }
        $zipPath = $tmpDir . '/export_dokumen_' . Str::random(12) . '.zip';

        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        foreach ($applications as $i => $app) {
            $no         = str_pad($i + 1, 2, '0', STR_PAD_LEFT);
            $nama       = $app->participant?->name ?? ('Peserta ' . $no);
            $folderName = str_replace(['/', '\\'], '-', $no . '. ' . $nama);

            foreach ($app->documents as $doc) {
                if (!Storage::disk('private')->exists($doc->file_path)) {
                    continue;
                }
                $zip->addFile(
                    Storage::disk('private')->path($doc->file_path),
                    "Dokumen Persyaratan Peserta/{$folderName}/{$doc->original_filename}"
                );
            }

            $zip->addFromString(
                "FR.APL.01 Permohonan Sertifikasi/Versi Pdf/{$folderName} - FR.APL.01 Permohonan Sertifikasi.pdf",
                $generator->generateFrApl01($app)
            );

            $zip->addFromString(
                "FR.AK.01 Persetujuan Asesmen & Kerahasiaan/Versi Pdf/{$folderName} - FR.AK.01 Persetujuan Asesmen & Kerahasiaan.pdf",
                $generator->generateFrAk01($app)
            );

            if ($app->initialAssessment) {
                $pdf = $generator->generateFrApl03($app);
                $zip->addFromString(
                    "FR.APL.03 Standar Kriteria dan Penilaian Awal Pemohon/Versi Pdf/{$folderName} - FR.APL.03 Standar Kriteria dan Penilaian Awal Pemohon.pdf",
                    $pdf
                );
            }
        }

        $zip->close();

        $zipNameParts = array_filter([
            'export_dokumen',
            Classroom::find($request->classroom_id)?->classrooms_code,
            $request->kode_batch ? 'batch' . $request->kode_batch : null,
        ]);

        $zipName = Str::slug(implode('_', $zipNameParts)) . '.zip';

        return response()->download($zipPath, $zipName)->deleteFileAfterSend(true);
    }

    public function downloadFrApl03(AssessmentApplication $application)
    {
        $pdf = app(DocumentGeneratorService::class)->generateFrApl03($application);
        return $this->pdfDownloadResponse($pdf, $application, 'FR.APL.03 Standar Kriteria dan Penilaian Awal Pemohon');
    }

    public function downloadFrApl01(AssessmentApplication $application)
    {
        $pdf = app(DocumentGeneratorService::class)->generateFrApl01($application);
        return $this->pdfDownloadResponse($pdf, $application, 'FR.APL.01 Permohonan Sertifikasi');
    }

    public function downloadFrAk01(AssessmentApplication $application)
    {
        $pdf = app(DocumentGeneratorService::class)->generateFrAk01($application);
        return $this->pdfDownloadResponse($pdf, $application, 'FR.AK.01 Persetujuan Asesmen & Kerahasiaan');
    }

    private function pdfDownloadResponse(string $pdf, AssessmentApplication $application, string $docLabel)
    {
        $application->loadMissing('participant');
        $nama     = $application->participant?->name ?? 'Peserta';
        $filename = $nama . ' - ' . $docLabel . '.pdf';

        return response($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . str_replace('"', '', $filename) . '"',
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
            'initialAssessment.assessor',
        ]);

        $admin = auth()->user();
        $admin->makeVisible(['signature_path', 'signature_name']);

        $otherSessions = ExamSession::where(function ($q) use ($application) {
                $q->whereHas('examPg', fn($q2) => $q2->where('classroom_id', $application->classroom_id))
                  ->orWhereHas('examEsai', fn($q2) => $q2->where('classroom_id', $application->classroom_id));
            })
            ->where('id', '!=', $application->exam_session_id)
            ->orderByDesc('start_time')
            ->get(['id', 'title', 'kode_batch', 'start_time', 'end_time']);

        return inertia('Admin/Applications/Show', [
            'application'               => $application,
            'auth_admin'                => $admin->only(['id', 'name', 'signature_path', 'signature_name']),
            'other_sessions'            => $otherSessions,
            'initial_assessment_rubric' => InitialAssessmentRubric::for($application->classroom_id),
        ]);
    }

    public function saveInitialAssessment(Request $request, AssessmentApplication $application)
    {
        $request->validate([
            'answers' => 'required|array',
        ]);

        $rubric     = InitialAssessmentRubric::for($application->classroom_id);
        $totalScore = InitialAssessmentRubric::score($rubric, $request->answers);
        $isEligible = $totalScore >= $rubric['threshold'];

        InitialAssessment::updateOrCreate(
            ['assessment_application_id' => $application->id],
            [
                'classroom_id' => $application->classroom_id,
                'answers'      => $request->answers,
                'total_score'  => $totalScore,
                'threshold'    => $rubric['threshold'],
                'is_eligible'  => $isEligible,
                'assessed_by'  => auth()->id(),
                'assessed_at'  => now(),
            ]
        );

        return back()->with('success', 'Penilaian awal kelayakan berhasil disimpan.');
    }

    public function approve(Request $request, AssessmentApplication $application)
    {
        abort_if(!$application->isSubmitted(), 422, 'Hanya permohonan berstatus submitted yang dapat disetujui.');

        $assessment = $application->initialAssessment;
        abort_if(!$assessment, 422, 'Penilaian awal kelayakan (FR.APL.03) belum diisi.');
        abort_if(!$assessment->is_eligible, 422, 'Pemohon belum memenuhi ambang batas nilai penilaian awal — harus mengikuti training terlebih dahulu.');

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

    public function changeBatch(Request $request, AssessmentApplication $application)
    {
        $request->validate([
            'exam_session_id' => 'required|exists:exam_sessions,id',
        ]);

        abort_if($request->exam_session_id == $application->exam_session_id, 422, 'Peserta sudah berada di batch ini.');

        $newSession = ExamSession::with('examPg', 'examEsai')->findOrFail($request->exam_session_id);

        abort_if($newSession->referenceExam?->classroom_id !== $application->classroom_id, 422, 'Sesi yang dipilih bukan untuk skema yang sama.');

        $oldSession = ExamSession::find($application->exam_session_id);
        $oldExamIds = array_filter([$oldSession?->exam_id_pg, $oldSession?->exam_id_esai]);

        // Kalau peserta sudah punya akun ujian (approved) dan sudah mulai mengerjakan
        // (ada nilai/jawaban tersimpan di batch lama), batch tidak boleh dipindah otomatis
        // supaya data hasil ujian tidak jadi yatim/tidak konsisten.
        if ($application->student_id && $oldExamIds) {
            $hasActivity = Grade::where('student_id', $application->student_id)
                    ->where('exam_session_id', $application->exam_session_id)
                    ->whereIn('exam_id', $oldExamIds)
                    ->exists()
                || Answer::where('student_id', $application->student_id)
                    ->where('exam_session_id', $application->exam_session_id)
                    ->whereIn('exam_id', $oldExamIds)
                    ->exists()
                || AnswerEssay::where('student_id', $application->student_id)
                    ->where('exam_session_id', $application->exam_session_id)
                    ->whereIn('exam_id', $oldExamIds)
                    ->exists();

            abort_if($hasActivity, 422, 'Peserta sudah memiliki jawaban/nilai tersimpan di batch saat ini, tidak bisa dipindahkan otomatis.');
        }

        DB::transaction(function () use ($application, $newSession) {
            if ($application->student_id) {
                ExamGroup::where('exam_session_id', $application->exam_session_id)
                    ->where('student_id', $application->student_id)
                    ->delete();

                $examIds = array_filter([$newSession->exam_id_pg, $newSession->exam_id_esai]);
                $firstExamGroup = null;
                foreach ($examIds as $examId) {
                    $eg = ExamGroup::create([
                        'exam_groups_code' => 'EG-' . strtoupper(Str::random(8)),
                        'exam_id'          => $examId,
                        'exam_session_id'  => $newSession->id,
                        'student_id'       => $application->student_id,
                    ]);
                    $firstExamGroup ??= $eg;
                }
                $application->exam_group_id = $firstExamGroup?->id;
            }

            $application->exam_session_id = $newSession->id;
            $application->konteks_asesmen = $newSession->konteks_asesmen;
            $application->tempat_ujian    = $newSession->tempat_ujian;
            $application->kode_batch      = $newSession->kode_batch ?? '-';
            $application->save();
        });

        return back()->with('success', 'Batch peserta berhasil dipindahkan ke ' . $newSession->title . ' (Batch ' . $newSession->kode_batch . ').');
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
        $disk = Storage::disk('private');
        $dir  = 'admin-signatures/' . $application->id;
        $now  = now()->format('YmdHis');
        $path = $dir . '/admin_' . $now . '.png';

        if ($request->hasFile('admin_signature_file')) {
            $raw = file_get_contents($request->file('admin_signature_file')->getRealPath());
            $disk->put($path, SignatureImageProcessor::removeBackground($raw));
            return $path;
        }

        // Format data URL: "data:image/png;base64,iVBORw0..."
        $data = $request->admin_signature_data;
        if (preg_match('/^data:image\/(png|jpe?g);base64,(.+)$/', $data, $m)) {
            $decoded = base64_decode($m[2]);
            $disk->put($path, SignatureImageProcessor::removeBackground($decoded));
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
        $prefix    = $kode . '.' . $batch . '.' . $year . '.';

        // Ambil nomor urut tertinggi yang SUDAH benar-benar dipakai untuk kombinasi
        // kode+batch+tahun ini, baru +1 — jangan hitung jumlah permohonan (bisa bentrok
        // kalau ada permohonan yang batch-nya berubah, ditolak lalu direset, dst).
        $lastNumber = Student::where('no_participant', 'like', $prefix . '%')
            ->get(['no_participant'])
            ->map(fn ($s) => (int) substr($s->no_participant, strlen($prefix)))
            ->max() ?? 0;

        $next = $lastNumber + 1;

        // Pengaman tambahan kalau masih bentrok (mis. ada nomor yang diinput manual).
        while (Student::where('no_participant', $prefix . str_pad($next, 5, '0', STR_PAD_LEFT))->exists()) {
            $next++;
        }

        return $prefix . str_pad($next, 5, '0', STR_PAD_LEFT);
    }
}
