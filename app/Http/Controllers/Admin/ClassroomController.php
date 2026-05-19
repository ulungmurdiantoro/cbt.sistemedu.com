<?php

namespace App\Http\Controllers\Admin;

use App\Models\Classroom;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ClassroomController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //get classrooms
        $classrooms = Classroom::when(request()->q, function($classrooms) {
            $classrooms = $classrooms->where('title', 'like', '%'. request()->q . '%');
        })->latest()->paginate(10);

        //append query string to pagination links
        $classrooms->appends(['q' => request()->q]);

        //render with inertia
        return inertia('Admin/Classrooms/Index', [
            'classrooms' => $classrooms,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //render with inertia
        return inertia('Admin/Classrooms/Create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'title'      => 'required|string|unique:classrooms',
            'title_en'   => 'nullable|string|max:255',
            'kode_skema' => 'nullable|string|max:100',
            'gelar'      => 'nullable|string|max:100',
        ]);

        Classroom::create([
            'classrooms_code' => 'clsr-' . rand(11, 99) . uniqid(),
            'kode_skema'      => $request->kode_skema,
            'gelar'           => $request->gelar,
            'title'           => $request->title,
            'title_en'        => $request->title_en,
        ]);

        //redirect
        return redirect()->route('admin.classrooms.index');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($classrooms_code)
    {
        //get classroom
        $classroom = Classroom::where('classrooms_code', $classrooms_code)->firstOrFail();

        //render with inertia
        return inertia('Admin/Classrooms/Edit', [
            'classroom' => $classroom,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Classroom $classroom)
    {
        $request->validate([
            'title'      => 'required|string|unique:classrooms,title,'.$classroom->id,
            'title_en'   => 'nullable|string|max:255',
            'kode_skema' => 'nullable|string|max:100',
            'gelar'      => 'nullable|string|max:100',
        ]);

        $classroom->update([
            'kode_skema' => $request->kode_skema,
            'gelar'      => $request->gelar,
            'title'      => $request->title,
            'title_en'   => $request->title_en,
        ]);

        //redirect
        return redirect()->route('admin.classrooms.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //get classroom
        $classroom = Classroom::findOrFail($id);

        //delete classroom
        $classroom->delete();

        //redirect
        return redirect()->route('admin.classrooms.index');
    }
}