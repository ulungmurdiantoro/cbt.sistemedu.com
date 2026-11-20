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
use Illuminate\Support\Collection;

/**
 * Nilai Ujian Praktik (mis. Corporate Legal Studi Kasus 2, Lifting Calculation & Gambar) — hanya untuk sesi yang
 * Komposisi Nilai skemanya memberi bobot praktik (NilaiPraktik::weights).
 * - show  : daftar peserta (baca saja) — progres aspek, nilai per bagian, tombol Nilai;
 * - grade : nilai satu peserta — kartu per aspek rubrik (kriteria per rentang skor + nilai + catatan), atau satu
 *           isian per bagian tanpa aspek; navigasi Sebelumnya / Simpan & Berikutnya (urut No. Peserta);
 * - store : simpan. Nilai bagian beraspek dihitung server (NilaiPraktik::aspekScore). Nilai disimpan mentah 0–100;
 *           bobot diterapkan sekali oleh ResultCalculatorService.
 */
class PraktikScoreController extends Controller
{
    public function show(int $exam_session_id)
    {
        [$exam_session, $weights, $students] = $this->context($exam_session_id);
        $parts = $this->parts($exam_session_id, $weights);

        $scores = PraktikScore::where('exam_session_id', $exam_session_id)
            ->whereIn('student_id', $students->pluck('id'))
            ->get()
            ->groupBy('student_id');
        $uploads = $this->uploads($exam_session_id, $students->pluck('id'));

        $rows = $students->map(function (Student $s) use ($parts, $weights, $scores, $uploads) {
            $mine = $scores->get($s->id, collect())->keyBy('bagian');
            $perPart = $parts->map(function (array $p) use ($mine, $uploads, $s) {
                $ps = $mine->get($p['bagian']);
                $filled = $p['aspek'] ? collect($ps?->aspek ?? [])->filter(fn ($v) => $v !== null)->count() : null;

                return [
                    'bagian' => $p['bagian'],
                    'score'  => $ps?->score,
                    'filled' => $filled,
                    'upload' => $uploads[$s->id][$p['bagian']] ?? null,
                ];
            })->values();
            [$total] = NilaiPraktik::combine($perPart->pluck('score', 'bagian')->all(), $weights);

            return [
                'id'             => $s->id,
                'no_participant' => $s->no_participant,
                'name'           => $s->name,
                'parts'          => $perPart,
                'total'          => $total,
            ];
        });

        return inertia('Asesor/Praktik/Show', [
            'exam_session' => $exam_session,
            'parts'        => $parts->map(fn (array $p) => collect($p)->except('rubrik')->put('aspek_count', count($p['aspek']))->except('aspek'))->values(),
            'rows'         => $rows,
        ]);
    }

    public function grade(int $exam_session_id, int $student_id)
    {
        [$exam_session, $weights, $students] = $this->context($exam_session_id);

        $index = $students->search(fn (Student $s) => $s->id === $student_id);
        abort_if($index === false, 403, 'Anda tidak ditugaskan untuk menilai peserta ini.');
        $student = $students[$index];

        $scores = PraktikScore::where('exam_session_id', $exam_session_id)
            ->where('student_id', $student->id)
            ->get()
            ->keyBy('bagian');
        $uploads = $this->uploads($exam_session_id, collect([$student->id]))[$student->id] ?? [];

        return inertia('Asesor/Praktik/Nilai', [
            'exam_session' => $exam_session,
            'student'      => $student->only('id', 'no_participant', 'name'),
            'parts'        => $this->parts($exam_session_id, $weights)->map(fn (array $p) => $p + [
                'score'   => $scores->get($p['bagian'])?->score,
                'nilai'   => (object) ($scores->get($p['bagian'])?->aspek ?? []),
                'catatan' => (object) ($scores->get($p['bagian'])?->catatan ?? []),
                // {no aspek: {no butir: nilai}} — aspek yang dinilai per butir
                'rincian' => (object) ($scores->get($p['bagian'])?->rincian ?? []),
                'upload'  => $uploads[$p['bagian']] ?? null,
            ])->values(),
            'nav'          => [
                'position' => $index + 1,
                'count'    => $students->count(),
                'prev_id'  => $students[$index - 1]->id ?? null,
                'next_id'  => $students[$index + 1]->id ?? null,
            ],
        ]);
    }

    public function store(Request $request, int $exam_session_id)
    {
        [, $weights, $students] = $this->context($exam_session_id);
        $assigned = $students->pluck('id')->all();
        $exams = UjianPraktik::parts($exam_session_id);

        $request->validate([
            'scores'                => ['required', 'array'],
            'scores.*.student_id'   => ['required', 'integer'],
            'scores.*.nilai'        => ['nullable', 'array'],
            'scores.*.nilai.*'      => ['nullable', 'numeric', 'min:0', 'max:100'],
            'scores.*.aspek'        => ['nullable', 'array'],
            'scores.*.aspek.*'      => ['nullable', 'array'],
            'scores.*.aspek.*.*'    => ['nullable', 'numeric', 'min:0', 'max:100'],
            'scores.*.rincian'      => ['nullable', 'array'],
            'scores.*.catatan'      => ['nullable', 'array'],
            'scores.*.catatan.*'    => ['nullable', 'array'],
            'scores.*.catatan.*.*'  => ['nullable', 'string', 'max:1000'],
        ], [
            'scores.*.aspek.*.*.numeric' => 'Nilai aspek harus berupa angka.',
            'scores.*.aspek.*.*.min'     => 'Nilai aspek minimal 0.',
            'scores.*.aspek.*.*.max'     => 'Nilai aspek maksimal 100.',
            'scores.*.nilai.*.numeric'   => 'Nilai praktik harus berupa angka.',
            'scores.*.nilai.*.min'       => 'Nilai praktik minimal 0.',
            'scores.*.nilai.*.max'       => 'Nilai praktik maksimal 100.',
            'scores.*.catatan.*.*.max'   => 'Catatan maksimal 1000 karakter.',
        ]);

        foreach ($request->input('scores') as $item) {
            abort_unless(in_array((int) $item['student_id'], $assigned, true), 403, 'Anda tidak ditugaskan untuk menilai peserta ini.');

            foreach (array_keys($weights) as $bagian) {
                $key = ['exam_session_id' => $exam_session_id, 'student_id' => (int) $item['student_id'], 'bagian' => $bagian];

                // Dinilai per aspek rubrik: nilai bagian dihitung server dari nilai aspek (null selama belum lengkap)
                if ($aspek = $exams[$bagian]->praktik_aspek ?? null) {
                    $nilaiAspek = [];
                    $rincian = [];
                    $catatan = [];
                    foreach ($aspek as $a) {
                        if (!empty($a['item'])) {
                            // Dinilai per butir: hanya nilai pilihan yang sah; nilai aspek = jumlah butir
                            $allowed = array_map('floatval', array_column($a['pilihan'], 'nilai'));
                            $pilih = [];
                            foreach ($a['item'] as $butir) {
                                $v = $item['rincian'][$bagian][$a['no']][$butir['no']] ?? null;
                                if ($v !== null && $v !== '') {
                                    abort_unless(in_array((float) $v, $allowed, true), 422, 'Pilihan nilai butir tidak sah.');
                                    $pilih[$butir['no']] = (float) $v;
                                }
                            }
                            if ($pilih) {
                                $rincian[$a['no']] = $pilih;
                            }
                            $nilaiAspek[$a['no']] = NilaiPraktik::itemScore($a, $pilih);
                        } else {
                            $v = $item['aspek'][$bagian][$a['no']] ?? null;
                            $nilaiAspek[$a['no']] = ($v === null || $v === '') ? null : round((float) $v, 2);

                            // Gerbang kritis gagal (mis. ESG) → nilai aspek dibatasi maks_gerbang (50)
                            if (!empty($a['gerbang']) && !empty($item['rincian'][$bagian][$a['no']]['gerbang'])) {
                                $rincian[$a['no']] = ['gerbang' => true];
                                $maks = (float) ($a['maks_gerbang'] ?? 50);
                                abort_if($nilaiAspek[$a['no']] !== null && $nilaiAspek[$a['no']] > $maks, 422,
                                    "Gerbang kritis {$a['nama']} gagal: nilai maksimal {$maks}.");
                            }
                        }
                        $c = trim((string) ($item['catatan'][$bagian][$a['no']] ?? ''));
                        if ($c !== '') {
                            $catatan[$a['no']] = $c;
                        }
                    }

                    if (collect($nilaiAspek)->filter(fn ($v) => $v !== null)->isEmpty() && !$catatan && !$rincian) {
                        PraktikScore::where($key)->update(['score' => null, 'aspek' => null, 'rincian' => null, 'catatan' => null, 'asesor_id' => auth()->id()]);
                        continue;
                    }

                    PraktikScore::updateOrCreate($key, [
                        'aspek'     => $nilaiAspek,
                        'rincian'   => $rincian ?: null,
                        'catatan'   => $catatan ?: null,
                        'score'     => NilaiPraktik::aspekScore($aspek, $nilaiAspek),
                        'asesor_id' => auth()->id(),
                    ]);
                    continue;
                }

                $value = $item['nilai'][$bagian] ?? null;
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

    /** Sesi, bobot praktik (404 bila tidak berlaku) dan peserta aktif yang ditugaskan, urut No. Peserta. */
    private function context(int $sessionId): array
    {
        $session = ExamSession::with('examPg.classroom', 'examEsai.classroom', 'examPraktik.classroom')->findOrFail($sessionId);
        $weights = NilaiPraktik::weights($session);
        abort_if($weights === [], 404, 'Sesi ini tidak memakai Nilai Praktik terpisah.');

        $ids = AsesorAssignment::where('user_id', auth()->id())
            ->where('exam_session_id', $sessionId)
            ->pluck('student_id');
        abort_if($ids->isEmpty(), 403, 'Anda tidak ditugaskan pada sesi ini.');

        $students = Student::whereIn('id', $ids)
            ->where('is_active', true)
            ->orderBy('no_participant')
            ->get(['id', 'no_participant', 'name'])
            ->values();

        return [$session, $weights, $students];
    }

    /** Bagian praktik yang dinilai: judul, durasi, bobot, rubrik (HTML) & aspek [{no, nama, bobot, kriteria}]. */
    private function parts(int $sessionId, array $weights): Collection
    {
        $exams = UjianPraktik::parts($sessionId);

        return collect($weights)->map(fn (float $bobot, int $bagian) => [
            'bagian' => $bagian,
            'title'  => $exams[$bagian]->title,
            'durasi' => (int) $exams[$bagian]->duration ?: null,
            'bobot'  => $bobot,
            // Rubrik penilaian (FR.IA.04) — panduan asesor; tidak pernah dikirim ke peserta
            'rubrik' => $exams[$bagian]->praktik_rubrik,
            'aspek'  => $exams[$bagian]->praktik_aspek ?: [],
        ])->values();
    }

    /** {student_id: {bagian: nama file}} — hasil unggahan per bagian. */
    private function uploads(int $sessionId, Collection $studentIds): array
    {
        return StudentTask::where('exam_session_id', $sessionId)
            ->whereIn('student_id', $studentIds)
            ->get(['student_id', 'bagian', 'original_filename'])
            ->groupBy('student_id')
            ->map(fn ($rows) => $rows->pluck('original_filename', 'bagian')->all())
            ->all();
    }
}
