<?php

namespace Database\Seeders;

use App\Enums\RoleSlug;
use App\Enums\UserStatus;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $email = config('development.super_admin.email');
        $password = config('development.super_admin.password');

        if (! is_string($email) || $email === '' || ! is_string($password) || $password === '') {
            $this->command?->warn('Super admin tidak dibuat. Isi DEV_SUPER_ADMIN_EMAIL dan DEV_SUPER_ADMIN_PASSWORD.');

            return;
        }

        $this->call(RoleSeeder::class);

        $role = Role::query()->where('slug', RoleSlug::SuperAdmin->value)->firstOrFail();
        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            $existingSuperAdmins = User::query()
                ->whereHas('roles', fn ($query) => $query->where('roles.id', $role->id))
                ->limit(2)
                ->get();
            $user = $existingSuperAdmins->count() === 1 ? $existingSuperAdmins->first() : new User;
        }

        $user->forceFill([
            'name' => config('development.super_admin.name', 'SKUAD Super Admin'),
            'email' => $email,
            'password' => Hash::make($password),
            'email_verified_at' => now(),
            'status' => UserStatus::Active,
        ])->save();

        $user->roles()->syncWithoutDetaching($role);
    }
}
