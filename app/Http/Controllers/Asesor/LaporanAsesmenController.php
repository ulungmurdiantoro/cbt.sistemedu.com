<?php

namespace App\Http\Controllers\Asesor;

use App\Http\Controllers\Controller;
use App\Models\AsesmenReport;
use App\Models\AsesorAssignment;
use App\Models\AssessmentApplication;
use App\Models\ExamSession;
use App\Models\Student;
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

        return $students->map(function ($student) use ($applications) {
            $rekomendasi = $applications->get($student->id)?->asesor_rekomendasi;
            $keterangan  = match ($rekomendasi) {
                'K'     => 'Direkomendasikan untuk mendapatkan sertifikat kegiatan sertifikasi',
                'BK'    => 'Tidak direkomendasikan untuk mendapatkan sertifikat kegiatan sertifikasi',
                default => '-',
            };

            return [
                'student_id'     => $student->id,
                'no_participant' => $student->no_participant,
                'name'           => $student->name,
                'rekomendasi'    => $rekomendasi,
                'keterangan'     => $keterangan,
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
     * Download FR.AK.05, diakses oleh asesor yang bersangkutan, admin, atau manager sertifikasi.
     */
    public function download(int $examSessionId, int $asesorUserId, DocumentGeneratorService $generator)
    {
        $user   = auth()->user();
        $isSelf = $user->hasRole(\App\Enums\UserRole::Asesor) && $user->id === $asesorUserId;
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
