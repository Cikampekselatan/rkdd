<?php

namespace Tests\Feature;

use App\Enums\RoleSlug;
use App\Models\User;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SuperAdminSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_development_super_admin_seeder_is_idempotent(): void
    {
        config()->set('development.super_admin', [
            'name' => 'Admin Development',
            'email' => 'admin-development@skuad.local',
            'password' => 'rahasia-development',
        ]);

        $this->app->make(SuperAdminSeeder::class)->run();
        $this->app->make(SuperAdminSeeder::class)->run();

        $user = User::query()->where('email', 'admin-development@skuad.local')->firstOrFail();

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('role_user', 1);
        $this->assertTrue(Hash::check('rahasia-development', $user->password));
        $this->assertTrue($user->hasRole(RoleSlug::SuperAdmin));
    }

    public function test_super_admin_is_not_seeded_in_production(): void
    {
        config()->set('development.super_admin', [
            'name' => 'Admin Production',
            'email' => 'admin-production@skuad.local',
            'password' => 'tidak-boleh-dibuat',
        ]);
        $this->app->detectEnvironment(fn (): string => 'production');

        $this->app->make(SuperAdminSeeder::class)->run();

        $this->assertDatabaseMissing('users', [
            'email' => 'admin-production@skuad.local',
        ]);
    }

    public function test_seeder_renames_the_single_existing_development_super_admin(): void
    {
        config()->set('development.super_admin', [
            'name' => 'Admin Lama',
            'email' => 'admin-lama@skuad.local',
            'password' => 'password-lama',
        ]);
        $this->app->make(SuperAdminSeeder::class)->run();

        config()->set('development.super_admin', [
            'name' => 'Admin Baru',
            'email' => 'admin-baru@skuad.local',
            'password' => 'password-baru',
        ]);
        $this->app->make(SuperAdminSeeder::class)->run();

        $user = User::query()->where('email', 'admin-baru@skuad.local')->firstOrFail();

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseMissing('users', ['email' => 'admin-lama@skuad.local']);
        $this->assertSame('Admin Baru', $user->name);
        $this->assertTrue(Hash::check('password-baru', $user->password));
        $this->assertTrue($user->hasRole(RoleSlug::SuperAdmin));
    }
}
