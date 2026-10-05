<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApplicationStatus;
use App\Exports\ApplicationsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\RejectApplicationRequest;
use App\Http\Requests\VerifyDocumentRequest;
use App\Models\AssessmentApplication;
use App\Models\Classroom;
use App\Models\ExamSession;
use App\Models\InitialAssessment;
use App\Services\DocumentGeneratorService;
use App\Services\StudentEnrollmentService;
use App\Support\InitialAssessmentRubric;
use App\Support\SignatureImageProcessor;
use Illuminate\Http\Request;
use App\Mail\ApplicationApprovedMail;
use App\Mail\ApplicationRejectedMail;
use App\Mail\DocumentRejectedMail;
use App\Models\ApplicationDocument;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class ApplicationController extends Controller
{
    private function filteredQuery(Request $request)
    {
        return AssessmentApplication::with(['participant', 'classroom', 'examSession', 'student'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->classroom_id, fn($q) => $q->where('classroom_id', $request->classroom_id))
            ->when($request->kode_batch, fn($q) => $q->where('kode_batch', $request->kode_batch))
            ->when($request->integer('exam_session_id'), fn($q, $sessionId) => $q->where('exam_session_id', $sessionId))
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
            'filters'      => $request->only('status', 'classroom_id', 'kode_batch', 'q', 'exam_session_id'),
            'classrooms'   => Classroom::orderBy('title')->get(['id', 'title']),
            // Dibuka dari tab sesi → tampilkan navigasi sesi di atas daftar
            'exam_session' => $this->filterSession($request)?->only('id', 'title', 'kode_batch', 'verifikasi_tuk'),
        ]);
    }

    private function filterSession(Request $request): ?ExamSession
    {
        $sessionId = $request->integer('exam_session_id');

        return $sessionId ? ExamSession::with('examPg.classroom', 'examEsai.classroom')->find($sessionId) : null;
    }

    /** Bagian nama file export: kode skema + batch, dari filter atau dari sesi yang difilter. */
    private function exportNameParts(Request $request): array
    {
        $session = $this->filterSession($request);

        $classroomCode = $request->classroom_id
            ? Classroom::find($request->classroom_id)?->classrooms_code
            : $session?->referenceExam?->classroom?->classrooms_code;

        $batch = $request->kode_batch ?: $session?->kode_batch;

        return array_filter([$classroomCode, $batch ? 'batch' . $batch : null]);
    }

    public function export(Request $request)
    {
        $applications = $this->filteredQuery($request)->latest()->get();

        $filenameParts = ['permohonan', ...$this->exportNameParts($request), now()->format('Ymd_His')];

        return Excel::download(new ApplicationsExport($applications), Str::slug(implode('_', $filenameParts)) . '.xlsx');
    }

    public function exportDokumen(Request $request)
    {
        abort_if(!$request->classroom_id && !$request->integer('exam_session_id'), 422, 'Pilih skema atau sesi terlebih dahulu untuk export dokumen.');

        $applications = $this->filteredQuery($request)
            ->with(['participant', 'classroom.documentRequirements', 'documents', 'examSession', 'approver', 'asesorVerifier', 'initialAssessment.assessor'])
            ->orderBy('id')
            ->get();

        abort_if($applications->isEmpty(), 422, 'Tidak ada permohonan yang cocok dengan filter.');

        $zipPath = app(DocumentGeneratorService::class)->applicationsZip($applications);

        $zipNameParts = ['export_dokumen', ...$this->exportNameParts($request)];

        $zipName = Str::slug(implode('_', $zipNameParts)) . '.zip';

        $response = response()->download($zipPath, $zipName)->deleteFileAfterSend(true);

        // Generate ZIP butuh waktu lama (mPDF per peserta) — halaman admin memakai
        // cookie ini sebagai sinyal "server sudah selesai" untuk menghentikan
        // indikator loading, karena unduhan file lewat navigasi biasa (bukan XHR)
        // tidak punya event "selesai" yang bisa didengar langsung dari JS.
        // response()->download() mengembalikan BinaryFileResponse (Symfony) yang
        // tidak punya helper withCookie() milik Laravel — pakai Cookie::queue()
        // supaya tetap ditempel oleh middleware AddQueuedCookiesToResponse.
        if ($request->filled('download_token') && preg_match('/^[A-Za-z0-9_-]{1,64}$/', $request->download_token)) {
            \Illuminate\Support\Facades\Cookie::queue(
                cookie('fileDownloadToken', $request->download_token, 1, null, null, false, false)
            );
        }

        return $response;
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
            // Preview inline (bukan paksa unduh) — dokumen langsung tampil di tab baru.
            'Content-Disposition' => 'inline; filename="' . str_replace('"', '', $filename) . '"',
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

    public function approve(Request $request, AssessmentApplication $application, StudentEnrollmentService $enrollment)
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

        DB::transaction(function () use ($application, $sigPath, $sigName, $enrollment) {
            $student   = $enrollment->findOrCreateStudent($application);
            $examGroup = $enrollment->enroll($student->id, $application->examSession);

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

        // Meterai FR.AK.01 baru dibubuhkan setelah ketiga tanda tangan lengkap
        // (Asesi + LSP/admin + Asesor) — lihat AssessmentApplication::maybeTriggerAk01Stamping().
        $application->maybeTriggerAk01Stamping();

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

    /**
     * Hapus permohonan (draft / submitted / rejected). Permohonan yang sudah
     * DISETUJUI tidak bisa dihapus di sini — sudah punya akun ujian, enrollment,
     * kemungkinan nilai & materai; batalkan lewat jalur lain kalau memang perlu.
     */
    public function destroy(AssessmentApplication $application)
    {
        if ($application->isApproved()) {
            throw ValidationException::withMessages([
                'delete' => 'Permohonan yang sudah disetujui tidak bisa dihapus. Sudah ada akun ujian & data terkait.',
            ]);
        }

        DB::transaction(function () use ($application) {
            // Hapus berkas fisik; baris DB (application_documents, initial_assessments)
            // ikut terhapus otomatis lewat cascade foreign key.
            foreach ($application->documents as $doc) {
                if ($doc->file_path) {
                    Storage::disk('private')->delete($doc->file_path);
                }
            }
            foreach (['signature_form_path', 'signature_path'] as $sigCol) {
                if ($application->{$sigCol}) {
                    Storage::disk('private')->delete($application->{$sigCol});
                }
            }

            $application->delete();
        });

        // Dihapus dari tab Permohonan sesi (daftar ?exam_session_id= atau detail ?sesi=)
        // → kembali ke daftar sesi itu, bukan ke semua permohonan.
        parse_str(parse_url(url()->previous(), PHP_URL_QUERY) ?? '', $from);
        $sessionId = (int) ($from['exam_session_id'] ?? $from['sesi'] ?? 0);

        return redirect()->route('admin.applications.index', $sessionId ? ['exam_session_id' => $sessionId] : [])
            ->with('success', 'Permohonan berhasil dihapus.');
    }

    /**
     * Bubuhkan e-meterai FR.AK.01 secara MANUAL (klik admin) — dipakai saat
     * MATERAI_AUTO_STAMP=false, atau untuk mengulang yang gagal. Job idempoten
     * (skip kalau sudah 'stamped').
     */
    public function stampMaterai(AssessmentApplication $application)
    {
        abort_unless(config('materai.enabled'), 404);

        if (!$application->hasAllAk01Signatures()) {
            throw ValidationException::withMessages([
                'materai' => 'Ketiga tanda tangan (Asesi, LSP, Asesor) harus lengkap dulu sebelum materai dibubuhkan.',
            ]);
        }
        if ($application->materai_status === 'stamped') {
            throw ValidationException::withMessages(['materai' => 'Materai FR.AK.01 sudah dibubuhkan.']);
        }
        if (!$application->queueAk01Stamping()) {
            throw ValidationException::withMessages(['materai' => 'Materai sedang diproses. Tunggu sebentar lalu muat ulang halaman.']);
        }

        return back()->with('success', 'Pembubuhan materai FR.AK.01 diproses. Muat ulang halaman beberapa saat lagi untuk melihat hasilnya.');
    }

    /** Tampilkan/unduh FR.AK.01 yang sudah dibubuhi materai. */
    public function downloadMaterai(AssessmentApplication $application)
    {
        abort_unless(config('materai.enabled'), 404);
        abort_if(
            $application->materai_status !== 'stamped' || !$application->materai_document_path,
            404,
            'Materai FR.AK.01 belum dibubuhkan.'
        );
        abort_unless(Storage::disk('private')->exists($application->materai_document_path), 404);

        return response()->file(Storage::disk('private')->path($application->materai_document_path), [
            'Content-Disposition' => 'inline; filename="FR.AK.01 (materai) - ' . str_replace('"', '', (string) $application->code) . '.pdf"',
        ]);
    }

    public function reissueStudent(Request $request, AssessmentApplication $application, StudentEnrollmentService $enrollment)
    {
        abort_if(!$application->isApproved(), 422, 'Hanya permohonan yang sudah disetujui yang dapat di-reissue.');

        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $enrollment->reissue($application, $request->reason, auth()->id());

        return back()->with('success', 'Akun ujian baru berhasil dibuat.');
    }

    public function changeBatch(Request $request, AssessmentApplication $application, StudentEnrollmentService $enrollment)
    {
        $request->validate([
            'exam_session_id' => 'required|exists:exam_sessions,id',
        ]);

        if ($request->exam_session_id == $application->exam_session_id) {
            throw ValidationException::withMessages(['exam_session_id' => 'Peserta sudah berada di batch ini.']);
        }

        $newSession = ExamSession::with('examPg', 'examEsai')->findOrFail($request->exam_session_id);

        if ($newSession->referenceExam?->classroom_id !== $application->classroom_id) {
            throw ValidationException::withMessages(['exam_session_id' => 'Sesi yang dipilih bukan untuk skema yang sama.']);
        }

        // Kalau peserta sudah punya akun ujian (approved) dan sudah mulai mengerjakan
        // (ada nilai/jawaban tersimpan di batch lama), batch tidak boleh dipindah otomatis
        // supaya data hasil ujian tidak jadi yatim/tidak konsisten.
        if ($enrollment->hasExamActivity($application)) {
            throw ValidationException::withMessages(['exam_session_id' => 'Peserta sudah memiliki jawaban/nilai tersimpan di batch saat ini, tidak bisa dipindahkan otomatis.']);
        }

        $enrollment->moveToSession($application, $newSession);

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
            'form'   => $application->signature_form_path,
            'pakta'  => $application->signature_path,
            'admin'  => $application->admin_signature_path,
            'asesor' => $application->asesor_signature_path,
            default  => null,
        };

        abort_if(!$path || !Storage::disk('private')->exists($path), 404);

        return response()->file(Storage::disk('private')->path($path), $this->noCacheHeaders());
    }

    public function serveAdminDefaultSignature()
    {
        $user = auth()->user();
        abort_if(!$user->signature_path || !Storage::disk('private')->exists($user->signature_path), 404);
        return response()->file(Storage::disk('private')->path($user->signature_path), $this->noCacheHeaders());
    }

    // TTD bisa diganti kapan saja (path unik per unggahan, tapi URL preview tetap
    // sama) — cegah browser menampilkan gambar lama dari cache.
    private function noCacheHeaders(): array
    {
        return ['Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0', 'Pragma' => 'no-cache'];
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
}
