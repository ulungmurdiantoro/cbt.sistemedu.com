<?php

namespace Tests\Feature\Student;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesExamFixtures;
use Tests\TestCase;

class LoginThrottleTest extends TestCase
{
    use RefreshDatabase;
    use CreatesExamFixtures;

    public function test_failed_logins_are_throttled_per_ip(): void
    {
        $student = $this->makeStudent($this->makeClassroom());

        for ($i = 0; $i < 10; $i++) {
            $this->post('/students/login', ['no_participant' => "SALAH-{$i}"]);
        }

        // Nomor yang benar pun ditolak sementara setelah terlalu banyak percobaan gagal.
        $this->post('/students/login', ['no_participant' => $student->no_participant])
            ->assertSessionHas('error');
        $this->assertGuest('student');
    }

    public function test_successful_logins_are_not_counted(): void
    {
        $classroom = $this->makeClassroom();

        // Satu ruang ujian di balik satu IP (NAT) login bersamaan.
        for ($i = 0; $i < 15; $i++) {
            $student = $this->makeStudent($classroom);

            $this->post('/students/login', ['no_participant' => $student->no_participant])
                ->assertRedirect(route('student.dashboard'));

            auth()->guard('student')->logout();
        }
    }
}
