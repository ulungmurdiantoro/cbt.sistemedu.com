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
            ['bobot_pg' => 40, 'bobot_esai' => 35, 'bobot_wawancara' => 25, 'nilai_kelulusan' => 70]
        );

        return inertia('Admin/GradingSchemes/Show', [
            'classroom' => $classroom,
            'scheme'    => $scheme->exists ? $scheme : $scheme->toArray(),
        ]);
    }

    public function save(Request $request, Classroom $classroom)
    {
        $data = $request->validate([
            'bobot_pg'        => 'required|numeric|min:0|max:100',
            'bobot_esai'      => 'required|numeric|min:0|max:100',
            'bobot_wawancara' => 'required|numeric|min:0|max:100',
            'nilai_kelulusan' => 'required|numeric|min:0|max:100',
        ]);

        $total = $data['bobot_pg'] + $data['bobot_esai'] + $data['bobot_wawancara'];
        if (abs($total - 100) > 0.01) {
            return back()->withErrors(['bobot_pg' => 'Total bobot harus 100%. Saat ini: ' . $total . '%']);
        }

        GradingScheme::updateOrCreate(
            ['classroom_id' => $classroom->id],
            $data
        );

        return redirect()->back()->with('success', 'Komposisi nilai berhasil disimpan.');
    }
}
