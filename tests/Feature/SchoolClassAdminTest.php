<?php

namespace Tests\Feature;

use App\Enums\RoleSlug;
use App\Models\AcademicYear;
use App\Models\Institution;
use App\Models\Program;
use App\Models\ProgramBatch;
use App\Models\RegistrationCode;
use App\Models\SchoolClass;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\ProgramContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolClassAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_and_update_a_class_with_a_teacher(): void
    {
        $admin = User::factory()->withRole(RoleSlug::Admin)->create();
        $teacher = User::factory()->withRole(RoleSlug::Teacher)->create();
        $academicYear = AcademicYear::factory()->active()->create();

        $this->actingAs($admin)->post(route('admin.classes.store'), [
            'academic_year_id' => $academicYear->id,
            'name' => 'Kelompok SKUAD 2028/2029',
            'code' => 'SKUAD-2028',
            'homeroom_teacher_id' => $teacher->id,
            'capacity' => 32,
            'is_active' => 1,
        ])->assertRedirect(route('admin.classes.index'));

        $schoolClass = SchoolClass::query()->firstOrFail();
        $this->assertSame($teacher->id, $schoolClass->homeroom_teacher_id);

        $this->actingAs($admin)->put(route('admin.classes.update', $schoolClass), [
            'academic_year_id' => $academicYear->id,
            'name' => 'Kelompok SKUAD Utama',
            'code' => 'SKUAD-UTAMA',
            'homeroom_teacher_id' => '',
            'capacity' => 28,
            'is_active' => 0,
        ])->assertRedirect(route('admin.classes.index'));

        $this->assertDatabaseHas('classes', [
            'id' => $schoolClass->id,
            'name' => 'Kelompok SKUAD Utama',
            'code' => 'SKUAD-UTAMA',
            'homeroom_teacher_id' => null,
            'capacity' => 28,
            'is_active' => false,
        ]);
    }

    public function test_group_validation_rejects_second_group_duplicate_code_and_non_coordinator(): void
    {
        $admin = User::factory()->withRole(RoleSlug::Admin)->create();
        $student = User::factory()->withRole(RoleSlug::Student)->create();
        $academicYear = AcademicYear::factory()->create();
        SchoolClass::factory()->create(['academic_year_id' => $academicYear->id, 'code' => '7A']);

        $this->actingAs($admin)->post(route('admin.classes.store'), [
            'academic_year_id' => $academicYear->id,
            'name' => 'Duplikat',
            'code' => '7A',
            'homeroom_teacher_id' => $student->id,
            'capacity' => 0,
            'is_active' => 1,
        ])->assertSessionHasErrors(['academic_year_id', 'code', 'homeroom_teacher_id', 'capacity']);

        $this->assertSame(1, SchoolClass::query()->count());
    }

    public function test_each_program_can_have_its_own_group_in_the_same_academic_year(): void
    {
        $admin = User::factory()->withRole(RoleSlug::Admin)->create();
        $teacher = User::factory()->withRole(RoleSlug::Teacher)->create();
        $academicYear = AcademicYear::factory()->active()->create(['name' => '2026/2027']);
        [$skuadBatch, $contentCoreBatch] = $this->programBatches($academicYear);

        $this->actingAs($admin)
            ->withSession(['active_program_batch_id' => $skuadBatch->id])
            ->post(route('admin.classes.store'), [
                'program_batch_id' => $skuadBatch->id,
                'academic_year_id' => $academicYear->id,
                'name' => 'Kelompok SKUAD 2026/2027',
                'code' => 'UTAMA',
                'homeroom_teacher_id' => $teacher->id,
                'capacity' => 100,
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.classes.index'));

        $this->actingAs($admin)
            ->withSession(['active_program_batch_id' => $contentCoreBatch->id])
            ->post(route('admin.classes.store'), [
                'program_batch_id' => $contentCoreBatch->id,
                'academic_year_id' => $academicYear->id,
                'name' => 'Kelompok Content Core 2026/2027',
                'code' => 'UTAMA',
                'homeroom_teacher_id' => $teacher->id,
                'capacity' => 100,
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.classes.index'));

        $this->assertDatabaseHas('classes', [
            'program_batch_id' => $skuadBatch->id,
            'academic_year_id' => $academicYear->id,
            'code' => 'UTAMA',
        ]);
        $this->assertDatabaseHas('classes', [
            'program_batch_id' => $contentCoreBatch->id,
            'academic_year_id' => $academicYear->id,
            'code' => 'UTAMA',
        ]);
        $this->assertSame(2, SchoolClass::query()->count());
    }

    public function test_super_admin_can_create_group_for_another_program_from_the_form_selector(): void
    {
        $superAdmin = User::factory()->withRole(RoleSlug::SuperAdmin)->create();
        $coach = User::factory()->withRole(RoleSlug::Coach)->create(['name' => 'Dede Rahmat']);
        $academicYear = AcademicYear::factory()->active()->create(['name' => '2026/2027']);
        [$skuadBatch, $contentCoreBatch] = $this->programBatches($academicYear);

        SchoolClass::factory()->create([
            'program_batch_id' => $skuadBatch->id,
            'academic_year_id' => $academicYear->id,
            'code' => 'UTAMA',
        ]);

        $this->actingAs($superAdmin)
            ->withSession(['active_program_batch_id' => $skuadBatch->id])
            ->get(route('admin.classes.create'))
            ->assertOk()
            ->assertSee('Program/Periode tujuan')
            ->assertSee('Content Core');

        $this->actingAs($superAdmin)
            ->withSession(['active_program_batch_id' => $skuadBatch->id])
            ->post(route('admin.classes.store'), [
                'program_batch_id' => $contentCoreBatch->id,
                'academic_year_id' => $academicYear->id,
                'name' => 'Kelompok Content Core 2026/2027',
                'code' => 'UTAMA',
                'homeroom_teacher_id' => $coach->id,
                'capacity' => 100,
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.classes.index'))
            ->assertSessionHas('active_program_batch_id', $contentCoreBatch->id);

        $this->assertDatabaseHas('classes', [
            'program_batch_id' => $contentCoreBatch->id,
            'academic_year_id' => $academicYear->id,
            'code' => 'UTAMA',
        ]);
        $this->assertDatabaseHas('program_batch_staff', [
            'program_batch_id' => $contentCoreBatch->id,
            'user_id' => $coach->id,
            'assigned_by' => $superAdmin->id,
        ]);
    }

    public function test_same_coordinator_automatically_manages_new_program_when_selected_for_group(): void
    {
        $superAdmin = User::factory()->withRole(RoleSlug::SuperAdmin)->create();
        $coach = User::factory()->withRole(RoleSlug::Coach)->create(['name' => 'Dede Rahmat']);
        $academicYear = AcademicYear::factory()->active()->create(['name' => '2026/2027']);
        [$contentCoreBatch, $journalisticBatch] = $this->programBatches($academicYear);

        $coach->assignedProgramBatches()->attach($contentCoreBatch, ['assigned_by' => $superAdmin->id]);

        $this->assertSame(
            [$contentCoreBatch->id],
            app(ProgramContextService::class)->availableBatches($coach)->pluck('id')->all(),
        );

        $this->actingAs($superAdmin)
            ->withSession(['active_program_batch_id' => $contentCoreBatch->id])
            ->post(route('admin.classes.store'), [
                'program_batch_id' => $journalisticBatch->id,
                'academic_year_id' => $academicYear->id,
                'name' => 'Kelompok Jurnalistik 2026/2027',
                'code' => 'JURN-2026',
                'homeroom_teacher_id' => $coach->id,
                'capacity' => 100,
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.classes.index'))
            ->assertSessionHas('active_program_batch_id', $journalisticBatch->id);

        $this->assertDatabaseHas('program_batch_staff', [
            'program_batch_id' => $contentCoreBatch->id,
            'user_id' => $coach->id,
        ]);
        $this->assertDatabaseHas('program_batch_staff', [
            'program_batch_id' => $journalisticBatch->id,
            'user_id' => $coach->id,
            'assigned_by' => $superAdmin->id,
        ]);

        $availableBatchIds = app(ProgramContextService::class)
            ->availableBatches($coach->refresh())
            ->pluck('id')
            ->sort()
            ->values()
            ->all();

        $this->assertSame(
            collect([$contentCoreBatch->id, $journalisticBatch->id])->sort()->values()->all(),
            $availableBatchIds,
        );
    }

    public function test_group_form_scopes_academic_years_to_active_program_batch(): void
    {
        $superAdmin = User::factory()->withRole(RoleSlug::SuperAdmin)->create();
        $skuadYear = AcademicYear::factory()->active()->create(['name' => 'SKUAD 2026/2027']);
        $contentYear = AcademicYear::factory()->create(['name' => 'Content Core']);
        $journalismYear = AcademicYear::factory()->create(['name' => 'Journi3 2026/2027']);
        [$skuadBatch, $journalismBatch] = $this->programBatches($skuadYear, 'Jurnalistik & Media Kreatif', 'jurnalistik-media-kreatif', $journalismYear->name);

        SchoolClass::factory()->create([
            'program_batch_id' => $skuadBatch->id,
            'academic_year_id' => $contentYear->id,
            'name' => 'Content Core A',
        ]);

        $response = $this->actingAs($superAdmin)
            ->withSession(['active_program_batch_id' => $journalismBatch->id])
            ->get(route('admin.classes.create'))
            ->assertOk()
            ->assertSee('Journi3 2026/2027');

        preg_match('/<select[^>]+id="academic_year_id"[^>]*>(.*?)<\/select>/s', $response->getContent(), $matches);

        $this->assertNotEmpty($matches[1] ?? null);
        $this->assertStringContainsString('Journi3 2026/2027', $matches[1]);
        $this->assertStringNotContainsString('SKUAD 2026/2027', $matches[1]);
        $this->assertStringNotContainsString('Content Core', $matches[1]);
    }

    public function test_unused_class_can_be_archived_and_referenced_classes_are_protected(): void
    {
        $admin = User::factory()->withRole(RoleSlug::Admin)->create();
        $academicYear = AcademicYear::factory()->create();
        $unused = SchoolClass::factory()->create(['academic_year_id' => $academicYear->id]);
        $withProfile = SchoolClass::factory()->create(['academic_year_id' => $academicYear->id]);
        $withCode = SchoolClass::factory()->create(['academic_year_id' => $academicYear->id]);

        StudentProfile::factory()->create(['class_id' => $withProfile->id]);
        RegistrationCode::factory()->create([
            'academic_year_id' => $academicYear->id,
            'class_id' => $withCode->id,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.classes.destroy', $withProfile))
            ->assertSessionHasErrors('school_class');
        $this->actingAs($admin)
            ->delete(route('admin.classes.destroy', $withCode))
            ->assertSessionHasErrors('school_class');
        $this->actingAs($admin)
            ->delete(route('admin.classes.destroy', $unused))
            ->assertRedirect(route('admin.classes.index'));

        $this->assertNotSoftDeleted($withProfile);
        $this->assertNotSoftDeleted($withCode);
        $this->assertSoftDeleted($unused);
    }

    public function test_teacher_cannot_manage_classes(): void
    {
        $teacher = User::factory()->withRole(RoleSlug::Teacher)->create();

        $this->actingAs($teacher)
            ->get(route('admin.classes.index'))
            ->assertForbidden();
    }

    /**
     * @return array{0: ProgramBatch, 1: ProgramBatch}
     */
    private function programBatches(
        AcademicYear $academicYear,
        string $secondProgramName = 'Content Core',
        string $secondProgramSlug = 'content-core',
        ?string $secondPeriodLabel = null,
    ): array {
        $institution = Institution::query()->create([
            'name' => 'RKDD Cikampek Selatan',
            'slug' => 'rkdd-cikampek-selatan',
            'type' => 'desa',
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

        $contentCore = Program::query()->create([
            'name' => $secondProgramName,
            'slug' => $secondProgramSlug,
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
                'name' => 'SKUAD '.$academicYear->name,
                'slug' => 'skuad-'.str_replace('/', '', $academicYear->name),
                'period_label' => $academicYear->name,
                'audience_type' => 'school',
                'participant_label' => 'Siswa',
                'is_active' => true,
            ]),
            ProgramBatch::query()->create([
                'program_id' => $contentCore->id,
                'institution_id' => $institution->id,
                'name' => $secondProgramName.' '.($secondPeriodLabel ?? $academicYear->name),
                'slug' => $secondProgramSlug.'-'.str_replace('/', '', $secondPeriodLabel ?? $academicYear->name),
                'period_label' => $secondPeriodLabel ?? $academicYear->name,
                'audience_type' => 'village',
                'participant_label' => 'Peserta',
                'is_active' => true,
            ]),
        ];
    }
}
