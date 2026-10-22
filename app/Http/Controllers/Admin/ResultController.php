<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamSession;
use App\Models\Grade;
use App\Models\ParticipantResult;
use App\Models\Student;
use App\Models\StudentTask;
use App\Services\DocumentGeneratorService;
use App\Services\ResultCalculatorService;
use App\Support\AnswerFile;
use Illuminate\Support\Facades\Storage;

class ResultController extends Controller
{
    public function __construct(
        private ResultCalculatorService $calculator,
    ) {}

    public function index()
    {
        // Jumlah peserta & progres dihitung dari peserta terdaftar di sesi (akun aktif) — sama dengan
        // baris Rekap Hasil & Dashboard Pengambil Keputusan (ExamSession::scopeWithActiveStudentsCount).
        $sessions = ExamSession::withActiveStudentsCount()
            ->withCount([
                'participantResults as reviewed_count'   => fn ($q) => $q->enrolled()->whereNotNull('manager_verified_at'),
                'participantResults as sp_sent_count'    => fn ($q) => $q->enrolled()->whereNotNull('sp_distributed_at'),
                'participantResults as final_sent_count' => fn ($q) => $q->enrolled()->whereNotNull('distributed_at'),
            ])
            ->orderByRaw('CASE WHEN end_time > NOW() THEN 0 ELSE 1 END ASC')
            ->orderByRaw('CASE WHEN end_time > NOW() THEN end_time END ASC')
            ->orderBy('end_time', 'desc')
            ->paginate(15);

        return inertia('Admin/Results/Index', ['sessions' => $sessions]);
    }

    public function show(ExamSession $examSession)
    {
        $examSession->load(['examPg.classroom', 'examEsai.classroom']);

        $results = \Illuminate\Database\Eloquent\Collection::make($this->calculator->recalcForSession($examSession));
        // Pengambil Keputusan = user yang menekan Finalisasi (Manager\SertifikasiController::finalize)
        $results->load('finalizer:id,name');

        $studentIds = $results->pluck('student_id');
        $students   = Student::whereIn('id', $studentIds)->with('participant', 'classroom')->get()->keyBy('id');

        $tugas = StudentTask::where('exam_session_id', $examSession->id)
            ->whereIn('student_id', $studentIds)
            ->get()
            ->mapWithKeys(fn (StudentTask $task) => [$task->student_id => [
                'original_filename' => $task->original_filename,
            ]]);

        // ID grade per ujian → tautan "detail jawaban" (eks menu Laporan Nilai, admin.reports.show)
        $gradeIds = Grade::where('exam_session_id', $examSession->id)
            ->whereIn('student_id', $studentIds)
            ->get(['id', 'exam_id', 'student_id'])
            ->groupBy('student_id');

        $rows = $results->map(function ($r) use ($students, $gradeIds, $examSession) {
            $student = $students->get($r->student_id);
            $grades  = $gradeIds->get($r->student_id, collect());
            return [
                'result_id'         => $r->id,
                'student_id'        => $r->student_id,
                'no_participant'    => $student?->no_participant,
                'name'              => $student?->name,
                'nilai_pg'          => $r->nilai_pg,
                'nilai_esai'        => $r->nilai_esai,
                'grade_id_pg'       => $grades->firstWhere('exam_id', $examSession->exam_id_pg)?->id,
                'grade_id_esai'     => $grades->firstWhere('exam_id', $examSession->exam_id_esai)?->id,
                'nilai_wawancara'   => $r->nilai_wawancara,
                'nilai_akhir'       => $r->nilai_akhir,
                'keputusan'         => $r->keputusan,
                'is_finalized'      => $r->is_finalized,
                'finalized_at'      => $r->finalized_at,
                'finalized_by_name' => $r->finalizer?->name,
                'sk_number'         => $r->sk_number,
                'sp_number'         => $r->sp_number,
                'sertifikat_number' => $r->sertifikat_number,
                'distributed_at'    => $r->distributed_at,
                'sp_distributed_at' => $r->sp_distributed_at,
                'attempt'           => $r->attempt,
                // FR.AK.14 hanya untuk peserta LULUS yang sudah difinalisasi (sama dengan portal peserta)
                'fr_ak_14'          => $r->is_finalized && $r->keputusan === 'LULUS' ? [
                    'state'          => $r->frAk14State(),
                    'signed_at'      => $r->fr_ak_14_signed_at,
                    'stamped_at'     => $r->materai_stamped_at,
                    'failure_reason' => $r->materai_failure_reason,
                ] : null,
            ];
        })->sortBy('no_participant')->values();

        $classroomId = $examSession->referenceExam?->classroom_id;
        $scheme = $classroomId
            ? \App\Models\GradingScheme::where('classroom_id', $classroomId)->first()
            : null;

        return inertia('Admin/Results/Show', [
            'exam_session' => $examSession,
            'rows'         => $rows,
            'scheme'       => $scheme,
            'tugas'        => $tugas,
            // Kolom Tugas disembunyikan untuk skema yang tidak wajib upload tugas
            // (Student::requiresTugas), kecuali ada yang tetap mengunggah.
            'show_tugas'   => $tugas->isNotEmpty() || $students->contains(fn (Student $s) => $s->requiresTugas()),
            'fr_ak_14_requires_materai' => ParticipantResult::sessionRequiresFrAk14Materai($examSession->id),
        ]);
    }

    /** FR.AK.14 peserta — versi bermaterai kalau sudah dibubuhi, selain itu dibuat dari TTD-nya (tanpa materai). */
    public function frAk14(ExamSession $examSession, Student $student, DocumentGeneratorService $generator)
    {
        $result = ParticipantResult::where('exam_session_id', $examSession->id)
            ->where('student_id', $student->id)
            ->where('is_finalized', true)
            ->where('keputusan', 'LULUS')
            ->whereNotNull('fr_ak_14_signed_at')
            ->firstOrFail();

        $filename = 'FR.AK.14_' . $student->no_participant;

        $stamped = $result->materai_status === 'stamped' ? $result->materai_document_path : null;
        if (config('materai.enabled') && $stamped && Storage::disk('private')->exists($stamped)) {
            return response()->file(Storage::disk('private')->path($stamped), [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => "inline; filename=\"{$filename}_materai.pdf\"",
            ]);
        }

        return response($generator->generateFrAk14($result), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', "inline; filename=\"{$filename}.pdf\"");
    }

    /** Pratinjau tugas peserta di tab baru — sama dengan portal asesor, tanpa cek penugasan. */
    public function previewTugas(ExamSession $examSession, Student $student)
    {
        return inertia('Admin/Results/Tugas', [
            'exam_session' => $examSession->only('id', 'title'),
            'student'      => $student->only('id', 'no_participant', 'name'),
            'tugas'        => $this->task($examSession, $student)->previewProps(),
        ]);
    }

    public function fileTugas(ExamSession $examSession, Student $student)
    {
        $task = $this->task($examSession, $student);

        return AnswerFile::preview($task->file_path, $task->original_filename);
    }

    public function downloadTugas(ExamSession $examSession, Student $student)
    {
        $task = $this->task($examSession, $student);

        return AnswerFile::download($task->file_path, $task->original_filename);
    }

    private function task(ExamSession $examSession, Student $student): StudentTask
    {
        $task = StudentTask::where('exam_session_id', $examSession->id)
            ->where('student_id', $student->id)
            ->first();

        abort_unless($task, 404, 'Tugas tidak ditemukan.');

        return $task;
    }

    // Finalisasi nilai dipindah ke Manager\SertifikasiController — kelulusan
    // & penerbitan sertifikat sekarang jadi wewenang Pengambil Keputusan,
    // bukan admin biasa.

    public function downloadSp(ExamSession $examSession, Student $student, DocumentGeneratorService $generator)
    {
        $result = ParticipantResult::where('exam_session_id', $examSession->id)
            ->where('student_id', $student->id)
            ->where('is_finalized', true)
            ->firstOrFail();

        $pdf      = $generator->spPdf($result);
        $filename = 'SP_' . $student->no_participant . '.pdf';

        return response($pdf, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', "inline; filename=\"{$filename}\"");
    }

    public function downloadSk(ExamSession $examSession, Student $student, DocumentGeneratorService $generator)
    {
        $result = ParticipantResult::where('exam_session_id', $examSession->id)
            ->where('student_id', $student->id)
            ->where('is_finalized', true)
            ->firstOrFail();

        $withKan  = request()->boolean('kan');
        $pdf      = $generator->skPdf($result, $withKan);
        $filename = 'SK_' . $student->no_participant . '.pdf';

        return response($pdf, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', "inline; filename=\"{$filename}\"");
    }

    public function downloadSertifikat(ExamSession $examSession, Student $student, DocumentGeneratorService $generator)
    {
        $result = ParticipantResult::where('exam_session_id', $examSession->id)
            ->where('student_id', $student->id)
            ->where('is_finalized', true)
            ->where('keputusan', 'LULUS')
            ->firstOrFail();

        $withKan  = request()->boolean('kan');
        $pdf      = $generator->sertifikatPdf($result, $withKan);
        $filename = 'Sertifikat_' . $student->no_participant . '.pdf';

        return response($pdf, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', "inline; filename=\"{$filename}\"");
    }

    /** Tahap 1 — kirim SP saja, supaya peserta bisa mengajukan revisi (mis. typo nama) sebelum SK/Sertifikat resmi terbit. */
    public function distributeSp(ExamSession $examSession)
    {
        // Abaikan baris yatim (akun lama hasil re-issue/merge duplikat, peserta yang
        // sudah dipindah/dikeluarkan dari sesi) — bisa saja masih is_finalized=false
        // dan tidak boleh ikut memblokir pengiriman untuk seluruh sesi.
        $unfinalized = ParticipantResult::where('exam_session_id', $examSession->id)
            ->where('is_finalized', false)
            ->enrolled()
            ->count();

        if ($unfinalized > 0) {
            return redirect()->back()->withErrors(['distribute' => 'Finalisasi semua peserta terlebih dahulu.']);
        }

        \App\Jobs\DistributeSpJob::dispatch($examSession->id);

        return redirect()->back()->with('success', 'Pengiriman SP dijadwalkan dan akan dikirim segera.');
    }

    /** Tahap 2 — kirim SK & Sertifikat (dokumen final). */
    public function distribute(ExamSession $examSession)
    {
        $unfinalized = ParticipantResult::where('exam_session_id', $examSession->id)
            ->where('is_finalized', false)
            ->enrolled()
            ->count();

        if ($unfinalized > 0) {
            return redirect()->back()->withErrors(['distribute' => 'Finalisasi semua peserta terlebih dahulu.']);
        }

        $withKan = request()->boolean('kan');

        // Simpan pilihan KAN dulu sebelum dispatch — SendResultMailJob & download
        // peserta di dashboard-nya sendiri membaca flag ini supaya varian yang
        // benar-benar terkirim via email selalu konsisten dengan yang bisa
        // diunduh ulang oleh peserta (bukan selalu default tanpa-KAN).
        ParticipantResult::where('exam_session_id', $examSession->id)
            ->where('is_finalized', true)
            ->whereNull('distributed_at')
            ->update(['with_kan' => $withKan]);

        \App\Jobs\DistributeResultsJob::dispatch($examSession->id);

        return redirect()->back()->with('success', 'Distribusi SK & Sertifikat dijadwalkan dan akan dikirim segera' . ($withKan ? ' (dengan logo KAN).' : '.'));
    }
}
