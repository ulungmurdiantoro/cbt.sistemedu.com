<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Requests\StoreExamSessionRequest;
use App\Http\Requests\UpdateExamSessionRequest;
use App\Models\AsesorAssignment;
use App\Models\AssessmentApplication;
use App\Models\Exam;
use App\Models\Student;
use App\Models\ExamGroup;
use App\Models\ExamSession;
use App\Models\ParticipantResult;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;

class ExamSessionController extends Controller
{
    public function index()
    {
        $exam_sessions = ExamSession::when(request()->q, function ($q) {
            $q->where('title', 'like', '%' . request()->q . '%');
        })->with('examPg.classroom', 'examEsai.classroom')
          // Satu peserta bisa punya 2 baris exam_groups (PG + Esai) dalam satu
          // sesi, jadi hitung student_id yang unik, bukan jumlah baris. Akun
          // nonaktif (akun lama hasil re-issue / merge) tidak ikut dihitung,
          // sama seperti daftar peserta di detail sesi.
          ->withCount(['exam_groups as students_count' => function ($q) {
              $q->join('students', 'students.id', '=', 'exam_groups.student_id')
                  ->where('students.is_active', true)
                  ->select(DB::raw('count(distinct exam_groups.student_id)'));
          }])
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

    public function store(StoreExamSessionRequest $request)
    {
        ExamSession::create([
            'exam_sessions_code' => 'exmss-' . Str::ulid(),
            'title'              => $request->title,
            'exam_id_pg'         => $request->exam_id_pg,
            'exam_id_esai'       => $request->exam_id_esai,
            'has_wawancara'      => $request->boolean('has_wawancara'),
            'verifikasi_tuk'     => $request->boolean('verifikasi_tuk'),
            'start_time'         => date('Y-m-d H:i:s', strtotime($request->start_time)),
            'end_time'           => date('Y-m-d H:i:s', strtotime($request->end_time)),
            'konteks_asesmen'    => $request->konteks_asesmen,
            'tempat_ujian'       => $request->tempat_ujian,
            'kode_batch'         => $request->kode_batch,
        ]);

        return redirect()->route('admin.exam_sessions.index');
    }

    /** Detail sesi: peserta + status permohonan + penugasan asesor dalam satu tabel. */
    public function show($id)
    {
        $exam_session = ExamSession::with('examPg.classroom', 'examEsai.classroom')->findOrFail($id);

        // Akun nonaktif (akun lama hasil re-issue / merge duplikat) tidak ditampilkan
        // supaya nama tidak muncul dobel, termasuk saat asesor menilai.
        $enrolledIds = ExamGroup::where('exam_session_id', $exam_session->id)->distinct()->pluck('student_id');
        $activeIds   = ExamSession::activeStudentIds($exam_session->id);

        $students = Student::whereIn('id', $activeIds)
            ->orderBy('no_participant')
            ->get(['id', 'no_participant', 'name']);

        $applications = AssessmentApplication::where('exam_session_id', $exam_session->id)
            ->whereIn('student_id', $activeIds)
            ->get(['id', 'student_id', 'status'])
            ->keyBy('student_id');

        $assignments = AsesorAssignment::where('exam_session_id', $exam_session->id)
            ->pluck('user_id', 'student_id');

        return inertia('Admin/ExamSessions/Show', [
            'exam_session'   => $exam_session,
            'students'       => $students->map(fn (Student $s) => [
                'id'                 => $s->id,
                'no_participant'     => $s->no_participant,
                'name'               => $s->name,
                'application_id'     => $applications->get($s->id)?->id,
                'application_status' => $applications->get($s->id)?->status,
                'asesor_id'          => $assignments->get($s->id),
            ]),
            'inactive_count' => $enrolledIds->count() - $activeIds->count(),
            // Hanya id + nama — asesor bisa ratusan, cukup untuk pilihan yang bisa dicari.
            'asesors'        => User::whereHas('roleAssignments', fn ($q) => $q->where('role', UserRole::Asesor->value))
                ->orderBy('name')
                ->get(['id', 'name']),
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

    public function update(UpdateExamSessionRequest $request, ExamSession $exam_session)
    {
        \DB::transaction(function () use ($request, $exam_session) {
            $exam_session->update([
                'title'           => $request->title,
                'exam_id_pg'      => $request->exam_id_pg,
                'exam_id_esai'    => $request->exam_id_esai,
                'has_wawancara'   => $request->boolean('has_wawancara'),
                'verifikasi_tuk'  => $request->boolean('verifikasi_tuk'),
                'start_time'      => date('Y-m-d H:i:s', strtotime($request->start_time)),
                'end_time'        => date('Y-m-d H:i:s', strtotime($request->end_time)),
                'remidi_start_at' => $request->remidi_start_at ? date('Y-m-d H:i:s', strtotime($request->remidi_start_at)) : null,
                'remidi_end_at'   => $request->remidi_end_at   ? date('Y-m-d H:i:s', strtotime($request->remidi_end_at))   : null,
                'konteks_asesmen' => $request->konteks_asesmen,
                'tempat_ujian'    => $request->tempat_ujian,
                'kode_batch'      => $request->kode_batch,
            ]);

            // Sinkronkan ExamGroup peserta yang sudah enrolled supaya
            // exam yang baru ditambahkan (mis. esai) langsung ter-create
            // untuk semua peserta tanpa perlu re-enroll manual.
            $enrolledStudentIds = ExamGroup::where('exam_session_id', $exam_session->id)
                ->distinct()
                ->pluck('student_id')
                ->all();

            $examIds = array_filter([
                $exam_session->exam_id_pg,
                $exam_session->exam_id_esai,
            ]);

            foreach ($examIds as $examId) {
                foreach ($enrolledStudentIds as $studentId) {
                    ExamGroup::firstOrCreate(
                        [
                            'exam_id'         => $examId,
                            'exam_session_id' => $exam_session->id,
                            'student_id'      => $studentId,
                        ],
                        [
                            'exam_groups_code' => 'exmg-' . Str::ulid(),
                        ]
                    );
                }
            }
        });

        return redirect()->route('admin.exam_sessions.index');
    }

    public function destroy($id)
    {
        $session = ExamSession::findOrFail($id);

        ParticipantResult::preventLosingNumbers(ParticipantResult::where('exam_session_id', $session->id), 'Sesi ini');

        $session->delete();

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
            ->orderBy('no_participant')
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

        // Nama peserta yang SUDAH ter-enroll di sesi ini (dinormalisasi) — dipakai
        // untuk mencegah 1 orang masuk 2x lewat 2 record Student berbeda.
        $enrolledNames = Student::whereIn('id', ExamGroup::where('exam_session_id', $exam_session->id)->distinct()->pluck('student_id'))
            ->pluck('name')
            ->map(fn ($n) => preg_replace('/\s+/', ' ', trim(mb_strtolower($n))))
            ->all();

        $skipped = [];

        foreach ($request->student_id as $student_id) {
            $student = Student::findOrFail($student_id); // validasi student ada

            $normName = preg_replace('/\s+/', ' ', trim(mb_strtolower($student->name)));
            if (in_array($normName, $enrolledNames, true)) {
                $skipped[] = $student->name;
                continue;
            }
            $enrolledNames[] = $normName;

            foreach ($examIds as $exam_id) {
                $exists = ExamGroup::where([
                    'exam_id'         => $exam_id,
                    'exam_session_id' => $exam_session->id,
                    'student_id'      => $student_id,
                ])->exists();

                if (!$exists) {
                    ExamGroup::create([
                        'exam_groups_code' => 'exmg-' . Str::ulid(),
                        'exam_id'          => $exam_id,
                        'exam_session_id'  => $exam_session->id,
                        'student_id'       => $student_id,
                    ]);
                }
            }
        }

        if (!empty($skipped)) {
            $names = collect($skipped)->unique()->values();
            // Peserta yang tidak bentrok tetap ter-enroll di atas; yang bentrok
            // dilaporkan sebagai error inline supaya admin sadar ada nama kembar.
            throw \Illuminate\Validation\ValidationException::withMessages([
                'student_id' => $names->count() . ' peserta dilewati karena nama yang sama sudah terdaftar di sesi ini: '
                    . $names->implode(', ') . '. Kalau memang orang berbeda, ubah dulu namanya agar tidak persis sama.',
            ]);
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
