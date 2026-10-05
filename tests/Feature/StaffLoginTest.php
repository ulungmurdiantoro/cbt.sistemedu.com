<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Participant;
use App\Models\User;
use App\Models\UserRoleAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Tests\Feature\Concerns\CreatesExamFixtures;
use Tests\TestCase;

/** Login staf di /login: langsung ke dashboard bila sesi masih aktif, dan "Remember me". */
class StaffLoginTest extends TestCase
{
    use RefreshDatabase, CreatesExamFixtures;

    private function staff(UserRole ...$roles): User
    {
        $user = User::forceCreate([
            'users_code' => 'U-' . Str::random(6),
            'name'       => 'Staf',
            // Huruf besar-kecil acak: mutator User menyimpannya huruf kecil, login tetap cocok
            'email'      => Str::random(8) . '@Example.com',
            'password'   => bcrypt('rahasia123'),
        ]);
        foreach ($roles as $role) {
            UserRoleAssignment::forceCreate(['user_id' => $user->id, 'role' => $role->value]);
        }

        return $user;
    }

    public function test_logged_in_staff_opening_login_goes_to_their_dashboard(): void
    {
        $this->actingAs($this->staff(UserRole::Admin))->get('/login')->assertRedirect('/admin/dashboard');
        $this->actingAs($this->staff(UserRole::ManagerSertifikasi))->get('/login')->assertRedirect('/manager/dashboard');
        $this->actingAs($this->staff(UserRole::Asesor))->get('/login')->assertRedirect('/asesor/dashboard');
        // Prioritas role sama dengan setelah login: admin > manager > asesor
        $this->actingAs($this->staff(UserRole::Asesor, UserRole::Admin))->get('/login')->assertRedirect('/admin/dashboard');
    }

    public function test_logged_in_staff_opening_home_goes_to_their_dashboard(): void
    {
        $this->actingAs($this->staff(UserRole::Asesor))->get('/')->assertRedirect('/asesor/dashboard');
    }

    public function test_home_still_shows_exam_login_for_guests_and_participants(): void
    {
        $this->get('/')->assertOk();

        // Peserta portal sertifikasi memakai "/" untuk login ujian → tidak dialihkan
        $participant = Participant::forceCreate(['name' => 'Peserta', 'email' => Str::random(8) . '@example.com', 'password' => bcrypt('x')]);
        $this->actingAs($participant, 'participant');
        $this->app['auth']->shouldUse('web');
        $this->get('/')->assertOk();
    }

    public function test_logged_in_student_opening_home_goes_to_student_dashboard(): void
    {
        $student = $this->makeStudent($this->makeClassroom());

        $this->actingAsStudent($student)->get('/')->assertRedirect('/student/dashboard');
    }

    public function test_staff_email_is_stored_lowercase_and_login_ignores_case(): void
    {
        $admin = $this->staff(UserRole::Admin);
        $form  = fn (string $code, string $email) => [
            'users_code' => $code, 'name' => 'Budi', 'email' => $email, 'roles' => ['asesor'],
            'password' => 'rahasia123', 'password_confirmation' => 'rahasia123',
        ];

        $this->actingAs($admin)->post('/admin/users', $form('ASR-1', ' Budi@Gmail.COM '))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['users_code' => 'ASR-1', 'email' => 'budi@gmail.com']);

        // Beda huruf saja tetap dianggap email yang sama
        $this->actingAs($admin)->post('/admin/users', $form('ASR-2', 'BUDI@gmail.com'))->assertSessionHasErrors('email');

        Auth::guard('web')->logout();
        $this->post('/login', ['email' => 'BuDi@GMAIL.com', 'password' => 'rahasia123'])->assertRedirect('/asesor/dashboard');
    }

    public function test_remember_me_sets_a_30_day_cookie_only_when_checked(): void
    {
        $user   = $this->staff(UserRole::Admin);
        $cookie = Auth::guard('web')->getRecallerName();

        $this->post('/login', ['email' => $user->email, 'password' => 'rahasia123'])
            ->assertRedirect('/admin/dashboard')
            ->assertCookieMissing($cookie);

        Auth::guard('web')->logout();

        $response = $this->post('/login', ['email' => $user->email, 'password' => 'rahasia123', 'remember' => true])
            ->assertRedirect('/admin/dashboard')
            ->assertCookie($cookie);

        $expires = collect($response->headers->getCookies())->first(fn ($c) => $c->getName() === $cookie)->getExpiresTime();
        $this->assertEqualsWithDelta(now()->addDays(30)->timestamp, $expires, 120);
    }
}
