<?php

namespace Tests\Feature;

use App\Enums\RoleSlug;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase19ResponsiveUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_and_auth_layouts_expose_responsive_accessibility_contract(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('viewport-fit=cover', false)
            ->assertSee('href="#main-content"', false)
            ->assertSee('id="main-content"', false)
            ->assertSee('mobile-public-actions', false)
            ->assertSee(route('login'), false)
            ->assertSee(route('google.redirect'), false);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('viewport-fit=cover', false)
            ->assertSee('Lewati ke konten utama')
            ->assertSee('id="main-content"', false);
    }

    public function test_dashboard_mobile_navigation_uses_role_specific_destinations(): void
    {
        $roles = [
            [RoleSlug::Teacher, 'teacher.dashboard', ['teacher.learning.index', 'teacher.assignments.index', 'reports.index']],
            [RoleSlug::Admin, 'admin.students.index', ['admin.students.index', 'admin.classes.index', 'documents.index']],
            [RoleSlug::Coach, 'coach.dashboard', ['teacher.learning.index', 'teacher.assignments.index', 'reports.index']],
            [RoleSlug::Principal, 'principal.dashboard', ['principal.dashboard', 'documents.index', 'reports.index']],
        ];

        foreach ($roles as [$role, $entryRoute, $routeNames]) {
            $user = User::factory()->withRole($role)->create();
            $response = $this->actingAs($user)->get(route($entryRoute));

            $response->assertOk()
                ->assertSee('viewport-fit=cover', false)
                ->assertSee('id="main-content"', false);

            foreach ($routeNames as $routeName) {
                $response->assertSee(route($routeName), false);
            }
        }
    }

    public function test_responsive_assets_cover_required_breakpoints_and_interactions(): void
    {
        $css = file_get_contents(resource_path('css/pages/responsive-polish.css'));
        $javascript = file_get_contents(resource_path('js/components/responsive.js'));

        $this->assertStringContainsString('@media (max-width: 1199.98px)', $css);
        $this->assertStringContainsString('@media (max-width: 767.98px)', $css);
        $this->assertStringContainsString('@media (max-width: 430px)', $css);
        $this->assertStringContainsString('min-height: 44px', $css);
        $this->assertStringContainsString('env(safe-area-inset-bottom)', $css);
        $this->assertStringContainsString('.skuad-table.is-responsive-cards', $css);
        $this->assertStringContainsString('.responsive-filter-offcanvas', $css);
        $this->assertStringContainsString(':focus-visible', $css);
        $this->assertStringContainsString('(prefers-contrast: more)', $css);

        $this->assertStringContainsString("document.querySelectorAll('.skuad-table')", $javascript);
        $this->assertStringContainsString('.attendance-filter-card', $javascript);
        $this->assertStringContainsString("button.setAttribute('aria-busy', 'true')", $javascript);
        $this->assertStringContainsString("mobileQuery.addEventListener('change', syncFilters)", $javascript);
    }
}
