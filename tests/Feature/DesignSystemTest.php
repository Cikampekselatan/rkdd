<?php

namespace Tests\Feature;

use App\Enums\RoleSlug;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DesignSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_view_design_system_during_development(): void
    {
        $superAdmin = User::factory()->withRole(RoleSlug::SuperAdmin)->create();

        $this->actingAs($superAdmin)
            ->get(route('super-admin.design-system'))
            ->assertOk()
            ->assertSee('SKUAD Design System')
            ->assertSee('Premium data table')
            ->assertSee('data-sidebar-toggle', false)
            ->assertSee('skuad-bottom-nav', false)
            ->assertSee('filterCanvas', false)
            ->assertSee('confirmModal', false)
            ->assertSee('demoToast', false);
    }

    public function test_non_super_admin_cannot_view_design_system(): void
    {
        $admin = User::factory()->withRole(RoleSlug::Admin)->create();

        $this->actingAs($admin)
            ->get(route('super-admin.design-system'))
            ->assertForbidden();
    }

    public function test_design_system_is_hidden_outside_development(): void
    {
        $superAdmin = User::factory()->withRole(RoleSlug::SuperAdmin)->create();
        $this->app->detectEnvironment(fn (): string => 'production');

        $this->actingAs($superAdmin)
            ->get('/design-system')
            ->assertNotFound();
    }
}
