<?php

namespace App\Http\Controllers\Asesor;

use App\Http\Controllers\Controller;
use App\Models\AsesorAssignment;
use App\Models\ExamSession;
use App\Models\PraktikScore;
use App\Models\Student;
use App\Models\StudentTask;
use App\Support\NilaiPraktik;
use App\Support\UjianPraktik;
use Illuminate\Http\Request;

/**
 * Nilai Ujian Praktik per bagian (mis. Lifting: Calculation & Gambar) — hanya untuk sesi yang Komposisi Nilai
 * skemanya memberi bobot praktik (NilaiPraktik::weights). Nilai disimpan mentah 0–100 per bagian; bobot
 * diterapkan sekali oleh ResultCalculatorService.
 */
class PraktikScoreController extends Controller
{
    public function show(int $exam_session_id)
    {
        $exam_session = ExamSession::with('examPg.classroom', 'examEsai.classroom', 'examPraktik.classroom')->findOrFail($exam_session_id);
        $weights = $this->weightsOrAbort($exam_session);
        $studentIds = $this->assignedStudentIds($exam_session_id);

        $students = Student::whereIn('id', $studentIds)
            ->where('is_active', true)
            ->orderBy('no_participant')
            ->get(['id', 'no_participant', 'name']);

        // {student_id: {bagian: nilai}}
        $scores = PraktikScore::where('exam_session_id', $exam_session_id)
            ->whereIn('student_id', $students->pluck('id'))
            ->get(['student_id', 'bagian', 'score'])
            ->groupBy('student_id')
            ->map(fn ($rows) => $rows->pluck('score', 'bagian'));

        // {student_id: {bagian: nama file}} — tombol lihat hasil unggahan per bagian
        $tugas = StudentTask::where('exam_session_id', $exam_session_id)
            ->whereIn('student_id', $students->pluck('id'))
            ->get(['student_id', 'bagian', 'original_filename'])
            ->groupBy('student_id')
            ->map(fn ($rows) => $rows->pluck('original_filename', 'bagian'));

        $exams = UjianPraktik::parts($exam_session_id);

        return inertia('Asesor/Praktik/Show', [
            'exam_session' => $exam_session,
            'students'     => $students,
            'parts'        => collect($weights)->map(fn (float $bobot, int $bagian) => [
                'bagian' => $bagian,
                'title'  => $exams[$bagian]->title,
                'durasi' => (int) $exams[$bagian]->duration ?: null,
                'bobot'  => $bobot,
            ])->values(),
            'scores'       => $scores,
            'tugas'        => $tugas,
        ]);
    }

    public function store(Request $request, int $exam_session_id)
    {
        $exam_session = ExamSession::findOrFail($exam_session_id);
        $weights = $this->weightsOrAbort($exam_session);
        $assigned = $this->assignedStudentIds($exam_session_id)->all();

        $request->validate([
            'scores'              => ['required', 'array'],
            'scores.*.student_id' => ['required', 'integer'],
            'scores.*.nilai'      => ['nullable', 'array'],
            'scores.*.nilai.*'    => ['nullable', 'numeric', 'min:0', 'max:100'],
        ], [
            'scores.*.nilai.*.numeric' => 'Nilai praktik harus berupa angka.',
            'scores.*.nilai.*.min'     => 'Nilai praktik minimal 0.',
            'scores.*.nilai.*.max'     => 'Nilai praktik maksimal 100.',
        ]);

        foreach ($request->input('scores') as $item) {
            abort_unless(in_array((int) $item['student_id'], $assigned, true), 403, 'Anda tidak ditugaskan untuk menilai peserta ini.');

            foreach (array_keys($weights) as $bagian) {
                $value = $item['nilai'][$bagian] ?? null;
                $key = ['exam_session_id' => $exam_session_id, 'student_id' => (int) $item['student_id'], 'bagian' => $bagian];

                if ($value === null || $value === '') {
                    // Dikosongkan: hapus nilai yang ada, jangan buat baris kosong baru
                    PraktikScore::where($key)->update(['score' => null, 'asesor_id' => auth()->id()]);
                    continue;
                }

                PraktikScore::updateOrCreate($key, ['score' => round((float) $value, 2), 'asesor_id' => auth()->id()]);
            }
        }

        return back()->with('success', 'Nilai praktik berhasil disimpan.');
    }

    private function weightsOrAbort(ExamSession $session): array
    {
        $weights = NilaiPraktik::weights($session);
        abort_if($weights === [], 404, 'Sesi ini tidak memakai Nilai Praktik terpisah.');

        return $weights;
    }

    private function assignedStudentIds(int $sessionId)
    {
        $ids = AsesorAssignment::where('user_id', auth()->id())
            ->where('exam_session_id', $sessionId)
            ->pluck('student_id')
            ->map(fn ($id) => (int) $id);

        abort_if($ids->isEmpty(), 403, 'Anda tidak ditugaskan pada sesi ini.');

        return $ids;
    }
}
