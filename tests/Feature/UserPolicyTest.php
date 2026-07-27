<?php

namespace Tests\Feature;

use App\Enums\RoleSlug;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_and_update_users_but_cannot_assign_roles(): void
    {
        $admin = User::factory()->withRole(RoleSlug::Admin)->create();
        $subject = User::factory()->create();

        $this->assertTrue($admin->can('viewAny', User::class));
        $this->assertTrue($admin->can('view', $subject));
        $this->assertTrue($admin->can('update', $subject));
        $this->assertFalse($admin->can('assignRole', User::class));
        $this->assertFalse($admin->can('delete', $subject));
    }

    public function test_regular_user_can_only_view_and_update_their_own_record(): void
    {
        $teacher = User::factory()->withRole(RoleSlug::Teacher)->create();
        $otherUser = User::factory()->create();

        $this->assertTrue($teacher->can('view', $teacher));
        $this->assertTrue($teacher->can('update', $teacher));
        $this->assertFalse($teacher->can('view', $otherUser));
        $this->assertFalse($teacher->can('update', $otherUser));
    }

    public function test_super_admin_bypasses_basic_user_policy_restrictions(): void
    {
        $superAdmin = User::factory()->withRole(RoleSlug::SuperAdmin)->create();
        $subject = User::factory()->create();

        $this->assertTrue($superAdmin->can('assignRole', User::class));
        $this->assertTrue($superAdmin->can('delete', $subject));
    }
}
