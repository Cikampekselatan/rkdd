<?php

namespace Tests\Feature;

use App\Enums\RoleSlug;
use App\Models\AcademicYear;
use App\Models\Institution;
use App\Models\Program;
use App\Models\ProgramBatch;
use App\Models\RegistrationCode;
use App\Models\SchoolClass;
use App\Models\User;
use App\Services\RegistrationCodeHasher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RegistrationCodeAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_high_entropy_registration_code_that_is_revealable_to_admin(): void
    {
        $admin = User::factory()->withRole(RoleSlug::Admin)->create();
        $academicYear = AcademicYear::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.registration-codes.store'), [
            'name' => 'Gelombang Utama',
            'academic_year_id' => $academicYear->id,
            'class_id' => '',
            'max_uses' => 30,
            'starts_at' => now()->subHour()->format('Y-m-d H:i:s'),
            'expires_at' => now()->addWeek()->format('Y-m-d H:i:s'),
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('admin.registration-codes.index'));
        $response->assertSessionHas('generated_code');

        $plainText = session('generated_code');
        $registrationCode = RegistrationCode::query()->firstOrFail();

        $this->assertMatchesRegularExpression('/^SKUAD-(?:[A-Z2-9]{5}-){3}[A-Z2-9]{5}$/', $plainText);
        $this->assertSame(app(RegistrationCodeHasher::class)->hash($plainText), $registrationCode->code_hash);
        $this->assertNotSame($plainText, $registrationCode->code_hash);
        $this->assertSame($plainText, $registrationCode->plain_code_encrypted);
        $this->assertNotSame($plainText, DB::table('registration_codes')->whereKey($registrationCode->id)->value('plain_code_encrypted'));
        $this->assertFalse(Schema::hasColumn('registration_codes', 'code'));
        $this->assertSame(0, $registrationCode->used_count);
        $this->assertNull($registrationCode->class_id);

        $this->actingAs($admin)
            ->get(route('admin.registration-codes.index'))
            ->assertOk()
            ->assertSee($plainText);
    }

    public function test_generated_codes_are_unique(): void
    {
        $admin = User::factory()->withRole(RoleSlug::Admin)->create();
        $academicYear = AcademicYear::factory()->create();
        $payload = [
            'name' => 'Gelombang',
            'academic_year_id' => $academicYear->id,
            'max_uses' => 20,
            'is_active' => 1,
        ];

        $this->actingAs($admin)->post(route('admin.registration-codes.store'), $payload);
        $firstHash = RegistrationCode::query()->value('code_hash');

        $this->actingAs($admin)->post(route('admin.registration-codes.store'), [
            ...$payload,
            'name' => 'Gelombang Kedua',
        ]);

        $this->assertDatabaseCount('registration_codes', 2);
        $this->assertSame(1, RegistrationCode::query()->where('code_hash', $firstHash)->count());
    }

    public function test_admin_can_update_and_delete_an_unused_code(): void
    {
        $admin = User::factory()->withRole(RoleSlug::Admin)->create();
        $registrationCode = RegistrationCode::factory()->create(['created_by' => $admin->id]);
        $academicYear = AcademicYear::factory()->create();
        $schoolClass = SchoolClass::factory()->create(['academic_year_id' => $academicYear->id]);

        $this->actingAs($admin)->put(route('admin.registration-codes.update', $registrationCode), [
            'name' => 'Gelombang Diperbarui',
            'academic_year_id' => $academicYear->id,
            'class_id' => $schoolClass->id,
            'max_uses' => 40,
            'starts_at' => '',
            'expires_at' => now()->addMonth()->format('Y-m-d H:i:s'),
            'is_active' => 0,
        ])->assertRedirect(route('admin.registration-codes.index'));

        $this->assertDatabaseHas('registration_codes', [
            'id' => $registrationCode->id,
            'name' => 'Gelombang Diperbarui',
            'academic_year_id' => $academicYear->id,
            'class_id' => $schoolClass->id,
            'max_uses' => 40,
            'is_active' => false,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.registration-codes.destroy', $registrationCode))
            ->assertRedirect(route('admin.registration-codes.index'));

        $this->assertDatabaseMissing('registration_codes', ['id' => $registrationCode->id]);
    }

    public function test_used_code_cannot_be_deleted(): void
    {
        $admin = User::factory()->withRole(RoleSlug::Admin)->create();
        $registrationCode = RegistrationCode::factory()->create([
            'created_by' => $admin->id,
            'used_count' => 1,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.registration-codes.destroy', $registrationCode))
            ->assertSessionHasErrors('registration_code');

        $this->assertDatabaseHas('registration_codes', ['id' => $registrationCode->id]);
    }

    public function test_registration_code_form_validates_dates_and_usage_limit(): void
    {
        $admin = User::factory()->withRole(RoleSlug::Admin)->create();

        $this->actingAs($admin)->post(route('admin.registration-codes.store'), [
            'name' => '',
            'academic_year_id' => 0,
            'max_uses' => 0,
            'starts_at' => now()->addWeek()->format('Y-m-d H:i:s'),
            'expires_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'is_active' => 1,
        ])->assertSessionHasErrors(['name', 'academic_year_id', 'max_uses', 'expires_at']);

        $this->assertDatabaseCount('registration_codes', 0);
    }

    public function test_registration_code_uses_active_program_batch_when_all_classes_are_allowed(): void
    {
        $admin = User::factory()->withRole(RoleSlug::SuperAdmin)->create();
        $institution = Institution::query()->create([
            'name' => 'SMPN 3 Cikampek',
            'slug' => 'smpn-3-cikampek',
            'type' => 'sekolah',
            'is_active' => true,
        ]);
        $skuadProgram = Program::query()->create([
            'name' => 'SKUAD',
            'slug' => 'skuad',
            'type' => 'ekstrakurikuler',
            'is_active' => true,
        ]);
        $journalismProgram = Program::query()->create([
            'name' => 'Jurnalistik & Media Kreatif',
            'slug' => 'jurnalistik-media-kreatif',
            'type' => 'ekstrakurikuler',
            'is_active' => true,
        ]);
        $skuadYear = AcademicYear::factory()->create(['name' => 'SKUAD 2026/2027']);
        $journalismYear = AcademicYear::factory()->create(['name' => 'Jurnalistik 2026/2027']);
        $skuadBatch = ProgramBatch::query()->create([
            'program_id' => $skuadProgram->id,
            'institution_id' => $institution->id,
            'name' => 'SKUAD 2026/2027',
            'slug' => 'skuad-2026-2027-test',
            'period_label' => 'SKUAD 2026/2027',
            'audience_type' => 'school',
            'participant_label' => 'Siswa',
            'is_active' => true,
        ]);
        $journalismBatch = ProgramBatch::query()->create([
            'program_id' => $journalismProgram->id,
            'institution_id' => $institution->id,
            'name' => 'Jurnalistik & Media Kreatif - SMPN 3 Cikampek - 2026/2027',
            'slug' => 'jurnalistik-media-kreatif-2026-2027-test',
            'period_label' => 'Jurnalistik 2026/2027',
            'audience_type' => 'school',
            'participant_label' => 'Murid Jurnalistik',
            'is_active' => true,
        ]);
        SchoolClass::factory()->create([
            'academic_year_id' => $skuadYear->id,
            'program_batch_id' => $skuadBatch->id,
            'name' => 'SKUAD A',
        ]);
        SchoolClass::factory()->create([
            'academic_year_id' => $journalismYear->id,
            'program_batch_id' => $journalismBatch->id,
            'name' => 'Jurnalistik A',
        ]);

        $this->actingAs($admin)
            ->withSession(['active_program_batch_id' => $journalismBatch->id])
            ->get(route('admin.registration-codes.create'))
            ->assertOk()
            ->assertSee('Jurnalistik 2026/2027')
            ->assertSee('Jurnalistik A')
            ->assertDontSee('SKUAD A');

        $this->actingAs($admin)
            ->withSession(['active_program_batch_id' => $journalismBatch->id])
            ->post(route('admin.registration-codes.store'), [
                'name' => 'Gelombang Jurnalistik',
                'academic_year_id' => $journalismYear->id,
                'class_id' => '',
                'max_uses' => 30,
                'starts_at' => now()->subHour()->format('Y-m-d H:i:s'),
                'expires_at' => now()->addWeek()->format('Y-m-d H:i:s'),
                'is_active' => 1,
            ])->assertRedirect(route('admin.registration-codes.index'));

        $this->assertDatabaseHas('registration_codes', [
            'name' => 'Gelombang Jurnalistik',
            'program_batch_id' => $journalismBatch->id,
            'academic_year_id' => $journalismYear->id,
            'class_id' => null,
        ]);
    }

    public function test_teacher_cannot_manage_codes_but_super_admin_can(): void
    {
        $teacher = User::factory()->withRole(RoleSlug::Teacher)->create();
        $superAdmin = User::factory()->withRole(RoleSlug::SuperAdmin)->create();

        $this->actingAs($teacher)
            ->get(route('admin.registration-codes.index'))
            ->assertForbidden();

        $this->actingAs($superAdmin)
            ->get(route('admin.registration-codes.index'))
            ->assertOk();
    }
}
