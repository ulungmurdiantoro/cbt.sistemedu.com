<?php

namespace App\Http\Controllers\Asesor;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInterviewAssessmentRequest;
use App\Models\AsesorAssignment;
use App\Models\ExamSession;
use App\Models\GradingScheme;
use App\Models\InterviewAssessment;
use App\Models\Student;
use App\Models\StudentTask;
use App\Support\AnswerFile;

class InterviewAssessmentController extends Controller
{
    /**
     * Bobot wawancara sebagai pecahan (mis. 30% -> 0.30), murni untuk tampilan
     * "Bobot: X% dari total nilai ujian" di halaman penilaian. Nilai_wawancara
     * yang disimpan di sini SELALU skala 0-100 mentah (rata-rata 4 kriteria) —
     * bobot ini baru diterapkan sekali oleh ResultCalculatorService saat
     * menghitung nilai_akhir. Jangan dikalikan lagi di sini (dulu ada bug
     * double-weighting lewat faktor_wawancara — lihat riwayat commit).
     */
    private function getBobotWawancaraFraction(int $examSessionId): float
    {
        $session     = ExamSession::find($examSessionId);
        $classroomId = $session?->referenceExam?->classroom_id;
        $scheme      = $classroomId
            ? GradingScheme::where('classroom_id', $classroomId)->first()
            : null;

        return (float) ($scheme?->bobot_wawancara ?? 30) / 100;
    }

    public function show(int $exam_session_id)
    {
        $asesor       = auth()->user();
        $exam_session = ExamSession::with('examPg.classroom', 'examEsai.classroom')->findOrFail($exam_session_id);

        $assigned_student_ids = AsesorAssignment::where('user_id', $asesor->id)
            ->where('exam_session_id', $exam_session_id)
            ->pluck('student_id');

        abort_if($assigned_student_ids->isEmpty(), 403, 'Anda tidak ditugaskan pada sesi ini.');

        // Sembunyikan akun peserta yang sudah dinonaktifkan (mis. akun lama hasil
        // re-issue) supaya nama tidak tampil dobel saat menilai.
        $assigned_student_ids = Student::whereIn('id', $assigned_student_ids)
            ->where('is_active', true)
            ->pluck('id');

        $students = Student::whereIn('id', $assigned_student_ids)
            ->orderBy('no_participant')
            ->get();

        $assessments = InterviewAssessment::where('exam_session_id', $exam_session_id)
            ->whereIn('student_id', $assigned_student_ids)
            ->get()
            ->keyBy('student_id');

        // Per peserta: daftar unggahan per bagian (Ujian Praktik/Keterampilan bisa dua bagian; Upload Tugas = bagian 1)
        $tugas = StudentTask::where('exam_session_id', $exam_session_id)
            ->whereIn('student_id', $assigned_student_ids)
            ->orderBy('bagian')
            ->get()
            ->groupBy('student_id')
            ->map(fn ($tasks) => $tasks->map(fn (StudentTask $task) => [
                'bagian'            => $task->bagian,
                'original_filename' => $task->original_filename,
            ])->values());

        return inertia('Asesor/Wawancara/Show', [
            'exam_session' => $exam_session,
            'students'     => $students,
            'assessments'  => $assessments,
            'tugas'        => $tugas,
            // Judul bagian Ujian Praktik/Keterampilan ({bagian: judul}) bila sesi punya lebih dari satu bagian
            'tugas_parts'  => \App\Support\UjianPraktik::partTitles($exam_session_id),
            'bobot'        => $this->getBobotWawancaraFraction($exam_session_id),
            // Keterampilan dinilai terpisah di halaman Nilai Praktik/Keterampilan → kriteria (D) Worksheet tidak dipakai
            'praktik_terpisah' => \App\Support\NilaiPraktik::applies($exam_session),
        ]);
    }

    /**
     * Halaman pratinjau tugas (dibuka di tab baru). Isi file diambil halaman lewat
     * XHR dari fileTugas lalu ditampilkan dari blob — link file langsung sering
     * dicegat browser/download manager dan malah diunduh.
     */
    public function previewTugas(int $exam_session_id, int $student_id)
    {
        $task  = $this->assignedTask($exam_session_id, $student_id);
        $parts = \App\Support\UjianPraktik::parts($exam_session_id);

        return inertia('Asesor/Wawancara/Tugas', [
            'exam_session' => ExamSession::select('id', 'title')->findOrFail($exam_session_id),
            'student'      => Student::select('id', 'no_participant', 'name')->findOrFail($student_id),
            // Judul bagian hanya untuk Ujian Praktik/Keterampilan dengan lebih dari satu bagian
            'tugas'        => $task->previewProps() + ['part_title' => count($parts) > 1 ? ($parts[$task->bagian]->title ?? null) : null],
        ]);
    }

    public function fileTugas(int $exam_session_id, int $student_id)
    {
        $task = $this->assignedTask($exam_session_id, $student_id);

        return AnswerFile::preview($task->file_path, $task->original_filename);
    }

    public function downloadTugas(int $exam_session_id, int $student_id)
    {
        $task = $this->assignedTask($exam_session_id, $student_id);

        return AnswerFile::download($task->file_path, $task->original_filename);
    }

    private function assignedTask(int $exam_session_id, int $student_id): StudentTask
    {
        $asesor = auth()->user();

        $assigned = AsesorAssignment::where('user_id', $asesor->id)
            ->where('exam_session_id', $exam_session_id)
            ->where('student_id', $student_id)
            ->exists();

        abort_unless($assigned, 403, 'Anda tidak ditugaskan untuk menilai peserta ini.');

        // ?bagian=2 untuk bagian 2 Ujian Praktik/Keterampilan (bawaan bagian 1)
        $task = StudentTask::where('exam_session_id', $exam_session_id)
            ->where('student_id', $student_id)
            ->where('bagian', request()->integer('bagian', 1))
            ->first();

        abort_unless($task, 404, 'Tugas tidak ditemukan.');

        return $task;
    }

    public function store(StoreInterviewAssessmentRequest $request, int $exam_session_id)
    {
        $asesor = auth()->user();

        $assigned_student_ids = AsesorAssignment::where('user_id', $asesor->id)
            ->where('exam_session_id', $exam_session_id)
            ->pluck('student_id')
            ->all();

        // Keterampilan dinilai terpisah (halaman Nilai Praktik/Keterampilan) → total wawancara = rata-rata 3 kriteria lisan
        $praktikTerpisah = \App\Support\NilaiPraktik::applies(ExamSession::findOrFail($exam_session_id));

        foreach ($request->assessments as $item) {
            if ($praktikTerpisah) {
                $item['hasil_worksheet'] = null;
            }

            abort_unless(
                in_array($item['student_id'], $assigned_student_ids),
                403,
                'Anda tidak ditugaskan untuk menilai peserta ini.'
            );

            // total_nilai = rata-rata 4 kriteria (skala 0-100), SEJAJAR dengan
            // nilai_pg & nilai_esai — bobot_wawancara diterapkan sekali oleh
            // ResultCalculatorService, bukan di sini (dulu dikali faktor_wawancara
            // lagi di sini -> bobot wawancara kepakai dua kali).
            $vals = collect([
                $item['gaya_wawancara'],
                $item['penguasaan_materi'],
                $item['kemampuan_hadapi_pertanyaan'],
                $item['hasil_worksheet'],
            ])->filter(fn($v) => $v !== null);
            $avg = $vals->count() > 0 ? round($vals->avg(), 2) : null;

            InterviewAssessment::updateOrCreate(
                [
                    'exam_session_id' => $exam_session_id,
                    'student_id'      => $item['student_id'],
                ],
                [
                    'asesor_id'                   => $asesor->id,
                    'gaya_wawancara'              => $item['gaya_wawancara'],
                    'penguasaan_materi'           => $item['penguasaan_materi'],
                    'kemampuan_hadapi_pertanyaan' => $item['kemampuan_hadapi_pertanyaan'],
                    'hasil_worksheet'             => $item['hasil_worksheet'],
                    'total_nilai'                 => $avg,
                    'catatan'                     => $item['catatan'] ?? null,
                ]
            );
        }

        return back()->with('success', 'Nilai wawancara berhasil disimpan.');
    }
}
