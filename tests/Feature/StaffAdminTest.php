<?php

namespace Tests\Feature;

use App\Enums\RoleSlug;
use App\Enums\UserStatus;
use App\Models\Institution;
use App\Models\Program;
use App\Models\ProgramBatch;
use App\Models\User;
use App\Services\ProgramContextService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StaffAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_admin_can_create_update_and_soft_delete_staff(): void
    {
        [$skuadBatch, $creatorBatch] = $this->programBatches();
        $admin = User::factory()->withRole(RoleSlug::Admin)->create();

        $this->actingAs($admin)
            ->get(route('admin.staff.create'))
            ->assertOk()
            ->assertSee('Tambah staff')
            ->assertSee('Nama lengkap')
            ->assertSee('Penempatan program')
            ->assertSee('SKUAD')
            ->assertSee('data-password-toggle="password"', false)
            ->assertSee('data-password-toggle="password_confirmation"', false)
            ->assertSee('Minimal 12 karakter');

        $this->actingAs($admin)->post(route('admin.staff.store'), $this->payload([$skuadBatch->id]))
            ->assertRedirect(route('admin.staff.index'));

        $staff = User::query()->where('email', 'guru@skuad.test')->firstOrFail();
        $this->assertTrue(Hash::check('Password1234', $staff->password));
        $this->assertNotNull($staff->email_verified_at);
        $this->assertTrue($staff->hasRole(RoleSlug::Teacher));
        $this->assertDatabaseHas('teacher_profiles', [
            'user_id' => $staff->id,
            'employee_number' => 'PEG-001',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('program_batch_staff', [
            'program_batch_id' => $skuadBatch->id,
            'user_id' => $staff->id,
            'assigned_by' => $admin->id,
        ]);

        $availableBatchIds = app(ProgramContextService::class)->availableBatches($staff)->pluck('id')->all();
        $this->assertSame([$skuadBatch->id], $availableBatchIds);
        $this->assertNotContains($creatorBatch->id, $availableBatchIds);

        $this->actingAs($admin)->put(route('admin.staff.update', $staff), [
            ...$this->payload([$creatorBatch->id]),
            'name' => 'Guru Diperbarui',
            'password' => '',
            'password_confirmation' => '',
            'role' => RoleSlug::Coach->value,
            'is_active' => 0,
        ])->assertRedirect(route('admin.staff.index'));

        $staff->refresh();
        $this->assertSame(UserStatus::Suspended, $staff->status);
        $this->assertTrue($staff->hasRole(RoleSlug::Coach));
        $this->assertFalse($staff->teacherProfile->is_active);
        $this->assertDatabaseMissing('program_batch_staff', [
            'program_batch_id' => $skuadBatch->id,
            'user_id' => $staff->id,
        ]);
        $this->assertDatabaseHas('program_batch_staff', [
            'program_batch_id' => $creatorBatch->id,
            'user_id' => $staff->id,
        ]);

        $profile = $staff->teacherProfile;
        $this->actingAs($admin)
            ->delete(route('admin.staff.destroy', $staff))
            ->assertRedirect(route('admin.staff.index'));

        $this->assertSoftDeleted($staff);
        $this->assertSoftDeleted($profile);
    }

    public function test_only_super_admin_can_create_or_assign_admin_role(): void
    {
        [$batch] = $this->programBatches();
        $admin = User::factory()->withRole(RoleSlug::Admin)->create();
        $superAdmin = User::factory()->withRole(RoleSlug::SuperAdmin)->create();

        $this->actingAs($admin)->post(route('admin.staff.store'), [
            ...$this->payload([$batch->id]),
            'role' => RoleSlug::Admin->value,
        ])->assertSessionHasErrors('role');

        $this->actingAs($superAdmin)->post(route('admin.staff.store'), [
            ...$this->payload([$batch->id]),
            'role' => RoleSlug::Admin->value,
        ])->assertRedirect(route('admin.staff.index'));

        $createdAdmin = User::query()->where('email', 'guru@skuad.test')->firstOrFail();
        $this->assertTrue($createdAdmin->hasRole(RoleSlug::Admin));
    }

    public function test_staff_validation_and_delete_guards_are_enforced(): void
    {
        [$batch] = $this->programBatches();
        $admin = User::factory()->withRole(RoleSlug::Admin)->create();
        $superAdmin = User::factory()->withRole(RoleSlug::SuperAdmin)->create();
        User::factory()->create(['email' => 'guru@skuad.test']);

        $this->actingAs($admin)->post(route('admin.staff.store'), [
            ...$this->payload([$batch->id]),
            'password' => 'short',
            'password_confirmation' => 'different',
        ])->assertSessionHasErrors([
            'email' => 'Email ini sudah digunakan akun lain. Gunakan email berbeda, atau edit akun yang sudah ada.',
            'password',
        ]);

        $this->actingAs($admin)->from(route('admin.staff.create'))->post(route('admin.staff.store'), [
            ...$this->payload([$batch->id]),
            'email' => 'email-baru@skuad.test',
            'password' => 'Password1234',
            'password_confirmation' => 'Password4321',
        ])->assertRedirect(route('admin.staff.create'))
            ->assertSessionHasErrors([
                'password' => 'Konfirmasi kata sandi tidak sama. Isi kedua kolom password dengan teks yang sama persis.',
            ]);

        $this->actingAs($admin)
            ->delete(route('admin.staff.destroy', $admin))
            ->assertForbidden();
        $this->actingAs($admin)
            ->delete(route('admin.staff.destroy', $superAdmin))
            ->assertForbidden();
    }

    public function test_admin_can_only_place_staff_inside_managed_programs(): void
    {
        [$skuadBatch, $creatorBatch] = $this->programBatches();
        $admin = User::factory()->withRole(RoleSlug::Admin)->create();
        $admin->assignedProgramBatches()->attach($skuadBatch, ['assigned_by' => $admin->id]);

        $this->actingAs($admin)
            ->post(route('admin.staff.store'), $this->payload([$creatorBatch->id]))
            ->assertSessionHasErrors('program_batch_ids');

        $this->assertDatabaseMissing('users', ['email' => 'guru@skuad.test']);
    }

    public function test_teacher_cannot_manage_staff(): void
    {
        $teacher = User::factory()->withRole(RoleSlug::Teacher)->create();

        $this->actingAs($teacher)
            ->get(route('admin.staff.index'))
            ->assertForbidden();
    }

    private function payload(array $programBatchIds = []): array
    {
        return [
            'name' => 'Guru SKUAD',
            'email' => 'guru@skuad.test',
            'password' => 'Password1234',
            'password_confirmation' => 'Password1234',
            'role' => RoleSlug::Teacher->value,
            'program_batch_ids' => $programBatchIds,
            'employee_number' => 'PEG-001',
            'phone' => '081234567890',
            'specialization' => 'Desain Digital',
            'bio' => 'Pembina program SKUAD.',
            'is_active' => 1,
        ];
    }

    /**
     * @return array{0: ProgramBatch, 1: ProgramBatch}
     */
    private function programBatches(): array
    {
        $institution = Institution::query()->create([
            'name' => 'SMPN 1 Cikampek Selatan',
            'slug' => 'smpn-1-cikampek-selatan',
            'type' => 'sekolah',
            'is_active' => true,
        ]);

        $skuad = Program::query()->create([
            'name' => 'SKUAD',
            'slug' => 'skuad',
            'type' => 'ekstrakurikuler',
            'primary_color' => '#0f766e',
            'secondary_color' => '#0f172a',
            'accent_color' => '#f59e0b',
            'is_active' => true,
        ]);

        $creator = Program::query()->create([
            'name' => 'Konten Kreator',
            'slug' => 'konten-kreator',
            'type' => 'pelatihan',
            'primary_color' => '#7c3aed',
            'secondary_color' => '#111827',
            'accent_color' => '#f97316',
            'is_active' => true,
        ]);

        return [
            ProgramBatch::query()->create([
                'program_id' => $skuad->id,
                'institution_id' => $institution->id,
                'name' => 'SKUAD 2026/2027',
                'slug' => 'skuad-2026-2027',
                'period_label' => '2026/2027',
                'audience_type' => 'school',
                'participant_label' => 'Siswa',
                'is_active' => true,
            ]),
            ProgramBatch::query()->create([
                'program_id' => $creator->id,
                'institution_id' => $institution->id,
                'name' => 'Konten Kreator 2026/2027',
                'slug' => 'konten-kreator-2026-2027',
                'period_label' => '2026/2027',
                'audience_type' => 'school',
                'participant_label' => 'Siswa',
                'is_active' => true,
            ]),
        ];
    }
}
