<?php

namespace Tests\Feature\Asesor;

use App\Enums\UserRole;
use App\Models\AnswerEssay;
use App\Models\AsesorAssignment;
use App\Models\AssessmentApplication;
use App\Models\Essay;
use App\Models\GradingScheme;
use App\Models\InterviewAssessment;
use App\Models\Participant;
use App\Models\ParticipantResult;
use App\Models\Student;
use App\Models\User;
use App\Models\UserRoleAssignment;
use App\Services\ResultCalculatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;
use Tests\Feature\Concerns\CreatesExamFixtures;
use Tests\TestCase;

/** Rekap Nilai asesor: baca saja, hanya peserta yang ditugaskan. */
class RekapNilaiTest extends TestCase
{
    use RefreshDatabase;
    use CreatesExamFixtures;

    private array $ctx;

    protected function setUp(): void
    {
        parent::setUp();

        $classroom = $this->makeClassroom();
        $pg        = $this->makeExam($classroom);
        $esai      = $this->makeExam($classroom, 'Essay');
        $session   = $this->makeSession($pg, $esai);
        $session->forceFill(['has_wawancara' => true])->save();

        GradingScheme::forceCreate([
            'classroom_id'    => $classroom->id,
            'bobot_pg'        => 40,
            'bobot_esai'      => 30,
            'bobot_wawancara' => 30,
            'nilai_kelulusan' => 70,
        ]);

        $asesor = $this->asesor();
        $mine   = $this->participant($classroom, $pg, $esai, $session);
        $other  = $this->participant($classroom, $pg, $esai, $session);

        AsesorAssignment::forceCreate(['user_id' => $asesor->id, 'exam_session_id' => $session->id, 'student_id' => $mine->id]);
        AsesorAssignment::forceCreate(['user_id' => $this->asesor()->id, 'exam_session_id' => $session->id, 'student_id' => $other->id]);

        $this->ctx = compact('session', 'asesor', 'mine', 'other', 'esai');
    }

    private function asesor(): User
    {
        $user = User::forceCreate([
            'users_code' => 'ASR-' . Str::random(6),
            'name'       => 'Asesor Test',
            'email'      => Str::random(8) . '@example.com',
            'password'   => bcrypt('password'),
        ]);

        UserRoleAssignment::forceCreate(['user_id' => $user->id, 'role' => UserRole::Asesor->value]);

        return $user;
    }

    /**
     * Peserta dengan PG 80, esai 2 jawaban (90 & belum dinilai), wawancara 70, rekomendasi K.
     * Nilai akhir = 80×0,4 + 90×0,3 + 70×0,3 = 80.
     */
    private function participant($classroom, $pg, $esai, $session): Student
    {
        $student = $this->makeStudent($classroom);
        [, $grade] = $this->enroll($student, $pg, $session);
        $grade->forceFill(['grade' => 80])->save();
        $this->enroll($student, $esai, $session);

        foreach ([1 => 90, 2 => null] as $n => $score) {
            $essay = Essay::forceCreate(['exam_id' => $esai->id, 'essays_code' => 'E' . Str::random(6), 'question' => "Q{$n}", 'answer' => '-']);
            AnswerEssay::forceCreate([
                'answeressays_code' => 'answess-' . Str::ulid(),
                'exam_id'           => $esai->id,
                'exam_session_id'   => $session->id,
                'essay_id'          => $essay->id,
                'student_id'        => $student->id,
                'essay_order'       => $n,
                'answer_order'      => '1',
                'answer'            => "jawaban {$n}",
                'score'             => $score,
            ]);
        }

        InterviewAssessment::forceCreate([
            'exam_session_id'             => $session->id,
            'student_id'                  => $student->id,
            'asesor_id'                   => $this->asesor()->id,
            'gaya_wawancara'              => 70,
            'penguasaan_materi'           => 70,
            'kemampuan_hadapi_pertanyaan' => 70,
            'hasil_worksheet'             => 70,
            'total_nilai'                 => 70,
        ]);

        $participant = Participant::forceCreate([
            'name'     => $student->name,
            'email'    => Str::random(8) . '@example.com',
            'password' => bcrypt('password'),
        ]);
        AssessmentApplication::forceCreate([
            'code'               => 'APL-' . Str::random(8),
            'participant_id'     => $participant->id,
            'classroom_id'       => $classroom->id,
            'exam_session_id'    => $session->id,
            'student_id'         => $student->id,
            'kode_batch'         => '-',
            'tujuan_asesmen'     => 'Sertifikasi',
            'status'             => 'approved',
            'asesor_rekomendasi' => 'K',
        ]);

        return $student;
    }

    private function rekapUrl(): string
    {
        return "/asesor/penilaian/{$this->ctx['session']->id}/rekap";
    }

    public function test_shows_only_assigned_participants_with_calculated_grades(): void
    {
        $this->actingAs($this->ctx['asesor'])
            ->get($this->rekapUrl())
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Asesor/RekapNilai/Show')
                ->has('rows', 1)
                ->where('rows.0.student_id', $this->ctx['mine']->id)
                ->where('rows.0.nilai_pg', 80)
                ->where('rows.0.nilai_esai', 90)
                ->where('rows.0.esai_belum_dinilai', 1)
                ->where('rows.0.nilai_wawancara', 70)
                ->where('rows.0.nilai_akhir', 80)
                ->where('rows.0.keputusan', 'LULUS')
                ->where('rows.0.is_finalized', false)
                ->where('rows.0.rekomendasi', 'K')
                ->where('scheme.nilai_kelulusan', 70));
    }

    public function test_viewing_does_not_write_results(): void
    {
        $this->actingAs($this->ctx['asesor'])->get($this->rekapUrl())->assertOk();

        $this->assertDatabaseCount('participant_results', 0);
    }

    public function test_finalized_result_keeps_stored_grades(): void
    {
        ParticipantResult::forceCreate([
            'exam_session_id' => $this->ctx['session']->id,
            'student_id'      => $this->ctx['mine']->id,
            'nilai_akhir'     => 65,
            'keputusan'       => 'TIDAK_LULUS',
            'is_finalized'    => true,
        ]);

        $this->actingAs($this->ctx['asesor'])
            ->get($this->rekapUrl())
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('rows.0.nilai_akhir', 65)
                ->where('rows.0.keputusan', 'TIDAK_LULUS')
                ->where('rows.0.is_finalized', true));
    }

    public function test_unassigned_asesor_is_forbidden(): void
    {
        $this->actingAs($this->asesor())->get($this->rekapUrl())->assertForbidden();
    }

    public function test_admin_recalc_still_saves_results(): void
    {
        app(ResultCalculatorService::class)->recalcForSession($this->ctx['session']->fresh());

        $this->assertDatabaseCount('participant_results', 2);
        $this->assertEquals(80, ParticipantResult::where('student_id', $this->ctx['mine']->id)->value('nilai_akhir'));
    }
}
