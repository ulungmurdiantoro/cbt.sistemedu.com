<?php

namespace App\Http\Controllers\Asesor;

use App\Http\Controllers\Controller;
use App\Models\AsesmenReport;
use App\Models\AsesorAssignment;
use App\Models\AssessmentApplication;
use App\Models\ExamSession;
use App\Models\ParticipantResult;
use App\Models\Student;
use App\Services\DocumentGeneratorService;
use App\Support\SignatureImageProcessor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LaporanAsesmenController extends Controller
{
    private function assignedStudentIds(int $examSessionId, int $userId): \Illuminate\Support\Collection
    {
        $ids = AsesorAssignment::where('user_id', $userId)
            ->where('exam_session_id', $examSessionId)
            ->pluck('student_id');

        // Sembunyikan akun peserta yang sudah dinonaktifkan (mis. akun lama hasil
        // re-issue) supaya nama tidak tampil dobel di Laporan Asesmen & FR.AK.05.
        return Student::whereIn('id', $ids)
            ->where('is_active', true)
            ->pluck('id');
    }

    private function buildRows(int $examSessionId, int $userId): array
    {
        $studentIds = $this->assignedStudentIds($examSessionId, $userId);

        $students = Student::whereIn('id', $studentIds)
            ->orderBy('no_participant')
            ->get();

        $applications = AssessmentApplication::whereIn('student_id', $studentIds)
            ->where('exam_session_id', $examSessionId)
            ->get()
            ->keyBy('student_id');

        $finalized = ParticipantResult::where('exam_session_id', $examSessionId)
            ->whereIn('student_id', $studentIds)
            ->pluck('is_finalized', 'student_id');

        return $students->map(function ($student) use ($applications, $finalized) {
            $application = $applications->get($student->id);
            $rekomendasi = $application?->asesor_rekomendasi;
            $keterangan  = match ($rekomendasi) {
                'K'     => 'Direkomendasikan untuk mendapatkan sertifikat kegiatan sertifikasi',
                'BK'    => 'Tidak direkomendasikan untuk mendapatkan sertifikat kegiatan sertifikasi',
                default => '-',
            };

            return [
                'student_id'     => $student->id,
                'application_id' => $application?->id,
                'no_participant' => $student->no_participant,
                'name'           => $student->name,
                'rekomendasi'    => $rekomendasi,
                'keterangan'     => $keterangan,
                'is_finalized'   => (bool) ($finalized->get($student->id) ?? false),
            ];
        })->values()->all();
    }

    public function show(int $examSessionId)
    {
        $studentIds = $this->assignedStudentIds($examSessionId, auth()->id());
        abort_if($studentIds->isEmpty(), 403, 'Anda tidak ditugaskan pada sesi ini.');

        $examSession = ExamSession::with('examPg.classroom', 'examEsai.classroom')->findOrFail($examSessionId);

        $report = AsesmenReport::where('exam_session_id', $examSessionId)
            ->where('user_id', auth()->id())
            ->first();

        return inertia('Asesor/LaporanAsesmen/Show', [
            'exam_session'   => $examSession,
            'rows'           => $this->buildRows($examSessionId, auth()->id()),
            'report'         => $report,
            'has_signature'  => (bool) auth()->user()->signature_path,
        ]);
    }

    /**
     * Simpan TTD milik asesor yang sedang login sendiri — dipakai otomatis untuk
     * FR.AK.05 dan dokumen lain (mis. Verifikasi Akhir dokumen oleh admin).
     * Selalu beroperasi pada auth()->user(), jadi tidak perlu otorisasi tambahan.
     */
    public function saveSignature(Request $request)
    {
        $user = auth()->user();

        $hasNewSig = $request->signature_data || $request->hasFile('signature_file');
        abort_if(!$hasNewSig, 422, 'Tanda tangan wajib diisi (gambar atau upload).');

        $request->validate([
            'signature_data' => 'nullable|string',
            'signature_file' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
        ]);

        $disk = Storage::disk('private');
        $path = 'user-signatures/' . $user->id . '/sig_' . now()->format('YmdHis') . '.png';

        if ($request->hasFile('signature_file')) {
            $raw = file_get_contents($request->file('signature_file')->getRealPath());
            $disk->put($path, SignatureImageProcessor::removeBackground($raw));
        } elseif (preg_match('/^data:image\/(png|jpe?g);base64,(.+)$/', (string) $request->signature_data, $m)) {
            $disk->put($path, SignatureImageProcessor::removeBackground(base64_decode($m[2])));
        } else {
            abort(422, 'Format tanda tangan tidak valid.');
        }

        $user->update([
            'signature_path' => $path,
            'signature_name' => $user->signature_name ?: $user->name,
        ]);

        return back()->with('success', 'Tanda tangan berhasil disimpan.');
    }

    public function serveSignature()
    {
        $user = auth()->user();
        abort_if(!$user->signature_path || !Storage::disk('private')->exists($user->signature_path), 404);

        return response()->file(Storage::disk('private')->path($user->signature_path), [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma'        => 'no-cache',
        ]);
    }

    public function store(Request $request, int $examSessionId)
    {
        $studentIds = $this->assignedStudentIds($examSessionId, auth()->id());
        abort_if($studentIds->isEmpty(), 403, 'Anda tidak ditugaskan pada sesi ini.');

        $request->validate([
            'tanggal_asesmen'       => 'nullable|date',
            'aspek_negatif_positif' => 'nullable|string',
            'pencatatan_penolakan'  => 'nullable|string',
            'saran_perbaikan'       => 'nullable|string',
            'catatan'               => 'nullable|string',
        ]);

        AsesmenReport::updateOrCreate(
            ['exam_session_id' => $examSessionId, 'user_id' => auth()->id()],
            [
                'tanggal_asesmen'       => $request->tanggal_asesmen,
                'aspek_negatif_positif' => $request->aspek_negatif_positif,
                'pencatatan_penolakan'  => $request->pencatatan_penolakan,
                'saran_perbaikan'       => $request->saran_perbaikan,
                'catatan'               => $request->catatan,
                'submitted_at'          => now(),
            ]
        );

        return back()->with('success', 'Laporan Asesmen berhasil disimpan.');
    }

    /**
     * Simpan rekomendasi K/BK per peserta — terpisah dari Verifikasi Akhir dokumen.
     * Bisa diisi/diubah asesor kapan saja sebelum sesi difinalisasi Pengambil Keputusan.
     */
    public function storeRekomendasi(Request $request, int $examSessionId)
    {
        $studentIds = $this->assignedStudentIds($examSessionId, auth()->id());
        abort_if($studentIds->isEmpty(), 403, 'Anda tidak ditugaskan pada sesi ini.');

        $request->validate([
            'rekomendasi'              => 'required|array',
            'rekomendasi.*.student_id' => 'required|integer',
            'rekomendasi.*.value'      => 'nullable|in:K,BK',
        ]);

        $finalized = ParticipantResult::where('exam_session_id', $examSessionId)
            ->whereIn('student_id', $studentIds)
            ->where('is_finalized', true)
            ->pluck('student_id');

        foreach ($request->rekomendasi as $item) {
            $studentId = (int) $item['student_id'];

            // Keamanan: hanya peserta yang ditugaskan ke asesor ini.
            if (!$studentIds->contains($studentId)) continue;
            // Sudah difinalisasi Pengambil Keputusan — rekomendasi tidak boleh diubah lagi.
            if ($finalized->contains($studentId)) continue;

            AssessmentApplication::where('student_id', $studentId)
                ->where('exam_session_id', $examSessionId)
                ->update(['asesor_rekomendasi' => $item['value']]);
        }

        return back()->with('success', 'Rekomendasi berhasil disimpan.');
    }

    /**
     * Download FR.AK.05, diakses oleh asesor yang bersangkutan, admin, atau manager sertifikasi.
     */
    public function download(int $examSessionId, int $asesorUserId, DocumentGeneratorService $generator)
    {
        $user    = auth()->user();
        $isSelf  = $user->hasRole(\App\Enums\UserRole::Asesor) && $user->id === $asesorUserId;
        $isStaff = $user->hasRole(\App\Enums\UserRole::Admin) || $user->hasRole(\App\Enums\UserRole::ManagerSertifikasi);
        abort_unless($isSelf || $isStaff, 403);

        $examSession = ExamSession::with('examPg.classroom', 'examEsai.classroom')->findOrFail($examSessionId);

        $report = AsesmenReport::where('exam_session_id', $examSessionId)
            ->where('user_id', $asesorUserId)
            ->first();
        abort_if(!$report, 404, 'Laporan Asesmen belum diisi oleh asesor ini.');

        $report->setRelation('examSession', $examSession);
        $report->setRelation('asesor', \App\Models\User::find($asesorUserId));

        $pdf = $generator->generateFrAk05($report, $this->buildRows($examSessionId, $asesorUserId));

        return response($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="FR.AK.05 Laporan Asesmen.pdf"',
        ]);
    }
}
