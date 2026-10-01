<?php

namespace Tests\Feature\Peserta;

use App\Models\AssessmentApplication;
use App\Models\Participant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Feature\Concerns\CreatesExamFixtures;
use Tests\TestCase;

class ChooseSkemaTest extends TestCase
{
    use RefreshDatabase;
    use CreatesExamFixtures;

    private function actingAsParticipant(): Participant
    {
        $participant = Participant::forceCreate([
            'name'     => 'Asesi Test',
            'email'    => 'asesi@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($participant, 'participant');
        $this->app['auth']->shouldUse('web');

        return $participant;
    }

    public function test_every_active_batch_of_the_same_skema_is_listed(): void
    {
        $exam   = $this->makeExam($this->makeClassroom());
        $batch1 = $this->makeSession($exam);
        $batch2 = $this->makeSession($exam);
        $batch2->forceFill(['start_time' => now()->addDays(2), 'end_time' => now()->addDays(3)])->save();

        $participant = $this->actingAsParticipant();
        AssessmentApplication::forceCreate([
            'code'            => 'APL-TEST0001',
            'participant_id'  => $participant->id,
            'classroom_id'    => $exam->classroom_id,
            'exam_session_id' => $batch1->id,
            'kode_batch'      => '-',
            'tujuan_asesmen'  => 'Sertifikasi',
            'status'          => 'draft',
        ]);

        $this->get('/peserta/skema')->assertInertia(fn (Assert $page) => $page
            ->component('Peserta/Application/ChooseSkema')
            ->has('skema_list', 1)
            ->has('skema_list.0.sessions', 2)
            ->where('skema_list.0.sessions.0.id', $batch1->id)
            ->where('skema_list.0.sessions.0.enrolled', true)
            ->where('skema_list.0.sessions.1.id', $batch2->id)
            ->where('skema_list.0.sessions.1.enrolled', false)
        );
    }

    public function test_participant_can_register_for_a_specific_batch(): void
    {
        $exam = $this->makeExam($this->makeClassroom());
        $this->makeSession($exam);
        $batch2 = $this->makeSession($exam);

        $participant = $this->actingAsParticipant();

        $this->post('/peserta/skema', ['exam_session_id' => $batch2->id, 'tujuan_asesmen' => 'Sertifikasi'])
            ->assertRedirect();

        $this->assertSame(
            [$batch2->id],
            AssessmentApplication::where('participant_id', $participant->id)->pluck('exam_session_id')->all()
        );
    }

    public function test_cannot_register_for_a_closed_session(): void
    {
        $session = $this->makeSession($this->makeExam($this->makeClassroom()));
        $session->forceFill(['end_time' => now()->subMinute()])->save();

        $this->actingAsParticipant();

        $this->post('/peserta/skema', ['exam_session_id' => $session->id, 'tujuan_asesmen' => 'Sertifikasi'])
            ->assertSessionHas('error');

        $this->assertSame(0, AssessmentApplication::count());
    }
}
