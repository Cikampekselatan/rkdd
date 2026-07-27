<?php

namespace Tests\Feature;

use App\Enums\RoleSlug;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_login_screen_is_available(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Akses akun')
            ->assertSee('Masuk / daftar siswa dengan Google')
            ->assertSee('Siswa tidak memakai password lokal')
            ->assertDontSee('Daftar akun');
    }

    public function test_login_requires_a_valid_email_and_password(): void
    {
        $this->post(route('login.store'), [
            'email' => 'bukan-email',
            'password' => '',
        ])->assertSessionHasErrors(['email', 'password']);

        $this->assertGuest();
    }

    public function test_active_staff_can_log_in_and_is_redirected_to_their_dashboard(): void
    {
        $user = User::factory()->withRole(RoleSlug::Teacher)->create([
            'password' => 'rahasia-kuat',
        ]);

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'rahasia-kuat',
        ]);

        $response->assertRedirect(route('teacher.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        $user = User::factory()->withRole(RoleSlug::Admin)->create();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'salah',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_student_cannot_use_local_staff_login(): void
    {
        $student = User::factory()->withRole(RoleSlug::Student)->create([
            'password' => 'rahasia-kuat',
        ]);

        $this->post(route('login.store'), [
            'email' => $student->email,
            'password' => 'rahasia-kuat',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_suspended_staff_cannot_log_in(): void
    {
        $user = User::factory()->withRole(RoleSlug::Coach)->create([
            'password' => 'rahasia-kuat',
            'status' => UserStatus::Suspended,
        ]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'rahasia-kuat',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_is_rate_limited_after_repeated_failures(): void
    {
        $user = User::factory()->withRole(RoleSlug::Admin)->create();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'salah',
            ]);
        }

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'salah',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString(
            'Terlalu banyak percobaan',
            session('errors')->first('email'),
        );
    }

    public function test_authenticated_user_can_log_out(): void
    {
        $user = User::factory()->withRole(RoleSlug::Principal)->create();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('home'));

        $this->assertGuest();
    }

    public function test_public_registration_routes_do_not_exist(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register')->assertNotFound();
    }
}
