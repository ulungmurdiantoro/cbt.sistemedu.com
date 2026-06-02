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
                'nilai_kelulusan'   => 70,
                'bobot_ujian_tulis' => 70,
                'proporsi_pg'       => 65,
                'jumlah_soal_pg'    => 100,
                'durasi_pg_menit'   => 120,
                'jumlah_soal_esai'  => 10,
                'durasi_esai_menit' => 90,
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
            'jumlah_soal_pg'    => 'required|integer|min:1',
            'durasi_pg_menit'   => 'required|integer|min:1',
            'jumlah_soal_esai'  => 'required|integer|min:1',
            'durasi_esai_menit' => 'required|integer|min:1',
            'bobot_wawancara'   => 'required|numeric|min:0|max:100',
        ]);

        $bobot_tulis = $data['bobot_ujian_tulis'];
        $proporsi_pg = $data['proporsi_pg'];

        // Hitung bobot efektif untuk ResultCalculatorService
        $data['bobot_pg']   = round($bobot_tulis * $proporsi_pg / 100, 2);
        $data['bobot_esai'] = round($bobot_tulis * (100 - $proporsi_pg) / 100, 2);

        // Validasi total = 100
        $total = round($bobot_tulis + $data['bobot_wawancara'], 2);
        if (abs($total - 100) > 0.01) {
            return back()->withErrors([
                'bobot_ujian_tulis' => "Total Ujian Tulis + Ujian Lisan harus 100%. Saat ini: {$total}%",
            ]);
        }

        GradingScheme::updateOrCreate(
            ['classroom_id' => $classroom->id],
            $data
        );

        return redirect()->back()->with('success', 'Komposisi nilai berhasil disimpan.');
    }
}
