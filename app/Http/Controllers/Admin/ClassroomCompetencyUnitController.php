<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\ClassroomCompetencyUnit;
use Illuminate\Http\Request;

class ClassroomCompetencyUnitController extends Controller
{
    public function index(Classroom $classroom)
    {
        $units = $classroom->competencyUnits()->orderBy('order')->get();

        return inertia('Admin/Classrooms/CompetencyUnits', [
            'classroom' => $classroom,
            'units'     => $units,
        ]);
    }

    public function store(Request $request, Classroom $classroom)
    {
        $request->validate([
            'kode_unit'    => 'required|string|max:100',
            'judul_unit'   => 'required|string|max:255',
            'judul_unit_en'=> 'nullable|string|max:255',
            'order'        => 'integer|min:0',
        ]);

        $classroom->competencyUnits()->create([
            'kode_unit'     => $request->kode_unit,
            'judul_unit'    => $request->judul_unit,
            'judul_unit_en' => $request->judul_unit_en,
            'order'         => $request->input('order', 0),
        ]);

        return back()->with('success', 'Unit kompetensi berhasil ditambahkan.');
    }

    public function update(Request $request, Classroom $classroom, ClassroomCompetencyUnit $unit)
    {
        abort_if($unit->classroom_id !== $classroom->id, 403);

        $request->validate([
            'kode_unit'     => 'required|string|max:100',
            'judul_unit'    => 'required|string|max:255',
            'judul_unit_en' => 'nullable|string|max:255',
            'order'         => 'integer|min:0',
        ]);

        $unit->update([
            'kode_unit'     => $request->kode_unit,
            'judul_unit'    => $request->judul_unit,
            'judul_unit_en' => $request->judul_unit_en,
            'order'         => $request->input('order', $unit->order),
        ]);

        return back()->with('success', 'Unit kompetensi berhasil diperbarui.');
    }

    public function destroy(Classroom $classroom, ClassroomCompetencyUnit $unit)
    {
        abort_if($unit->classroom_id !== $classroom->id, 403);

        $unit->delete();

        return back()->with('success', 'Unit kompetensi berhasil dihapus.');
    }
}
