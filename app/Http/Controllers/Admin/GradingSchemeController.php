<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Essay;
use App\Models\Exam;
use App\Models\GradingScheme;
use App\Models\Question;
use Illuminate\Http\Request;

class GradingSchemeController extends Controller
{
    public function show(Classroom $classroom)
    {
        $scheme = GradingScheme::firstOrNew(
            ['classroom_id' => $classroom->id],
            [
                'bobot_pg'          => 45.5,
                'bobot_esai'        => 24.5,
                'bobot_wawancara'   => 30,
                'nilai_kelulusan'   => 70,
                'bobot_ujian_tulis' => 70,
                'proporsi_pg'       => 65,
            ]
        );

        // Ambil info dari exam PG dan Esai terkait classroom ini
        $examPg   = Exam::where('classroom_id', $classroom->id)->where('type', 'Pilihan Ganda')->latest()->first();
        $examEsai = Exam::where('classroom_id', $classroom->id)->whereIn('type', ['Essay', 'Essay Migas'])->latest()->first();

        $examInfo = [
            'pg' => $examPg ? [
                'title'        => $examPg->title,
                'duration'     => $examPg->duration,
                'jumlah_soal'  => Question::where('exam_id', $examPg->id)->count(),
            ] : null,
            'esai' => $examEsai ? [
                'title'        => $examEsai->title,
                'duration'     => $examEsai->duration,
                'jumlah_soal'  => Essay::where('exam_id', $examEsai->id)->count(),
            ] : null,
        ];

        return inertia('Admin/GradingSchemes/Show', [
            'classroom' => $classroom,
            'scheme'    => $scheme->exists ? $scheme : $scheme->toArray(),
            'exam_info' => $examInfo,
        ]);
    }

    public function save(Request $request, Classroom $classroom)
    {
        $data = $request->validate([
            'bobot_ujian_tulis' => 'required|numeric|min:0|max:100',
            'proporsi_pg'       => 'required|numeric|min:0|max:100',
            'nilai_kelulusan'   => 'required|numeric|min:0|max:100',
            'bobot_wawancara'   => 'required|numeric|min:0|max:100',
        ]);

        $total = round($data['bobot_ujian_tulis'] + $data['bobot_wawancara'], 2);
        if (abs($total - 100) > 0.01) {
            return back()->withErrors([
                'bobot_ujian_tulis' => "Total Ujian Tulis + Ujian Lisan harus 100%. Saat ini: {$total}%",
            ]);
        }

        // Hitung bobot efektif untuk ResultCalculatorService
        $data['bobot_pg']   = round($data['bobot_ujian_tulis'] * $data['proporsi_pg'] / 100, 2);
        $data['bobot_esai'] = round($data['bobot_ujian_tulis'] * (100 - $data['proporsi_pg']) / 100, 2);

        GradingScheme::updateOrCreate(
            ['classroom_id' => $classroom->id],
            $data
        );

        return redirect()->back()->with('success', 'Komposisi nilai berhasil disimpan.');
    }
}
