<?php

namespace App\Http\Controllers\Peserta;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadDocumentRequest;
use App\Models\ApplicationDocument;
use App\Models\AssessmentApplication;
use App\Models\ClassroomDocumentRequirement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function index(AssessmentApplication $application)
    {
        $this->authorizeApplication($application);

        $application->load(['classroom.documentRequirements', 'documents.requirement']);

        $requirements = $application->classroom->documentRequirements->map(function ($req) use ($application) {
            $doc = $application->documents->firstWhere('classroom_document_requirement_id', $req->id);
            return [
                'id'          => $req->id,
                'code'        => $req->code,
                'label'       => $req->label,
                'description' => $req->description,
                'is_required' => $req->is_required,
                'document'    => $doc ? [
                    'id'                => $doc->id,
                    'file_path'         => $doc->file_path,
                    'original_filename' => $doc->original_filename,
                    'status'            => $doc->status,
                    'reviewer_notes'    => $doc->reviewer_notes,
                ] : null,
            ];
        });

        return inertia('Peserta/Application/Documents', [
            'application'  => $application->only('id', 'code', 'status',
                'signature_path', 'signature_form_path', 'pakta_signed_at'),
            'requirements' => $requirements,
        ]);
    }

    public function upload(UploadDocumentRequest $request, AssessmentApplication $application)
    {
        $this->authorizeApplication($application);

        abort_if(!$application->isDraft(), 403, 'Permohonan sudah disubmit, dokumen tidak dapat diubah.');

        // Validasi MIME aktual menggunakan finfo (cek magic bytes, bukan ekstensi)
        $file         = $request->file('file');
        $allowedMimes = ['application/pdf', 'image/jpeg', 'image/png'];
        $finfo        = new \finfo(FILEINFO_MIME_TYPE);
        $realMime     = $finfo->file($file->getRealPath());

        if (!in_array($realMime, $allowedMimes)) {
            return back()->withErrors(['file' => 'Format file tidak valid. Hanya PDF, JPG, dan PNG yang diperbolehkan.']);
        }

        $requirement = ClassroomDocumentRequirement::where('id', $request->requirement_id)
            ->where('classroom_id', $application->classroom_id)
            ->firstOrFail();

        // hapus file lama jika ada
        $existing = ApplicationDocument::where('assessment_application_id', $application->id)
            ->where('classroom_document_requirement_id', $requirement->id)
            ->first();

        if ($existing) {
            Storage::disk('private')->delete($existing->file_path);
            $existing->delete();
        }

        $ext      = $file->getClientOriginalExtension();
        $filename = $requirement->code . '_' . $application->id . '_' . time() . '.' . $ext;
        $path     = $file->storeAs('applications/' . $application->id, $filename, 'private');

        ApplicationDocument::create([
            'assessment_application_id'          => $application->id,
            'classroom_document_requirement_id'  => $requirement->id,
            'file_path'                          => $path,
            'original_filename'                  => $file->getClientOriginalName(),
            'mime_type'                          => $realMime,
            'file_size'                          => $file->getSize(),
            'status'                             => 'pending',
        ]);

        return back()->with('success', 'Dokumen berhasil diupload.');
    }

    public function download(AssessmentApplication $application, ApplicationDocument $document)
    {
        $this->authorizeApplication($application);

        abort_if($document->assessment_application_id !== $application->id, 403);
        abort_if(!Storage::disk('private')->exists($document->file_path), 404);

        return response()->download(Storage::disk('private')->path($document->file_path), $document->original_filename);
    }

    public function destroy(AssessmentApplication $application, ApplicationDocument $document)
    {
        $this->authorizeApplication($application);

        abort_if(!$application->isDraft(), 403);
        abort_if($document->assessment_application_id !== $application->id, 403);

        Storage::disk('private')->delete($document->file_path);
        $document->delete();

        return back()->with('success', 'Dokumen berhasil dihapus.');
    }

    private function authorizeApplication(AssessmentApplication $application): void
    {
        abort_if(
            $application->participant_id !== auth()->guard('participant')->id(),
            403
        );
    }
}
