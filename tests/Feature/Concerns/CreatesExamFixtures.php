<?php

namespace Tests\Feature\Concerns;

use App\Models\Classroom;
use App\Models\Exam;
use App\Models\ExamGroup;
use App\Models\ExamSession;
use App\Models\Grade;
use App\Models\Student;
use Illuminate\Support\Str;

trait CreatesExamFixtures
{
    // Kode skema yang dikecualikan dari wajib unggah tugas (Student::requiresTugas),
    // supaya test ujian tidak dialihkan ke halaman tugas.
    protected function makeClassroom(string $code = 'LEM'): Classroom
    {
        return Classroom::forceCreate([
            'classrooms_code' => $code,
            'title'           => 'Skema ' . Str::random(6),
        ]);
    }

    protected function makeExam(Classroom $classroom, string $type = 'Pilihan Ganda', int $minutes = 60): Exam
    {
        return Exam::forceCreate([
            'exams_code'      => 'EX-' . Str::random(8),
            'title'           => 'Ujian ' . $type,
            'type'            => $type,
            'classroom_id'    => $classroom->id,
            'duration'        => $minutes,
            'description'     => '-',
            'random_question' => 'N',
            'random_answer'   => 'N',
            'show_answer'     => 'N',
        ]);
    }

    protected function makeSession(Exam $exam, ?Exam $esai = null): ExamSession
    {
        $isPg = $exam->type === 'Pilihan Ganda';

        return ExamSession::forceCreate([
            'exam_sessions_code' => 'ES-' . Str::random(8),
            'exam_id_pg'         => $isPg ? $exam->id : null,
            'exam_id_esai'       => $isPg ? $esai?->id : $exam->id,
            'title'              => 'Sesi Test',
            'start_time'         => now()->subHour(),
            'end_time'           => now()->addDay(),
        ]);
    }

    protected function makeStudent(Classroom $classroom): Student
    {
        return Student::forceCreate([
            'classroom_id'   => $classroom->id,
            'no_participant' => 'NP-' . Str::random(8),
            'name'           => 'Peserta Test',
            'position'       => '-',
            'institution'    => '-',
            'gender'         => 'L',
            'is_active'      => true,
        ]);
    }

    /**
     * Login sebagai peserta ujian. actingAs() menjadikan guard 'student' sebagai
     * default, padahal di aplikasi default-nya tetap 'web' (HandleInertiaRequests
     * membaca auth()->user() sebagai admin/asesor) — kembalikan seperti aslinya.
     */
    protected function actingAsStudent(Student $student): static
    {
        $this->actingAs($student, 'student');
        $this->app['auth']->shouldUse('web');

        return $this;
    }

    /** Enroll peserta + buat grade awal seperti Student\DashboardController. */
    protected function enroll(Student $student, Exam $exam, ExamSession $session): array
    {
        $group = ExamGroup::forceCreate([
            'exam_groups_code' => 'EG-' . Str::random(8),
            'exam_id'          => $exam->id,
            'exam_session_id'  => $session->id,
            'student_id'       => $student->id,
        ]);

        $grade = Grade::forceCreate([
            'grades_code'     => 'grds-' . Str::ulid(),
            'exam_id'         => $exam->id,
            'exam_session_id' => $session->id,
            'student_id'      => $student->id,
            'duration'        => $exam->duration * 60000,
            'total_correct'   => 0,
            'grade'           => 0,
        ]);

        return [$group, $grade];
    }
}
