<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\GradingScheme;
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
                'faktor_wawancara'  => 0.075,
                'nilai_kelulusan'   => 60,
                'bobot_ujian_tulis' => 70,
                'proporsi_pg'       => 65,
            ]
        );

        return inertia('Admin/GradingSchemes/Show', [
            'classroom' => $classroom,
            'scheme'    => $scheme->exists ? $scheme : $scheme->toArray(),
        ]);
    }

    public function save(Request $request, Classroom $classroom)
    {
        $data = $request->validate([
            'bobot_ujian_tulis' => 'required|numeric|min:0|max:100',
            'proporsi_pg'       => 'required|numeric|min:0|max:100',
            'nilai_kelulusan'   => 'required|numeric|min:0|max:100',
            'bobot_wawancara'   => 'required|numeric|min:0|max:100',
            'faktor_wawancara'  => 'required|numeric|min:0|max:1',
        ]);

        $total = round($data['bobot_ujian_tulis'] + $data['bobot_wawancara'], 2);
        if (abs($total - 100) > 0.01) {
            return back()->withErrors([
                'bobot_ujian_tulis' => "Total Ujian Tulis + Ujian Lisan harus 100%. Saat ini: {$total}%",
            ]);
        }

        // Hitung bobot efektif PG dan Esai dari proporsi ujian tulis
        $data['bobot_pg']   = round($data['bobot_ujian_tulis'] * $data['proporsi_pg'] / 100, 2);
        $data['bobot_esai'] = round($data['bobot_ujian_tulis'] * (100 - $data['proporsi_pg']) / 100, 2);

        GradingScheme::updateOrCreate(
            ['classroom_id' => $classroom->id],
            $data
        );

        return redirect()->back()->with('success', 'Komposisi nilai berhasil disimpan.');
    }
}
