<?php

namespace App\Http\Controllers\Asesor;

use App\Http\Controllers\Controller;
use App\Models\AsesorAssignment;
use App\Models\AssessmentApplication;
use App\Models\ExamSession;
use App\Models\Student;
use App\Services\DocumentGeneratorService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * TTD AK.01 self-service — asesor menandatangani FR.AK.01 sendiri untuk peserta
 * yang ditugaskan kepadanya, tanpa perlu admin. Versi admin dari fitur yang sama
 * ada di Admin\PenilaianDokumenController (dipakai kalau admin yang membubuhkan
 * atas nama asesor); di sini asesor melakukannya sendiri, dibatasi hanya untuk
 * peserta yang memang ditugaskan kepadanya (AsesorAssignment miliknya).
 */
class TtdAk01Controller extends Controller
{
    private function assignedStudentIds(int $examSessionId, int $userId): \Illuminate\Support\Collection
    {
        $ids = AsesorAssignment::where('user_id', $userId)
            ->where('exam_session_id', $examSessionId)
            ->pluck('student_id');

        // Sembunyikan akun peserta yang sudah dinonaktifkan (mis. akun lama hasil
        // re-issue) supaya nama tidak tampil dobel di daftar TTD AK.01.
        return Student::whereIn('id', $ids)
            ->where('is_active', true)
            ->pluck('id');
    }

    public function show(int $examSessionId)
    {
        $asesor = auth()->user();
        $studentIds = $this->assignedStudentIds($examSessionId, $asesor->id);
        abort_if($studentIds->isEmpty(), 403, 'Anda tidak ditugaskan pada sesi ini.');

        $examSession = ExamSession::with('examPg.classroom', 'examEsai.classroom')->findOrFail($examSessionId);

        $students = Student::whereIn('id', $studentIds)->orderBy('no_participant')->get();

        $applications = AssessmentApplication::whereIn('student_id', $studentIds)
            ->where('exam_session_id', $examSessionId)
            ->get()
            ->keyBy('student_id');

        $rows = $students->map(function ($student) use ($applications) {
            $app = $applications->get($student->id);

            return [
                'student_id'         => $student->id,
                'no_participant'     => $student->no_participant,
                'name'               => $student->name,
                'app_id'             => $app?->id,
                'asesor_verified_at' => $app?->asesor_verified_at,
            ];
        })->values();

        return inertia('Asesor/TtdAk01/Show', [
            'exam_session'  => $examSession,
            'rows'          => $rows,
            'has_signature' => (bool) $asesor->signature_path,
        ]);
    }

    private function abortUnlessAssigned(int $examSessionId, int $studentId, int $userId): void
    {
        abort_unless(
            AsesorAssignment::where('user_id', $userId)
                ->where('exam_session_id', $examSessionId)
                ->where('student_id', $studentId)
                ->exists(),
            403,
            'Peserta ini tidak ditugaskan kepada Anda.'
        );
    }

    /**
     * Preview FR.AK.01 peserta (inline, tab baru): versi bermeterai bila sudah dibubuhkan,
     * selain itu dibuat dari kondisi terkini — TTD asesor ikut tampil setelah ditandatangani.
     */
    public function preview(int $examSessionId, int $studentId, DocumentGeneratorService $generator)
    {
        $this->abortUnlessAssigned($examSessionId, $studentId, auth()->id());

        $application = AssessmentApplication::with('participant')
            ->where('student_id', $studentId)
            ->where('exam_session_id', $examSessionId)
            ->first();

        abort_unless($application, 404, 'Peserta ini belum memiliki permohonan sertifikasi.');

        $nama = str_replace('"', '', $application->participant?->name ?? 'Peserta');

        $stamped = $application->materai_status === 'stamped' ? $application->materai_document_path : null;
        if (config('materai.enabled') && $stamped && Storage::disk('private')->exists($stamped)) {
            return response()->file(Storage::disk('private')->path($stamped), [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $nama . ' - FR.AK.01 (materai).pdf"',
            ]);
        }

        return response($generator->generateFrAk01($application), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $nama . ' - FR.AK.01.pdf"',
        ]);
    }

    public function sign(int $examSessionId, int $studentId)
    {
        $asesor = auth()->user();

        $this->abortUnlessAssigned($examSessionId, $studentId, $asesor->id);

        abort_unless(
            Student::where('id', $studentId)->where('is_active', true)->exists(),
            404,
            'Akun peserta ini sudah tidak aktif (mis. digantikan akun baru hasil re-issue).'
        );

        $application = AssessmentApplication::where('student_id', $studentId)
            ->where('exam_session_id', $examSessionId)
            ->first();

        if (!$application) {
            throw ValidationException::withMessages(['ttd_ak01' => 'Peserta ini belum memiliki permohonan sertifikasi.']);
        }

        if ($application->asesor_verified_at) {
            throw ValidationException::withMessages(['ttd_ak01' => 'AK.01 peserta ini sudah ditandatangani sebelumnya.']);
        }

        if (!$asesor->signature_path || !Storage::disk('private')->exists($asesor->signature_path)) {
            throw ValidationException::withMessages(['ttd_ak01' => 'Anda belum memiliki TTD tersimpan. Simpan TTD Anda dulu di halaman Laporan Asesmen.']);
        }

        $application->update([
            'asesor_verified_by'    => $asesor->id,
            'asesor_verified_at'    => now(),
            'asesor_signature_path' => $asesor->signature_path,
            'asesor_signature_name' => $asesor->signature_name ?: $asesor->name,
        ]);

        // Meterai FR.AK.01 baru dibubuhkan setelah ketiga tanda tangan lengkap
        // (Asesi + LSP/admin + Asesor) — lihat AssessmentApplication::maybeTriggerAk01Stamping().
        $application->maybeTriggerAk01Stamping();

        return back()->with('success', 'AK.01 berhasil ditandatangani atas nama Anda.');
    }
}
