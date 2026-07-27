<?php

namespace Tests\Feature;

use App\Enums\RoleSlug;
use App\Enums\UserStatus;
use App\Models\Institution;
use App\Models\Program;
use App\Models\ProgramBatch;
use App\Models\RegistrationCode;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase20SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_browser_security_headers_are_applied_and_sensitive_pages_are_not_cached(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(self), geolocation=(), microphone=()');

        $loginResponse = $this->get(route('login'))
            ->assertOk()
            ->assertHeader('Pragma', 'no-cache');
        $this->assertStringContainsString('no-store', (string) $loginResponse->headers->get('Cache-Control'));
        $this->assertStringContainsString('private', (string) $loginResponse->headers->get('Cache-Control'));

        $teacher = User::factory()->withRole(RoleSlug::Teacher)->create();

        $dashboardResponse = $this->actingAs($teacher)
            ->get(route('teacher.dashboard'))
            ->assertOk();
        $this->assertStringContainsString('no-store', (string) $dashboardResponse->headers->get('Cache-Control'));
        $this->assertStringContainsString('private', (string) $dashboardResponse->headers->get('Cache-Control'));
    }

    public function test_disabled_or_expired_registration_code_revokes_existing_wizard_session(): void
    {
        foreach ([
            ['is_active' => false],
            ['expires_at' => now()->subMinute()],
        ] as $attributes) {
            $user = User::factory()->create([
                'status' => UserStatus::Onboarding,
                'password' => null,
            ]);
            $code = RegistrationCode::factory()->create($attributes);

            $this->actingAs($user)
                ->withSession(['onboarding.registration_code' => $this->codeState($user, $code)])
                ->get(route('onboarding.wizard.show', 'identity'))
                ->assertRedirect(route('onboarding.registration-code.show'))
                ->assertSessionHasErrors('code')
                ->assertSessionMissing('onboarding.registration_code');
        }
    }

    public function test_admin_cannot_escalate_a_staff_account_to_super_admin(): void
    {
        $admin = User::factory()->withRole(RoleSlug::Admin)->create();

        $this->actingAs($admin)
            ->post(route('admin.staff.store'), [
                'name' => 'Percobaan Eskalasi',
                'email' => 'escalation@example.test',
                'password' => 'PasswordAman123',
                'password_confirmation' => 'PasswordAman123',
                'role' => RoleSlug::SuperAdmin->value,
                'is_active' => true,
            ])
            ->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'escalation@example.test']);
    }

    public function test_state_changing_requests_reject_missing_csrf_token(): void
    {
        $this->app->detectEnvironment(fn (): string => 'local');
        $staff = User::factory()->withRole(RoleSlug::Teacher)->create([
            'email' => 'csrf@example.test',
            'password' => 'PasswordAman123',
        ]);

        $this->withMiddleware(ValidateCsrfToken::class)
            ->post(route('login.store'), [
                'email' => $staff->email,
                'password' => 'PasswordAman123',
            ])
            ->assertStatus(419);

        $this->assertGuest();
    }

    public function test_user_controlled_content_is_escaped_in_dashboard_output(): void
    {
        $teacher = User::factory()->withRole(RoleSlug::Teacher)->create([
            'name' => '<script>window.compromised=true</script>',
        ]);

        $this->actingAs($teacher)
            ->get(route('teacher.dashboard'))
            ->assertOk()
            ->assertSee('&lt;script&gt;window.compromised=true&lt;/script&gt;', false)
            ->assertDontSee('<script>window.compromised=true</script>', false);
    }

    public function test_staff_cannot_switch_to_unassigned_program_context(): void
    {
        $teacher = User::factory()->withRole(RoleSlug::Teacher)->create();
        $assignedBatch = $this->programBatch('skuad-aman');
        $unassignedBatch = $this->programBatch('rkdd-luar');

        $teacher->assignedProgramBatches()->attach($assignedBatch, ['assigned_by' => $teacher->id]);

        $this->actingAs($teacher)
            ->put(route('program-context.update'), ['program_batch_id' => $unassignedBatch->id])
            ->assertForbidden();

        $this->assertNotSame($unassignedBatch->id, session('active_program_batch_id'));

        $this->actingAs($teacher)
            ->put(route('program-context.update'), ['program_batch_id' => $assignedBatch->id])
            ->assertRedirect();

        $this->assertSame($assignedBatch->id, session('active_program_batch_id'));
    }

    public function test_unassigned_staff_has_no_program_context_fallback(): void
    {
        $teacher = User::factory()->withRole(RoleSlug::Teacher)->create();
        $batch = $this->programBatch('program-tertutup');

        $this->actingAs($teacher)
            ->put(route('program-context.update'), ['program_batch_id' => $batch->id])
            ->assertForbidden();

        $this->assertNull(session('active_program_batch_id'));
    }

    /**
     * @return array<string, int|string>
     */
    private function codeState(User $user, RegistrationCode $code): array
    {
        return [
            'user_id' => $user->id,
            'registration_code_id' => $code->id,
            'validated_at' => now()->toIso8601String(),
        ];
    }

    private function programBatch(string $slug): ProgramBatch
    {
        $program = Program::query()->create([
            'name' => 'Program '.strtoupper($slug),
            'slug' => $slug,
            'type' => 'ekstrakurikuler',
            'is_active' => true,
        ]);
        $institution = Institution::query()->firstOrCreate(
            ['slug' => 'smp-it-mentari-ilmu'],
            ['name' => 'SMP IT Mentari Ilmu', 'type' => 'sekolah', 'is_active' => true],
        );

        return ProgramBatch::query()->create([
            'program_id' => $program->id,
            'institution_id' => $institution->id,
            'name' => 'Batch '.strtoupper($slug),
            'slug' => $slug.'-2026',
            'period_label' => '2026',
            'audience_type' => 'school',
            'participant_label' => 'Siswa',
            'is_active' => true,
        ]);
    }
}
