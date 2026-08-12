<?php

namespace Tests\Feature;

use App\Enums\RoleSlug;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class GoogleOAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_redirect_uses_the_official_socialite_provider(): void
    {
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('redirect')
            ->once()
            ->andReturn(redirect()->away('https://accounts.google.com/o/oauth2/auth'));

        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

        $this->get(route('google.redirect'))
            ->assertRedirect('https://accounts.google.com/o/oauth2/auth');
    }

    public function test_google_redirect_is_blocked_when_oauth_configuration_is_missing(): void
    {
        config()->set('services.google.client_id', null);

        Socialite::shouldReceive('driver')->never();

        $this->get(route('google.redirect'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('google');
    }

    public function test_new_google_user_enters_onboarding_without_student_role_or_stored_token(): void
    {
        $this->mockGoogleCallback([
            'id' => 'google-new-student',
            'name' => 'Nadia Putri',
            'email' => 'nadia.putri@gmail.com',
            'token' => 'token-yang-tidak-boleh-disimpan',
            'refreshToken' => 'refresh-yang-tidak-boleh-disimpan',
        ]);

        $this->get(route('google.callback'))
            ->assertRedirect(route('student.onboarding.pending'));

        $user = User::query()->where('email', 'nadia.putri@gmail.com')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertSame('google-new-student', $user->google_id);
        $this->assertSame(UserStatus::Onboarding, $user->status);
        $this->assertNull($user->password);
        $this->assertCount(0, $user->roles);
        $this->assertDatabaseHas('authentication_logs', [
            'user_id' => $user->id,
            'event' => 'login_created',
            'email' => 'nadia.putri@gmail.com',
        ]);
        $this->assertFalse(Schema::hasColumn('users', 'google_token'));
        $this->assertFalse(Schema::hasColumn('users', 'google_refresh_token'));
    }

    public function test_allowed_domains_are_loaded_from_configuration(): void
    {
        config()->set('student-registration.allowed_email_domains', ['student.sekolah.id']);
        $this->mockGoogleCallback([
            'id' => 'workspace-student',
            'email' => 'siswa@student.sekolah.id',
        ]);

        $this->get(route('google.callback'))
            ->assertRedirect(route('student.onboarding.pending'));

        $this->assertDatabaseHas('users', [
            'email' => 'siswa@student.sekolah.id',
            'google_id' => 'workspace-student',
            'status' => UserStatus::Onboarding->value,
        ]);
    }

    public function test_disallowed_domain_is_rejected_and_audited(): void
    {
        $this->mockGoogleCallback([
            'id' => 'wrong-domain',
            'email' => 'siswa@yahoo.com',
        ]);

        $this->get(route('google.callback'))
            ->assertForbidden()
            ->assertSee('Domain email belum diizinkan')
            ->assertSee('yahoo.com')
            ->assertSee('gmail.com');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'siswa@yahoo.com']);
        $this->assertDatabaseHas('authentication_logs', [
            'event' => 'rejected_domain',
            'email' => 'siswa@yahoo.com',
        ]);
    }

    public function test_existing_active_student_can_log_in_with_google(): void
    {
        $student = User::factory()->withRole(RoleSlug::Student)->create([
            'email' => 'siswa.aktif@gmail.com',
            'google_id' => null,
            'status' => UserStatus::Active,
        ]);
        $this->mockGoogleCallback([
            'id' => 'existing-google-id',
            'email' => $student->email,
        ]);

        $this->get(route('google.callback'))
            ->assertRedirect(route('student.dashboard'));

        $this->assertAuthenticatedAs($student);
        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'google_id' => 'existing-google-id',
            'status' => UserStatus::Active->value,
        ]);
        $this->assertDatabaseHas('authentication_logs', [
            'user_id' => $student->id,
            'event' => 'login_success',
        ]);
    }

    public function test_staff_email_is_not_linked_to_google_student_login(): void
    {
        $admin = User::factory()->withRole(RoleSlug::Admin)->create([
            'email' => 'admin.skuad@gmail.com',
            'google_id' => null,
        ]);
        $this->mockGoogleCallback([
            'id' => 'google-admin-attempt',
            'email' => $admin->email,
        ]);

        $this->get(route('google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('google');

        $this->assertGuest();
        $this->assertNull($admin->fresh()->google_id);
        $this->assertDatabaseHas('authentication_logs', [
            'user_id' => $admin->id,
            'event' => 'rejected_staff_account',
        ]);
    }

    public function test_suspended_student_is_rejected_and_audited(): void
    {
        $student = User::factory()->withRole(RoleSlug::Student)->create([
            'email' => 'siswa.suspended@gmail.com',
            'status' => UserStatus::Suspended,
        ]);
        $this->mockGoogleCallback([
            'id' => 'suspended-google-id',
            'email' => $student->email,
        ]);

        $this->get(route('google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('google');

        $this->assertGuest();
        $this->assertDatabaseHas('authentication_logs', [
            'user_id' => $student->id,
            'event' => 'rejected_inactive_account',
        ]);
    }

    public function test_invalid_oauth_state_is_rejected_and_audited(): void
    {
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->once()->andThrow(new InvalidStateException);
        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

        $this->get(route('google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('google');

        $this->assertGuest();
        $this->assertDatabaseHas('authentication_logs', [
            'event' => 'rejected_invalid_state',
        ]);
    }

    public function test_only_onboarding_student_can_view_pending_page(): void
    {
        $onboardingUser = User::factory()->create([
            'status' => UserStatus::Onboarding,
            'password' => null,
        ]);
        $activeStudent = User::factory()->withRole(RoleSlug::Student)->create();

        $this->actingAs($onboardingUser)
            ->get(route('student.onboarding.pending'))
            ->assertOk()
            ->assertSee('Google berhasil terhubung');

        $this->actingAs($activeStudent)
            ->get(route('student.onboarding.pending'))
            ->assertForbidden();
    }

    public function test_google_onboarding_user_is_redirected_from_student_routes_to_registration_code(): void
    {
        $onboardingUser = User::factory()->create([
            'status' => UserStatus::Onboarding,
            'password' => null,
        ]);

        $this->actingAs($onboardingUser)
            ->get(route('student.dashboard'))
            ->assertRedirect(route('onboarding.registration-code.show'));
    }

    public function test_google_onboarding_user_with_validated_code_is_redirected_to_continue_onboarding(): void
    {
        $onboardingUser = User::factory()->create([
            'status' => UserStatus::Onboarding,
            'password' => null,
        ]);

        $this->actingAs($onboardingUser)
            ->withSession(['onboarding.registration_code' => [
                'user_id' => $onboardingUser->id,
                'registration_code_id' => 123,
                'validated_at' => now()->toIso8601String(),
            ]])
            ->get(route('student.dashboard'))
            ->assertRedirect(route('onboarding.registration-code.accepted'));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function mockGoogleCallback(array $attributes): void
    {
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')
            ->once()
            ->andReturn(SocialiteUser::fake($attributes));

        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);
    }
}
