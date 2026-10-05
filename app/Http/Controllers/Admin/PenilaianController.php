<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AsesorAssignment;
use App\Models\ExamSession;
use Illuminate\Http\Request;

class PenilaianController extends Controller
{
    // Menu "Penugasan Asesor" sudah dilebur ke detail sesi (tab Peserta & Asesor).
    // URL lama tetap hidup untuk bookmark & tautan lama.
    public function index()
    {
        return redirect()->route('admin.exam_sessions.index');
    }

    public function show(int $exam_session_id)
    {
        return redirect()->route('admin.exam_sessions.show', $exam_session_id);
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
