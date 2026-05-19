<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AsesorAssignment;
use App\Models\ExamGroup;
use App\Models\ExamSession;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;

class PenilaianController extends Controller
{
    public function index()
    {
        $exam_sessions = ExamSession::with('exam.classroom')
            ->orderBy('id', 'desc')
            ->get();

        $asesors = User::where('role', 'asesor')->orderBy('name')->get();

        return inertia('Admin/Penilaian/Index', [
            'exam_sessions' => $exam_sessions,
            'asesors'       => $asesors,
        ]);
    }

    public function show(int $exam_session_id)
    {
        $exam_session = ExamSession::with('exam.classroom')->findOrFail($exam_session_id);

        $asesors = User::where('role', 'asesor')->orderBy('name')->get();

        // Ambil semua siswa yang terdaftar di sesi ini via exam_groups
        $student_ids = ExamGroup::where('exam_session_id', $exam_session_id)
            ->pluck('student_id');

        $students = Student::whereIn('id', $student_ids)
            ->orderBy('no_participant')
            ->get();

        // Penugasan yang sudah ada
        $assignments = AsesorAssignment::where('exam_session_id', $exam_session_id)
            ->with('asesor', 'student')
            ->get();

        return inertia('Admin/Penilaian/Show', [
            'exam_session' => $exam_session,
            'students'     => $students,
            'asesors'      => $asesors,
            'assignments'  => $assignments,
        ]);
    }

    public function saveAssignments(Request $request, int $exam_session_id)
    {
        $request->validate([
            'assignments'              => 'required|array',
            'assignments.*.student_id' => 'required|exists:students,id',
            'assignments.*.user_id'    => 'nullable|exists:users,id',
        ]);

        $exam_session = ExamSession::findOrFail($exam_session_id);

        foreach ($request->assignments as $item) {
            if (empty($item['user_id'])) {
                AsesorAssignment::where('exam_session_id', $exam_session->id)
                    ->where('student_id', $item['student_id'])
                    ->delete();
                continue;
            }

            AsesorAssignment::updateOrCreate(
                [
                    'exam_session_id' => $exam_session->id,
                    'student_id'      => $item['student_id'],
                ],
                [
                    'user_id' => $item['user_id'],
                ]
            );
        }

        return back()->with('success', 'Penugasan asesor berhasil disimpan.');
    }
}
