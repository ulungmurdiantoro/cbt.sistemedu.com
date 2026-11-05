<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\AssessmentApplication;
use App\Models\ClassroomDocumentRequirement;
use App\Models\ExamSession;
use App\Models\Student;
use App\Models\TukVerification;
use App\Models\User;
use App\Services\DocumentGeneratorService;
use App\Support\TukChecklist;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

// FR.TUK.06 — Checklist Verifikasi TUK Online. Admin mengisi checklist atas nama Pengawas Ujian,
// satu checklist per peserta per sesi: bagian A–E & G sebelum ujian, F & H selama/
// setelah ujian, I validasi. Hanya pencatatan — tidak mengunci ujian peserta.
// Pengawas Ujian dipilih dari user ber-role admin; nama + TTD-nya (users.signature_path/_name —
// dibuat di Kelola User atau saat menyetujui permohonan) disalin ke checklist setiap kali disimpan.
class TukVerificationController extends Controller
{
    /** Peserta aktif sesi ini, urut No. Peserta. */
    private function orderedStudentIds(int $examSessionId): Collection
    {
        return Student::whereIn('id', ExamSession::activeStudentIds($examSessionId))
            ->orderBy('no_participant')
            ->pluck('id');
    }

    /** Pengawas Ujian yang bisa dipilih: user ber-role admin, urut nama. */
    private function pengawasOptions(): Collection
    {
        return User::whereHas('roleAssignments', fn ($q) => $q->where('role', UserRole::Admin->value))
            ->orderBy('name')
            ->get(['id', 'name', 'signature_name', 'signature_path'])
            ->map(fn (User $u) => [
                'id'            => $u->id,
                'name'          => $u->signature_name ?: $u->name,
                'has_signature' => $u->signature_path && Storage::disk('private')->exists($u->signature_path),
            ]);
    }

    /**
     * Dokumen Identitas Diri (KTP/SIM/Paspor) yang diunggah peserta di permohonannya — pembanding
     * Pengawas Ujian untuk bagian B (Verifikasi Identitas). Hanya ditampilkan, tidak disimpan ke checklist.
     */
    private function identityDocument(int $examSessionId, int $studentId): array
    {
        $application = AssessmentApplication::where('student_id', $studentId)
            ->where('exam_session_id', $examSessionId)
            ->with('classroom.documentRequirements', 'documents')
            ->first();

        $requirement = $application?->classroom?->documentRequirements
            ->first(fn (ClassroomDocumentRequirement $r) => $r->isIdentityDocument());
        $document = $requirement
            ? $application->documents->firstWhere('classroom_document_requirement_id', $requirement->id)
            : null;

        return [
            'label'           => $requirement?->label ?? 'Dokumen Identitas Diri (KTP/SIM/Paspor)',
            'is_required'     => $requirement?->is_required ?? true,
            'application_id'  => $application?->id,
            'classroom_id'    => $application?->classroom_id,
            'has_requirement' => (bool) $requirement,
            'document'        => $document ? [
                'id'             => $document->id,
                'status'         => $document->status,
                'reviewer_notes' => $document->reviewer_notes,
                'is_image'       => str_starts_with((string) $document->mime_type, 'image/'),
            ] : null,
        ];
    }

    /** Pengawas terakhir yang dipilih admin ini di sesi tersebut — supaya tidak memilih ulang tiap peserta. */
    private function pengawasSessionKey(int $examSessionId): string
    {
        return "tuk_pengawas.{$examSessionId}";
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
                'revision'         => $v?->revision,
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

        $examSession  = ExamSession::with('examPg.classroom', 'examEsai.classroom')->findOrFail($examSessionId);
        $student      = Student::findOrFail($studentId);
        $verification = TukVerification::where('exam_session_id', $examSessionId)->where('student_id', $studentId)->first();
        $pengawas     = $this->pengawasOptions();

        // Checklist yang sudah ada tetap memakai pengawasnya; checklist baru memakai pengawas
        // terakhir yang dipilih di sesi ini, atau admin yang login. Bukan admin lagi → pilih ulang.
        $defaultPengawasId = $verification
            ? $verification->pengawas_id
            : $request->session()->get($this->pengawasSessionKey($examSessionId), $request->user()->id);

        // Checklist lama tetap memakai revisinya sendiri; checklist baru memakai revisi terbaru.
        $definition = TukChecklist::definition($verification?->revision ?? TukChecklist::CURRENT);

        return inertia('Admin/Penilaian/VerifikasiTuk/Show', [
            'exam_session'        => $examSession,
            'student'             => $student->only(['id', 'no_participant', 'name']),
            'skema'               => $examSession->referenceExam?->classroom?->title,
            'verification'        => $verification,
            'identity_document'   => $this->identityDocument($examSessionId, $studentId),
            'sections'            => $definition['sections'],
            'options'             => collect($definition)->except('sections')->all()
                + ['status_ulang' => TukChecklist::STATUS_ULANG],
            'pengawas_options'    => $pengawas,
            'default_pengawas_id' => $pengawas->contains('id', $defaultPengawasId) ? $defaultPengawasId : null,
            'next_student_id'     => $ids->get($ids->search($studentId) + 1),
        ]);
    }

    public function store(Request $request, int $examSessionId, int $studentId)
    {
        $ids = $this->orderedStudentIds($examSessionId);
        abort_unless($ids->contains($studentId), 404);

        $verification = TukVerification::firstOrNew([
            'exam_session_id' => $examSessionId,
            'student_id'      => $studentId,
        ]);

        // Checklist lama tetap di revisinya (butir & pilihan kesimpulan berbeda); yang baru = revisi terbaru.
        $revision   = $verification->exists ? TukChecklist::normalizeRevision($verification->revision) : TukChecklist::CURRENT;
        $definition = TukChecklist::definition($revision);
        $isRev1     = $revision === 'Rev.1';

        $rules = [
            'tanggal_asesmen'   => 'nullable|date',
            'waktu_asesmen'     => 'nullable|string|max:50',
            'lokasi_peserta'    => $isRev1
                ? ['nullable', Rule::in($definition['lokasi_options'])]
                : 'nullable|string|max:255',
            'items'             => 'nullable|array',
            'items.*.status'    => ['nullable', Rule::in(TukChecklist::STATUSES)],
            'items.*.catatan'   => 'nullable|string|max:500',
            'kesimpulan_awal'   => ['nullable', Rule::in(array_keys($definition['kesimpulan_awal']))],
            'catatan_awal'      => 'nullable|string|max:2000',
            'hasil_pemantauan'  => ['nullable', Rule::in(array_keys($definition['hasil_pemantauan']))],
            'uraian_pemantauan' => 'nullable|string|max:2000',
            'kesimpulan_akhir'  => ['nullable', Rule::in(array_keys($definition['kesimpulan_akhir']))],
            'pengawas_id'       => ['required', Rule::exists('user_roles', 'user_id')->where('role', UserRole::Admin->value)],
        ];

        if ($isRev1) {
            $rules += [
                'lokasi_lainnya'                => 'nullable|string|max:255',
                'ketidaksesuaian'               => 'nullable|array|max:20',
                'ketidaksesuaian.*.uraian'      => 'nullable|string|max:500',
                'ketidaksesuaian.*.tindakan'    => 'nullable|string|max:500',
                'ketidaksesuaian.*.batas_waktu' => 'nullable|string|max:100',
                'ketidaksesuaian.*.status'      => ['nullable', Rule::in(array_keys(TukChecklist::STATUS_ULANG))],
                'kesimpulan_ulang'              => ['nullable', Rule::in(array_keys(TukChecklist::STATUS_ULANG))],
                'tindakan_pemantauan'           => 'nullable|string|max:2000',
                'dampak_pemantauan'             => ['nullable', Rule::in(array_keys($definition['dampak_options']))],
                'dampak_lainnya'                => 'nullable|string|max:255',
            ];
        }

        $data = $request->validate($rules, [
            'pengawas_id.required' => 'Pilih Nama Pengawas Ujian.',
            'pengawas_id.exists'   => 'Pengawas Ujian harus user dengan role Admin.',
        ]);

        // Simpan hanya butir yang dikenal revisi ini dan yang terisi.
        $items = collect($data['items'] ?? [])
            ->only(TukChecklist::itemKeys($revision))
            ->map(fn ($item) => [
                'status'  => $item['status'] ?? null,
                'catatan' => trim((string) ($item['catatan'] ?? '')),
            ])
            ->filter(fn ($item) => $item['status'] || $item['catatan'] !== '')
            ->all();

        if ($isRev1) {
            // Baris tabel ketidaksesuaian yang kosong semua tidak disimpan.
            $data['ketidaksesuaian'] = collect($data['ketidaksesuaian'] ?? [])
                ->map(fn ($row) => [
                    'uraian'      => trim((string) ($row['uraian'] ?? '')),
                    'tindakan'    => trim((string) ($row['tindakan'] ?? '')),
                    'batas_waktu' => trim((string) ($row['batas_waktu'] ?? '')),
                    'status'      => $row['status'] ?? null,
                ])
                ->filter(fn ($row) => $row['uraian'] !== '' || $row['tindakan'] !== '' || $row['batas_waktu'] !== '' || $row['status'])
                ->values()
                ->all();

            if (($data['lokasi_peserta'] ?? null) !== 'Lainnya') {
                $data['lokasi_lainnya'] = null;
            }
            if (($data['dampak_pemantauan'] ?? null) !== 'lainnya') {
                $data['dampak_lainnya'] = null;
            }
        }

        $pengawas = User::findOrFail($data['pengawas_id']);
        $request->session()->put($this->pengawasSessionKey($examSessionId), $pengawas->id);

        // Nama + TTD pengawas terpilih disalin ke checklist setiap kali disimpan.
        $verification->fill([
            ...collect($data)->except('items', 'pengawas_id')->all(),
            'revision'                => $revision,
            'items'                   => $items,
            'pengawas_id'             => $pengawas->id,
            'pengawas_name'           => $pengawas->signature_name ?: $pengawas->name,
            'pengawas_signature_path' => $pengawas->signature_path,
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
