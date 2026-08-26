<?php

namespace App\Http\Controllers\Asesor;

use App\Http\Controllers\Controller;
use App\Models\AsesorAssignment;
use App\Models\AssessmentApplication;
use App\Models\ExamSession;
use App\Models\ParticipantResult;
use App\Models\Student;
use App\Models\User;
use App\Services\DocumentGeneratorService;
use Illuminate\Http\Request;

class LaporanAsesmenController extends Controller
{
    private function assignedStudentIds(int $examSessionId, int $userId): \Illuminate\Support\Collection
    {
        return AsesorAssignment::where('user_id', $userId)
            ->where('exam_session_id', $examSessionId)
            ->pluck('student_id');
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

        return inertia('Asesor/LaporanAsesmen/Show', [
            'exam_session' => $examSession,
            'rows'         => $this->buildRows($examSessionId, auth()->id()),
        ]);
    }

    /**
     * Simpan rekomendasi K/BK per peserta — terpisah dari Verifikasi Akhir dokumen.
     * Bisa diisi/diubah asesor kapan saja sebelum sesi difinalisasi Manager Sertifikasi.
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
            // Sudah difinalisasi Manager Sertifikasi — rekomendasi tidak boleh diubah lagi.
            if ($finalized->contains($studentId)) continue;

            AssessmentApplication::where('student_id', $studentId)
                ->where('exam_session_id', $examSessionId)
                ->update(['asesor_rekomendasi' => $item['value']]);
        }

        return back()->with('success', 'Rekomendasi berhasil disimpan.');
    }

    /**
     * Download FR.AK.05, diakses oleh asesor yang bersangkutan, admin, atau manager sertifikasi.
     * Dibuat langsung dari rekomendasi & data sesi — tidak perlu form/"laporan" terpisah.
     */
    public function download(int $examSessionId, int $asesorUserId, DocumentGeneratorService $generator)
    {
        $user    = auth()->user();
        $isSelf  = $user->hasRole(\App\Enums\UserRole::Asesor) && $user->id === $asesorUserId;
        $isStaff = $user->hasRole(\App\Enums\UserRole::Admin) || $user->hasRole(\App\Enums\UserRole::ManagerSertifikasi);
        abort_unless($isSelf || $isStaff, 403);

        $examSession = ExamSession::with('examPg.classroom', 'examEsai.classroom')->findOrFail($examSessionId);
        $asesor      = User::findOrFail($asesorUserId);

        $rows = $this->buildRows($examSessionId, $asesorUserId);
        abort_if(empty($rows), 404, 'Asesor ini tidak ditugaskan ke peserta manapun di sesi tersebut.');

        $pdf = $generator->generateFrAk05($examSession, $asesor, $rows);

        return response($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="FR.AK.05 Laporan Asesmen.pdf"',
        ]);
    }
}
