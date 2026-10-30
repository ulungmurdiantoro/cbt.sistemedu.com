<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Log Materai: riwayat pembubuhan e-meterai FR.AK.14 (participant_results) dan FR.AK.01
 * (assessment_applications) dalam satu daftar, terbaru di atas. Hanya membaca — tidak ada
 * tabel log terpisah, sumbernya kolom materai_* di kedua tabel itu.
 */
class MateraiLogController extends Controller
{
    /** Status yang dianggap "sedang diproses" (antrian / menunggu Peruri). */
    private const PROSES = ['processing', 'pending_payment', 'paid'];

    public function index(Request $request)
    {
        $rows = DB::query()
            ->fromSub($this->unionQuery(), 'm')
            ->when($request->dokumen, fn ($q, $d) => $q->where('dokumen', $d))
            ->when($request->status, fn ($q, $s) => $s === 'proses'
                ? $q->whereIn('status', self::PROSES)
                : $q->where('status', $s))
            ->when($request->q, fn ($q, $k) => $q->where(fn ($w) => $w
                ->where('nama', 'like', "%{$k}%")
                ->orWhere('no_peserta', 'like', "%{$k}%")
                ->orWhere('sesi', 'like', "%{$k}%")))
            ->orderByDesc('waktu')
            ->paginate(20)
            ->withQueryString();

        $summary = DB::query()->fromSub($this->unionQuery(), 'm')
            ->selectRaw("SUM(CASE WHEN status = 'stamped' THEN 1 ELSE 0 END) AS stamped")
            ->selectRaw("SUM(CASE WHEN status = 'stamped' AND stamped_at >= ? THEN 1 ELSE 0 END) AS today", [now()->startOfDay()])
            ->selectRaw("SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) AS failed")
            ->selectRaw("SUM(CASE WHEN status IN ('" . implode("','", self::PROSES) . "') THEN 1 ELSE 0 END) AS proses")
            ->first();

        return inertia('Admin/Materai/Index', [
            'rows'    => $rows,
            'summary' => [
                'stamped' => (int) $summary->stamped,
                'today'   => (int) $summary->today,
                'failed'  => (int) $summary->failed,
                'proses'  => (int) $summary->proses,
            ],
            'filters' => $request->only('q', 'dokumen', 'status'),
        ]);
    }

    private function unionQuery()
    {
        $frAk14 = DB::table('participant_results as pr')
            ->join('students as s', 's.id', '=', 'pr.student_id')
            ->leftJoin('exam_sessions as es', 'es.id', '=', 'pr.exam_session_id')
            ->where('pr.materai_status', '!=', 'none')
            ->selectRaw("'FR.AK.14' AS dokumen, pr.id AS ref_id, pr.exam_session_id AS sesi_id, es.title AS sesi,
                s.id AS student_id, s.name AS nama, s.no_participant AS no_peserta,
                pr.materai_status AS status, pr.materai_stamped_at AS stamped_at, pr.materai_failure_reason AS alasan,
                COALESCE(pr.materai_stamped_at, pr.updated_at) AS waktu");

        $frAk01 = DB::table('assessment_applications as aa')
            ->leftJoin('participants as p', 'p.id', '=', 'aa.participant_id')
            ->leftJoin('students as s', 's.id', '=', 'aa.student_id')
            ->leftJoin('exam_sessions as es', 'es.id', '=', 'aa.exam_session_id')
            ->where('aa.materai_status', '!=', 'none')
            ->selectRaw("'FR.AK.01' AS dokumen, aa.id AS ref_id, aa.exam_session_id AS sesi_id, es.title AS sesi,
                aa.student_id AS student_id, COALESCE(s.name, p.name) AS nama, s.no_participant AS no_peserta,
                aa.materai_status AS status, aa.materai_stamped_at AS stamped_at, aa.materai_failure_reason AS alasan,
                COALESCE(aa.materai_stamped_at, aa.updated_at) AS waktu");

        return $frAk14->unionAll($frAk01);
    }
}
