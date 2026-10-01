<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamSession;
use App\Models\Student;
use App\Models\TukVerification;
use App\Services\DocumentGeneratorService;
use App\Support\TukChecklist;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

// FR.TUK.06 — Checklist Verifikasi TUK Online. Admin mengisi sebagai Pengawas Ujian,
// satu checklist per peserta per sesi: bagian A–E & G sebelum ujian, F & H selama/
// setelah ujian, I validasi. Hanya pencatatan — tidak mengunci ujian peserta.
// Pengawas yang tercatat (nama + TTD dari Kelola User) = admin terakhir yang menyimpan.
class TukVerificationController extends Controller
{
    /** Peserta aktif sesi ini, urut No. Peserta. */
    private function orderedStudentIds(int $examSessionId): Collection
    {
        return Student::whereIn('id', ExamSession::activeStudentIds($examSessionId))
            ->orderBy('no_participant')
            ->pluck('id');
    }

    public function index(int $examSessionId)
    {
        $examSession = ExamSession::with('examPg.classroom', 'examEsai.classroom')->findOrFail($examSessionId);

        $students = Student::whereIn('id', ExamSession::activeStudentIds($examSessionId))
            ->orderBy('no_participant')
            ->get(['id', 'no_participant', 'name']);

        $verifications = TukVerification::where('exam_session_id', $examSessionId)->get()->keyBy('student_id');

        $rows = $students->map(function ($student) use ($verifications) {
            $v = $verifications->get($student->id);

            return [
                'student_id'       => $student->id,
                'no_participant'   => $student->no_participant,
                'name'             => $student->name,
                'has_record'       => (bool) $v,
                'kesimpulan_awal'  => $v?->kesimpulan_awal,
                'kesimpulan_akhir' => $v?->kesimpulan_akhir,
                'pengawas_name'    => $v?->pengawas_name,
                'verified_at'      => $v?->verified_at,
            ];
        });

        return inertia('Admin/Penilaian/VerifikasiTuk/Index', [
            'exam_session' => $examSession,
            'rows'         => $rows,
        ]);
    }

    public function show(Request $request, int $examSessionId, int $studentId)
    {
        $ids = $this->orderedStudentIds($examSessionId);
        abort_unless($ids->contains($studentId), 404);

        $examSession = ExamSession::with('examPg.classroom', 'examEsai.classroom')->findOrFail($examSessionId);
        $student     = Student::findOrFail($studentId);
        $user        = $request->user();

        return inertia('Admin/Penilaian/VerifikasiTuk/Show', [
            'exam_session'    => $examSession,
            'student'         => $student->only(['id', 'no_participant', 'name']),
            'skema'           => $examSession->referenceExam?->classroom?->title,
            'verification'    => TukVerification::where('exam_session_id', $examSessionId)->where('student_id', $studentId)->first(),
            'sections'        => TukChecklist::sections(),
            'options'         => [
                'kesimpulan_awal'            => TukChecklist::KESIMPULAN_AWAL,
                'kesimpulan_awal_keterangan' => TukChecklist::KESIMPULAN_AWAL_KETERANGAN,
                'hasil_pemantauan'           => TukChecklist::HASIL_PEMANTAUAN,
                'kesimpulan_akhir'           => TukChecklist::KESIMPULAN_AKHIR,
            ],
            'pengawas'        => [
                'name'          => $user->signature_name ?: $user->name,
                'has_signature' => (bool) $user->signature_path,
            ],
            'next_student_id' => $ids->get($ids->search($studentId) + 1),
        ]);
    }

    public function store(Request $request, int $examSessionId, int $studentId)
    {
        $ids = $this->orderedStudentIds($examSessionId);
        abort_unless($ids->contains($studentId), 404);

        $data = $request->validate([
            'tanggal_asesmen'   => 'nullable|date',
            'waktu_asesmen'     => 'nullable|string|max:50',
            'lokasi_peserta'    => 'nullable|string|max:255',
            'items'             => 'nullable|array',
            'items.*.status'    => ['nullable', Rule::in(TukChecklist::STATUSES)],
            'items.*.catatan'   => 'nullable|string|max:500',
            'kesimpulan_awal'   => ['nullable', Rule::in(array_keys(TukChecklist::KESIMPULAN_AWAL))],
            'catatan_awal'      => 'nullable|string|max:2000',
            'hasil_pemantauan'  => ['nullable', Rule::in(array_keys(TukChecklist::HASIL_PEMANTAUAN))],
            'uraian_pemantauan' => 'nullable|string|max:2000',
            'kesimpulan_akhir'  => ['nullable', Rule::in(array_keys(TukChecklist::KESIMPULAN_AKHIR))],
        ]);

        // Simpan hanya butir yang dikenal TukChecklist dan yang terisi.
        $items = collect($data['items'] ?? [])
            ->only(TukChecklist::itemKeys())
            ->map(fn ($item) => [
                'status'  => $item['status'] ?? null,
                'catatan' => trim((string) ($item['catatan'] ?? '')),
            ])
            ->filter(fn ($item) => $item['status'] || $item['catatan'] !== '')
            ->all();

        $user = $request->user();

        $verification = TukVerification::firstOrNew([
            'exam_session_id' => $examSessionId,
            'student_id'      => $studentId,
        ]);

        $verification->fill([
            ...collect($data)->except('items')->all(),
            'items'                   => $items,
            'pengawas_id'             => $user->id,
            'pengawas_name'           => $user->signature_name ?: $user->name,
            'pengawas_signature_path' => $user->signature_path,
        ]);

        // Tanggal/Waktu Verifikasi = saat kesimpulan verifikasi awal pertama kali diisi;
        // tidak bergeser saat checklist dilengkapi lagi (bagian F/H) setelah ujian.
        if (!$verification->kesimpulan_awal) {
            $verification->verified_at = null;
        } elseif (!$verification->verified_at) {
            $verification->verified_at = now();
        }

        $verification->save();

        $next = $ids->get($ids->search($studentId) + 1);
        if ($request->boolean('next') && $next) {
            return redirect()->route('admin.penilaian.tuk.show', [$examSessionId, $next])
                ->with('success', 'Checklist peserta sebelumnya tersimpan.');
        }

        return back()->with('success', 'Checklist FR.TUK.06 berhasil disimpan.');
    }

    public function pdf(int $examSessionId, int $studentId, DocumentGeneratorService $generator)
    {
        abort_unless(ExamSession::activeStudentIds($examSessionId)->contains($studentId), 404);

        $verification = TukVerification::with('student', 'examSession.examPg.classroom', 'examSession.examEsai.classroom')
            ->where('exam_session_id', $examSessionId)
            ->where('student_id', $studentId)
            ->firstOrFail();

        $filename = 'FR.TUK.06 - ' . str_replace('"', '', (string) $verification->student->no_participant) . '.pdf';

        return response($generator->generateFrTuk06($verification), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }
}
