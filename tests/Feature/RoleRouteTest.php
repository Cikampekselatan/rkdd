<?php

namespace Tests\Feature;

use App\Enums\RoleSlug;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RoleRouteTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{RoleSlug, string, string, string}>
     */
    public static function roleRouteProvider(): array
    {
        return [
            'super admin' => [RoleSlug::SuperAdmin, 'super-admin.dashboard', 'Super Admin', 'System command center'],
            'admin' => [RoleSlug::Admin, 'admin.dashboard', 'Admin Sekolah', 'Administrasi program'],
            'teacher' => [RoleSlug::Teacher, 'teacher.dashboard', 'Pembina/Guru', 'Pusat kendali pembinaan'],
            'coach' => [RoleSlug::Coach, 'coach.dashboard', 'Instruktur/Coach', 'Pusat kendali pembinaan'],
            'student' => [RoleSlug::Student, 'student.dashboard', 'Siswa', 'Dashboard pribadi'],
            'principal' => [RoleSlug::Principal, 'principal.dashboard', 'Kepala Sekolah', 'Monitoring program RKDD'],
        ];
    }

    #[DataProvider('roleRouteProvider')]
    public function test_user_can_access_their_role_route(RoleSlug $role, string $routeName, string $label, string $marker): void
    {
        $user = User::factory()->withRole($role)->create();

        $this->actingAs($user)
            ->get(route($routeName))
            ->assertOk()
            ->assertSee($label)
            ->assertSee($marker);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_user_cannot_access_another_role_route(): void
    {
        $teacher = User::factory()->withRole(RoleSlug::Teacher)->create();

        $this->actingAs($teacher)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }
}
