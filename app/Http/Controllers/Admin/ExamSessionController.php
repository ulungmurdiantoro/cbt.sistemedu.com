<?php

namespace App\Http\Controllers\Admin;

use App\Models\Exam;
use App\Models\Student;
use App\Models\ExamGroup;
use App\Models\ExamSession;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ExamSessionController extends Controller
{
    public function index()
    {
        $exam_sessions = ExamSession::when(request()->q, function ($q) {
            $q->where('title', 'like', '%' . request()->q . '%');
        })->with('examPg.classroom', 'examEsai.classroom', 'exam_groups')
          ->orderByRaw('CASE WHEN end_time > NOW() THEN 0 ELSE 1 END ASC')
          ->orderByRaw('CASE WHEN end_time > NOW() THEN end_time END ASC')
          ->orderBy('end_time', 'desc')
          ->paginate(10);

        $exam_sessions->appends(['q' => request()->q]);

        return inertia('Admin/ExamSessions/Index', [
            'exam_sessions' => $exam_sessions,
        ]);
    }

    public function create()
    {
        $exams = Exam::select('id', 'title', 'type')->orderBy('title')->get();

        return inertia('Admin/ExamSessions/Create', [
            'exams' => $exams,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'           => 'required|string|max:255',
            'exam_id_pg'      => 'nullable|exists:exams,id',
            'exam_id_esai'    => 'nullable|exists:exams,id',
            'has_wawancara'   => 'boolean',
            'start_time'      => 'required',
            'end_time'        => 'required',
            'konteks_asesmen' => 'required|string|max:255',
            'tempat_ujian'    => 'required|string|max:255',
            'kode_batch'      => 'required|string|max:100',
        ]);

        // Pastikan minimal satu ujian dipilih
        if (!$request->exam_id_pg && !$request->exam_id_esai && !$request->has_wawancara) {
            return back()->withErrors(['exam_id_pg' => 'Pilih minimal satu jenis ujian.'])->withInput();
        }

        ExamSession::create([
            'exam_sessions_code' => 'exmss-' . rand(11, 99) . uniqid(),
            'title'              => $request->title,
            'exam_id_pg'         => $request->exam_id_pg,
            'exam_id_esai'       => $request->exam_id_esai,
            'has_wawancara'      => $request->boolean('has_wawancara'),
            // legacy: simpan salah satu untuk backward compat
            'exam_id'            => $request->exam_id_pg ?? $request->exam_id_esai,
            'start_time'         => date('Y-m-d H:i:s', strtotime($request->start_time)),
            'end_time'           => date('Y-m-d H:i:s', strtotime($request->end_time)),
            'konteks_asesmen'    => $request->konteks_asesmen,
            'tempat_ujian'       => $request->tempat_ujian,
            'kode_batch'         => $request->kode_batch,
        ]);

        return redirect()->route('admin.exam_sessions.index');
    }

    public function show($id)
    {
        $exam_session = ExamSession::with('examPg.classroom', 'examEsai.classroom')->findOrFail($id);

        // Tampilkan siswa unik yang terdaftar di sesi ini
        $enrolled_ids = ExamGroup::where('exam_session_id', $exam_session->id)
            ->distinct()
            ->pluck('student_id');

        $students = Student::whereIn('id', $enrolled_ids)
            ->with('classroom')
            ->paginate(10);

        return inertia('Admin/ExamSessions/Show', [
            'exam_session' => $exam_session,
            'students'     => $students,
        ]);
    }

    public function edit($id)
    {
        $exam_session = ExamSession::findOrFail($id);
        $exams        = Exam::select('id', 'title', 'type')->orderBy('title')->get();

        return inertia('Admin/ExamSessions/Edit', [
            'exam_session' => $exam_session,
            'exams'        => $exams,
        ]);
    }

    public function update(Request $request, ExamSession $exam_session)
    {
        $request->validate([
            'title'           => 'required|string|max:255',
            'exam_id_pg'      => 'nullable|exists:exams,id',
            'exam_id_esai'    => 'nullable|exists:exams,id',
            'has_wawancara'   => 'boolean',
            'start_time'      => 'required',
            'end_time'        => 'required',
            'remidi_start_at' => 'nullable',
            'remidi_end_at'   => 'nullable',
            'konteks_asesmen' => 'required|string|max:255',
            'tempat_ujian'    => 'required|string|max:255',
            'kode_batch'      => 'required|string|max:100',
        ]);

        if (!$request->exam_id_pg && !$request->exam_id_esai && !$request->has_wawancara) {
            return back()->withErrors(['exam_id_pg' => 'Pilih minimal satu jenis ujian.'])->withInput();
        }

        $exam_session->update([
            'title'           => $request->title,
            'exam_id_pg'      => $request->exam_id_pg,
            'exam_id_esai'    => $request->exam_id_esai,
            'has_wawancara'   => $request->boolean('has_wawancara'),
            'exam_id'         => $request->exam_id_pg ?? $request->exam_id_esai,
            'start_time'      => date('Y-m-d H:i:s', strtotime($request->start_time)),
            'end_time'        => date('Y-m-d H:i:s', strtotime($request->end_time)),
            'remidi_start_at' => $request->remidi_start_at ? date('Y-m-d H:i:s', strtotime($request->remidi_start_at)) : null,
            'remidi_end_at'   => $request->remidi_end_at   ? date('Y-m-d H:i:s', strtotime($request->remidi_end_at))   : null,
            'konteks_asesmen' => $request->konteks_asesmen,
            'tempat_ujian'    => $request->tempat_ujian,
            'kode_batch'      => $request->kode_batch,
        ]);

        return redirect()->route('admin.exam_sessions.index');
    }

    public function destroy($id)
    {
        ExamSession::findOrFail($id)->delete();

        return redirect()->route('admin.exam_sessions.index');
    }

    public function createEnrolle(ExamSession $exam_session)
    {
        $exam_session->load('examPg', 'examEsai');

        $referenceExam = $exam_session->referenceExam;
        abort_if(!$referenceExam, 422, 'Sesi ini belum memiliki ujian Pilihan Ganda atau Esai.');

        $enrolled_ids = ExamGroup::where('exam_session_id', $exam_session->id)
            ->distinct()
            ->pluck('student_id')
            ->all();

        $students = Student::with('classroom')
            ->where('classroom_id', $referenceExam->classroom_id)
            ->whereNotIn('id', $enrolled_ids)
            ->where('created_at', '>=', now()->subMonth())
            ->get();

        return inertia('Admin/ExamGroups/Create', [
            'exam_session' => $exam_session,
            'students'     => $students,
        ]);
    }

    public function storeEnrolle(Request $request, ExamSession $exam_session)
    {
        $request->validate([
            'student_id' => 'required|array|min:1',
        ]);

        $exam_session->load('examPg', 'examEsai');

        // Kumpulkan semua exam_id aktif di sesi ini
        $examIds = array_filter([
            $exam_session->exam_id_pg,
            $exam_session->exam_id_esai,
        ]);

        foreach ($request->student_id as $student_id) {
            Student::findOrFail($student_id); // validasi student ada

            foreach ($examIds as $exam_id) {
                $exists = ExamGroup::where([
                    'exam_id'         => $exam_id,
                    'exam_session_id' => $exam_session->id,
                    'student_id'      => $student_id,
                ])->exists();

                if (!$exists) {
                    ExamGroup::create([
                        'exam_groups_code' => 'exmg-' . rand(11, 99) . uniqid(),
                        'exam_id'          => $exam_id,
                        'exam_session_id'  => $exam_session->id,
                        'student_id'       => $student_id,
                    ]);
                }
            }
        }

        return redirect()->route('admin.exam_sessions.show', $exam_session->id);
    }

    public function destroyEnrolle(ExamSession $exam_session, Student $student)
    {
        ExamGroup::where('exam_session_id', $exam_session->id)
            ->where('student_id', $student->id)
            ->delete();

        return redirect()->route('admin.exam_sessions.show', $exam_session->id);
    }
}
