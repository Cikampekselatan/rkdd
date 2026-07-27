<?php

namespace Tests\Feature;

use App\Enums\RoleSlug;
use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademicYearAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_update_and_switch_the_single_active_academic_year(): void
    {
        $admin = User::factory()->withRole(RoleSlug::Admin)->create();

        $this->actingAs($admin)->post(route('admin.academic-years.store'), [
            'name' => '2026/2027',
            'starts_on' => '2026-07-01',
            'ends_on' => '2027-06-30',
            'is_active' => 0,
        ])->assertRedirect(route('admin.academic-years.index'));

        $first = AcademicYear::query()->firstOrFail();
        $this->assertTrue($first->is_active);

        $this->actingAs($admin)->post(route('admin.academic-years.store'), [
            'name' => '2027/2028',
            'starts_on' => '2027-07-01',
            'ends_on' => '2028-06-30',
            'is_active' => 1,
        ])->assertRedirect(route('admin.academic-years.index'));

        $second = AcademicYear::query()->where('name', '2027/2028')->firstOrFail();
        $this->assertFalse($first->fresh()->is_active);
        $this->assertTrue($second->is_active);
        $this->assertSame(1, AcademicYear::query()->where('is_active', true)->count());

        $this->actingAs($admin)->put(route('admin.academic-years.update', $second), [
            'name' => '2027/2028 Revisi',
            'starts_on' => '2027-07-01',
            'ends_on' => '2028-06-30',
            'is_active' => 1,
        ])->assertRedirect(route('admin.academic-years.index'));

        $this->assertDatabaseHas('academic_years', [
            'id' => $second->id,
            'name' => '2027/2028 Revisi',
            'is_active' => true,
        ]);
    }

    public function test_active_year_cannot_be_deactivated_or_deleted(): void
    {
        $admin = User::factory()->withRole(RoleSlug::Admin)->create();
        $academicYear = AcademicYear::factory()->active()->create();

        $this->actingAs($admin)->put(route('admin.academic-years.update', $academicYear), [
            'name' => $academicYear->name,
            'starts_on' => $academicYear->starts_on->format('Y-m-d'),
            'ends_on' => $academicYear->ends_on->format('Y-m-d'),
            'is_active' => 0,
        ])->assertSessionHasErrors('is_active');

        $this->actingAs($admin)
            ->delete(route('admin.academic-years.destroy', $academicYear))
            ->assertSessionHasErrors('academic_year');

        $this->assertTrue($academicYear->fresh()->is_active);
        $this->assertNotSoftDeleted($academicYear);
    }

    public function test_unused_inactive_year_can_be_archived_but_used_year_is_protected(): void
    {
        $admin = User::factory()->withRole(RoleSlug::Admin)->create();
        $unused = AcademicYear::factory()->create();
        $used = AcademicYear::factory()->create();
        SchoolClass::factory()->create(['academic_year_id' => $used->id]);

        $this->actingAs($admin)
            ->delete(route('admin.academic-years.destroy', $used))
            ->assertSessionHasErrors('academic_year');

        $this->actingAs($admin)
            ->delete(route('admin.academic-years.destroy', $unused))
            ->assertRedirect(route('admin.academic-years.index'));

        $this->assertNotSoftDeleted($used);
        $this->assertSoftDeleted($unused);
    }

    public function test_academic_year_validation_and_authorization_are_enforced(): void
    {
        $admin = User::factory()->withRole(RoleSlug::Admin)->create();
        $teacher = User::factory()->withRole(RoleSlug::Teacher)->create();
        AcademicYear::factory()->create(['name' => '2026/2027']);

        $this->actingAs($admin)->post(route('admin.academic-years.store'), [
            'name' => '2026/2027',
            'starts_on' => '2027-07-01',
            'ends_on' => '2027-06-30',
            'is_active' => 0,
        ])->assertSessionHasErrors(['name', 'ends_on']);

        $this->actingAs($teacher)
            ->get(route('admin.academic-years.index'))
            ->assertForbidden();
    }
}
