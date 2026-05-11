<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\ClassroomDocumentRequirement;
use Illuminate\Http\Request;

class ClassroomRequirementController extends Controller
{
    public function index(Classroom $classroom)
    {
        $requirements = $classroom->documentRequirements()->orderBy('order')->get();

        return inertia('Admin/Classrooms/Requirements', [
            'classroom'    => $classroom,
            'requirements' => $requirements,
        ]);
    }

    public function store(Request $request, Classroom $classroom)
    {
        $request->validate([
            'code'        => 'required|string|max:50',
            'label'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_required' => 'boolean',
            'order'       => 'integer|min:0',
        ]);

        $classroom->documentRequirements()->create([
            'code'        => $request->code,
            'label'       => $request->label,
            'description' => $request->description,
            'is_required' => $request->boolean('is_required', true),
            'order'       => $request->input('order', 0),
        ]);

        return back()->with('success', 'Persyaratan berhasil ditambahkan.');
    }

    public function update(Request $request, Classroom $classroom, ClassroomDocumentRequirement $requirement)
    {
        abort_if($requirement->classroom_id !== $classroom->id, 403);

        $request->validate([
            'code'        => 'required|string|max:50',
            'label'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_required' => 'boolean',
            'order'       => 'integer|min:0',
        ]);

        $requirement->update([
            'code'        => $request->code,
            'label'       => $request->label,
            'description' => $request->description,
            'is_required' => $request->boolean('is_required', true),
            'order'       => $request->input('order', $requirement->order),
        ]);

        return back()->with('success', 'Persyaratan berhasil diperbarui.');
    }

    public function destroy(Classroom $classroom, ClassroomDocumentRequirement $requirement)
    {
        abort_if($requirement->classroom_id !== $classroom->id, 403);

        $requirement->delete();

        return back()->with('success', 'Persyaratan berhasil dihapus.');
    }
}
