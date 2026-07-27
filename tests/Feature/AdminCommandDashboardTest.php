<?php

namespace Tests\Feature;

use App\Enums\RoleSlug;
use App\Enums\UserStatus;
use App\Models\AcademicYear;
use App\Models\AuthenticationLog;
use App\Models\DocumentResource;
use App\Models\RegistrationCode;
use App\Models\SchoolClass;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\AdminDashboardService;
use App\Services\SuperAdminDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminCommandDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_uses_real_master_data_and_surfaces_data_quality_alerts(): void
    {
        $year = AcademicYear::factory()->active()->create(['name' => '2033/2034']);
        $class = SchoolClass::factory()->create(['academic_year_id' => $year->id, 'name' => 'Kelas Administrasi', 'homeroom_teacher_id' => null]);
        $student = User::factory()->withRole(RoleSlug::Student)->create(['name' => 'Siswa Administrasi', 'status' => UserStatus::Active]);
        StudentProfile::factory()->create(['user_id' => $student->id, 'class_id' => $class->id]);
        User::factory()->withRole(RoleSlug::Teacher)->create();
        RegistrationCode::factory()->create(['academic_year_id' => $year->id, 'class_id' => $class->id, 'is_active' => true]);
        DocumentResource::factory()->published()->create(['academic_year_id' => $year->id]);
        $admin = User::factory()->withRole(RoleSlug::Admin)->create();

        DB::enableQueryLog();
        $data = app(AdminDashboardService::class)->build();
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame(1, $data['kpis']['active_students']);
        $this->assertSame(1, $data['kpis']['active_classes']);
        $this->assertSame(1, $data['kpis']['available_codes']);
        $this->assertSame(1, $data['kpis']['active_documents']);
        $this->assertTrue($data['alerts']->contains(fn ($alert) => str_contains($alert, 'Koordinator')));
        $this->assertLessThanOrEqual(20, $queryCount);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Administrasi program')
            ->assertSee('Siswa Administrasi')
            ->assertSee('Kelas Administrasi')
            ->assertDontSee('Route placeholder');
    }

    public function test_super_admin_dashboard_exposes_system_role_and_authentication_overview(): void
    {
        $superAdmin = User::factory()->withRole(RoleSlug::SuperAdmin)->create();
        User::factory()->withRole(RoleSlug::Teacher)->create();
        AuthenticationLog::query()->create([
            'email' => 'blocked@example.test',
            'provider' => 'google',
            'event' => 'rejected_domain',
            'ip_address' => '127.0.0.1',
            'created_at' => now(),
        ]);

        DB::enableQueryLog();
        $data = app(SuperAdminDashboardService::class)->build();
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame(2, $data['kpis']['users']);
        $this->assertSame(1, $data['kpis']['auth_rejections']);
        $this->assertSame('testing', $data['system']['environment']);
        $this->assertLessThanOrEqual(20, $queryCount);

        $this->actingAs($superAdmin)
            ->get(route('super-admin.dashboard'))
            ->assertOk()
            ->assertSee('System command center')
            ->assertSee('Pengguna berdasarkan role')
            ->assertSee('blocked@example.test')
            ->assertDontSee('Route placeholder');
    }

    public function test_dashboard_role_boundaries_are_enforced(): void
    {
        $teacher = User::factory()->withRole(RoleSlug::Teacher)->create();
        $admin = User::factory()->withRole(RoleSlug::Admin)->create();

        $this->actingAs($teacher)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($admin)->get(route('super-admin.dashboard'))->assertForbidden();
    }
}
