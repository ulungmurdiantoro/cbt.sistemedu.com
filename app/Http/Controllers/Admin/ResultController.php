<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AsesorAssignment;
use App\Models\ExamSession;
use App\Models\Grade;
use App\Models\InterviewAssessment;
use App\Models\ParticipantResult;
use App\Models\Student;
use App\Models\StudentTask;
use App\Services\AdminDashboardService;
use App\Services\DocumentGeneratorService;
use App\Services\ResultCalculatorService;
use App\Services\ResultProgressService;
use App\Support\AnswerFile;
use Illuminate\Support\Facades\Storage;

class ResultController extends Controller
{
    public function __construct(
        private ResultCalculatorService $calculator,
        private ResultProgressService $progress,
    ) {}

    public function index(AdminDashboardService $dashboard)
    {
        // Jumlah peserta & progres dihitung dari peserta terdaftar di sesi (akun aktif) — sama dengan
        // baris Rekap Hasil & Dashboard Pengambil Keputusan (ExamSession::scopeWithActiveStudentsCount).
        $sessions = ExamSession::when(request()->q, fn ($q) => $q->where('title', 'like', '%' . request()->q . '%'))
            ->with(['examPg.classroom:id,title', 'examEsai.classroom:id,title', 'pengambilKeputusan:id,name'])
            ->withActiveStudentsCount()
            ->withCount([
                'participantResults as reviewed_count'   => fn ($q) => $q->enrolled()->whereNotNull('manager_verified_at'),
                'participantResults as sp_sent_count'    => fn ($q) => $q->enrolled()->whereNotNull('sp_distributed_at'),
                'participantResults as final_sent_count' => fn ($q) => $q->enrolled()->whereNotNull('distributed_at'),
            ])
            // Waktu sekarang sebagai parameter (bukan NOW()) supaya jalan di MySQL & SQLite.
            ->orderByRaw('CASE WHEN end_time > ? THEN 0 ELSE 1 END ASC', [now()])
            ->orderByRaw('CASE WHEN end_time > ? THEN end_time END ASC', [now()])
            ->orderBy('end_time', 'desc')
            ->paginate(15)
            ->appends(['q' => request()->q]);

        // Progres per tahap (Ujian → Dinilai → PK → MS → Final → SP → SK) — sama dengan Pipeline di dashboard admin.
        $sessions->getCollection()->transform(function (ExamSession $s) use ($dashboard) {
            $s->setAttribute('pipeline', $dashboard->pipelineRow($s));

            return $s;
        });

        return inertia('Admin/Results/Index', ['sessions' => $sessions]);
    }

    public function show(ExamSession $examSession)
    {
        $examSession->load(['examPg.classroom', 'examEsai.classroom', 'pengambilKeputusan:id,name']);

        $results = \Illuminate\Database\Eloquent\Collection::make($this->calculator->recalcForSession($examSession));
        // finalizer = Manager Sertifikasi yang menekan Finalisasi (SertifikasiReviewService::finalize).
        // Pengambil Keputusan sesi = exam_session.pengambil_keputusan (ditunjuk admin).
        $results->load('finalizer:id,name');

        $studentIds = $results->pluck('student_id');
        $students   = Student::whereIn('id', $studentIds)->with('participant', 'classroom')->get()->keyBy('id');

        // Per peserta: daftar unggahan per bagian (Ujian Praktik bisa dua bagian; Upload Tugas = bagian 1)
        $tugas = StudentTask::where('exam_session_id', $examSession->id)
            ->whereIn('student_id', $studentIds)
            ->orderBy('bagian')
            ->get()
            ->groupBy('student_id')
            ->map(fn ($tasks) => $tasks->map(fn (StudentTask $task) => [
                'bagian'            => $task->bagian,
                'original_filename' => $task->original_filename,
            ])->values());

        // ID grade per ujian → tautan "detail jawaban" (eks menu Laporan Nilai, admin.reports.show)
        $gradeIds = Grade::where('exam_session_id', $examSession->id)
            ->whereIn('student_id', $studentIds)
            ->get(['id', 'exam_id', 'student_id'])
            ->groupBy('student_id');

        // Asesor yang ditugaskan per peserta (satu per peserta per sesi — PenilaianController::saveAssignments)
        $assignments = AsesorAssignment::where('exam_session_id', $examSession->id)
            ->whereIn('student_id', $studentIds)
            ->with('asesor:id,name')
            ->get()
            ->keyBy('student_id');

        // Rincian nilai wawancara (4 kriteria + catatan) → modal di kolom Wawancara
        $interviews = $examSession->has_wawancara
            ? InterviewAssessment::where('exam_session_id', $examSession->id)
                ->whereIn('student_id', $studentIds)
                ->with('asesor:id,name')
                ->get()
                ->keyBy('student_id')
            : collect();

        // Ujian Praktik dinilai terpisah per bagian (mis. Lifting) → kolom Praktik + rincian per bagian
        $praktikWeights = \App\Support\NilaiPraktik::weights($examSession);
        $praktikScores = $praktikWeights
            ? \App\Models\PraktikScore::where('exam_session_id', $examSession->id)
                ->whereIn('student_id', $studentIds)
                ->get(['student_id', 'bagian', 'score'])
                ->groupBy('student_id')
                ->map(fn ($rows) => $rows->pluck('score', 'bagian'))
            : collect();

        // Progres per peserta setelah ujian: tahap, yang sedang ditunggu, riwayat, dokumen
        $progress = $this->progress->forSession($examSession, $results);

        $rows = $results->map(function ($r) use ($students, $gradeIds, $assignments, $interviews, $examSession, $progress, $praktikScores) {
            $student = $students->get($r->student_id);
            $grades  = $gradeIds->get($r->student_id, collect());
            $ia      = $interviews->get($r->student_id);
            return [
                'result_id'         => $r->id,
                'student_id'        => $r->student_id,
                'no_participant'    => $student?->no_participant,
                'name'              => $student?->name,
                'asesor_name'       => $assignments->get($r->student_id)?->asesor?->name,
                'nilai_pg'          => $r->nilai_pg,
                'nilai_esai'        => $r->nilai_esai,
                'grade_id_pg'       => $grades->firstWhere('exam_id', $examSession->exam_id_pg)?->id,
                'grade_id_esai'     => $grades->firstWhere('exam_id', $examSession->exam_id_esai)?->id,
                'nilai_wawancara'   => $r->nilai_wawancara,
                'wawancara'         => $ia ? [
                    'gaya_wawancara'              => $ia->gaya_wawancara,
                    'penguasaan_materi'           => $ia->penguasaan_materi,
                    'kemampuan_hadapi_pertanyaan' => $ia->kemampuan_hadapi_pertanyaan,
                    'hasil_worksheet'             => $ia->hasil_worksheet,
                    'total_nilai'                 => $ia->total_nilai,
                    'catatan'                     => $ia->catatan,
                    'asesor_name'                 => $ia->asesor?->name,
                    'updated_at'                  => $ia->updated_at,
                ] : null,
                'nilai_praktik'     => $r->nilai_praktik,
                'praktik'           => $praktikScores->get($r->student_id),
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
                'progress'          => $progress->get($r->student_id),
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
            'show_tugas'   => $tugas->isNotEmpty() || $students->contains(fn (Student $s) => $s->requiresTugasFor($examSession->id)),
            // Judul bagian Ujian Praktik ({bagian: judul}) bila sesi punya lebih dari satu bagian
            'tugas_parts'  => \App\Support\UjianPraktik::partTitles($examSession->id),
            // Bagian praktik yang dinilai terpisah: [{bagian, title, bobot}] — kosong = praktik lewat wawancara
            'praktik_parts' => collect($praktikWeights)->map(fn (float $bobot, int $bagian) => [
                'bagian' => $bagian,
                'title'  => \App\Support\UjianPraktik::examFor($examSession->id, $bagian)?->title,
                'bobot'  => $bobot,
            ])->values(),
            'fr_ak_14_requires_materai' => ParticipantResult::sessionRequiresFrAk14Materai($examSession->id),
            // Urutan & label tahap untuk ringkasan progres dan kolom Progres
            'steps'        => ResultProgressService::STEPS,
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
        $task = $this->task($examSession, $student);

        return inertia('Admin/Results/Tugas', [
            'exam_session' => $examSession->only('id', 'title'),
            'student'      => $student->only('id', 'no_participant', 'name'),
            'tugas'        => $task->previewProps() + ['part_title' => $this->partTitle($examSession, $task)],
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

    /** Judul bagian Ujian Praktik — hanya untuk sesi dengan lebih dari satu bagian. */
    private function partTitle(ExamSession $examSession, StudentTask $task): ?string
    {
        $parts = \App\Support\UjianPraktik::parts($examSession->id);

        return count($parts) > 1 ? ($parts[$task->bagian]->title ?? null) : null;
    }

    /** Unggahan peserta; ?bagian=2 untuk bagian 2 Ujian Praktik (bawaan bagian 1). */
    private function task(ExamSession $examSession, Student $student): StudentTask
    {
        $task = StudentTask::where('exam_session_id', $examSession->id)
            ->where('student_id', $student->id)
            ->where('bagian', request()->integer('bagian', 1))
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
