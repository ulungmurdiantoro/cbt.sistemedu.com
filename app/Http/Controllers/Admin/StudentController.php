<?php

namespace App\Http\Controllers\Admin;

use App\Models\Student;
use App\Models\Classroom;
use App\Models\ExamGroup;
use App\Models\ParticipantResult;
use Illuminate\Http\Request;
use App\Imports\StudentsImport;
use App\Exports\StudentsCertificationExport;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;

class StudentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //get students
        $students = Student::when(request()->q, function($students) {
            $students = $students->where('name', 'like', '%'. request()->q . '%');
        })->with('classroom')->latest()->paginate(10);

        //append query string to pagination links
        $students->appends(['q' => request()->q]);

        //render with inertia
        return inertia('Admin/Students/Index', [
            'students' => $students,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //get classrooms
        $classrooms = Classroom::all();

        //render with inertia
        return inertia('Admin/Students/Create', [
            'classrooms' => $classrooms,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //validate request
        $request->validate([
            'name'              => 'required|string|max:255',  
            'no_participant'    => 'required|unique:students',
            'gender'            => 'required|string',
            'position'          => 'required',
            'institution'       => 'required',
            'classroom_id'      => 'required'
        ]);

        //create student
        Student::create([
            'no_participant'    => $request->no_participant,
            'name'              => $request->name,
            'gender'            => $request->gender,
            'position'          => $request->position,
            'institution'       => $request->institution,
            'classroom_id'      => $request->classroom_id
        ]);

        //redirect
        return redirect()->route('admin.students.index');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $students_code
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //get student
        $student = Student::findOrFail($id);

        //get classrooms
        $classrooms = Classroom::all();

        //render with inertia
        return inertia('Admin/Students/Edit', [
            'student' => $student,
            'classrooms' => $classrooms,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Student $student)
    {
        //validate request
        $request->validate([
            'name'              => 'required|string|max:255',
            'no_participant'    => 'required|unique:students,no_participant,'.$student->id,
            'gender'            => 'required|string',
            'position'          => 'required',
            'institution'       => 'required',
            'classroom_id'      => 'required',
        ]);

        //check passwordy
        // if($request->password == "") {

        //     //update student without password
        //     $student->update([
        //         'name'          => $request->name,
        //         'email'          => $request->email,
        //         'gender'        => $request->gender,
        //         'classroom_id'  => $request->classroom_id
        //     ]);

        // } else {

            //update student with password
            $student->update([
                'name'              => $request->name,
                'no_participant'    => $request->no_participant,
                'gender'            => $request->gender,
                'position'          => $request->position,
                'institution'       => $request->institution,
                'classroom_id'      => $request->classroom_id
            ]);

        // }

        //redirect
        return redirect()->route('admin.students.index');

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //get student
        $student = Student::findOrFail($id);

        ParticipantResult::preventLosingNumbers(ParticipantResult::where('student_id', $student->id), 'Peserta ini');

        //delete student
        $student->delete();

        //redirect
        return redirect()->route('admin.students.index');
    }

    /**
     * Download Excel peserta yang pernah mengikuti sertifikasi (terdaftar di sesi ujian,
     * akun aktif): satu baris per peserta per sesi, ikut kata kunci pencarian halaman.
     */
    public function export(Request $request)
    {
        // Satu peserta punya 2 baris exam_groups per sesi (PG + Esai) → ambil pasangan unik.
        $groups = ExamGroup::query()
            ->select('exam_groups.student_id', 'exam_groups.exam_session_id', 'students.no_participant')
            ->distinct()
            ->join('students', 'students.id', '=', 'exam_groups.student_id')
            ->where('students.is_active', true)
            ->when($request->q, fn ($query, $q) => $query->where('students.name', 'like', '%' . $q . '%'))
            ->with(['student.classroom', 'student.participant', 'exam_session'])
            ->orderBy('exam_groups.exam_session_id')
            ->orderBy('students.no_participant')
            ->get();

        $results = ParticipantResult::whereIn('student_id', $groups->pluck('student_id')->unique())
            ->get()
            ->keyBy(fn ($result) => $result->student_id . '-' . $result->exam_session_id);

        $filename = 'peserta_sertifikasi_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new StudentsCertificationExport($groups, $results), $filename);
    }

    /**
     * import
     *
     * @return void
     */
    public function import()
    {
        return inertia('Admin/Students/Import');
    }
    
    /**
     * storeImport
     *
     * @param  mixed $request
     * @return void
     */
    public function storeImport(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,xls,xlsx'
        ]);

        // import data
        $import = new StudentsImport();
        Excel::import($import, $request->file('file'));

        $msg = 'Data peserta berhasil diimpor.';
        if (!empty($import->skipped)) {
            $names = collect($import->skipped)->unique()->values();
            $msg .= ' ' . $names->count() . ' baris dilewati karena namanya sudah terdaftar di skema yang sama: '
                . $names->implode(', ') . '.';
        }

        //redirect
        return redirect()->route('admin.students.index')->with('success', $msg);
    }
}