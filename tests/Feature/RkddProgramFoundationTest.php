<?php

namespace Tests\Feature;

use App\Enums\RoleSlug;
use App\Models\AcademicYear;
use App\Models\Institution;
use App\Models\LearningModule;
use App\Models\Program;
use App\Models\ProgramBatch;
use App\Models\RegistrationCode;
use App\Models\SchoolClass;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RkddProgramFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_super_admin_can_manage_program_institution_and_batch_context(): void
    {
        Storage::fake('public');
        $superAdmin = User::factory()->withRole(RoleSlug::SuperAdmin)->create();
        AcademicYear::query()->create([
            'name' => '2026/2027',
            'starts_on' => '2026-07-01',
            'ends_on' => '2027-06-30',
            'is_active' => true,
        ]);
        $primaryInstitution = Institution::query()->create([
            'name' => 'SMP IT Mentari Ilmu Jatisari',
            'slug' => 'smp-it-mentari-ilmu-jatisari',
            'type' => 'sekolah',
            'is_active' => true,
        ]);

        $this->actingAs($superAdmin)
            ->get(route('super-admin.programs.index'))
            ->assertOk()
            ->assertSee('Program RKDD');

        $this->actingAs($superAdmin)
            ->get(route('super-admin.programs.create'))
            ->assertOk()
            ->assertSee('Preset cepat')
            ->assertSee('Konten Kreator')
            ->assertSee('Lembaga penyelenggara')
            ->assertSee('SMP IT Mentari Ilmu Jatisari')
            ->assertSee('data-program-theme-preview', false)
            ->assertSee('data-program-theme-preset', false);

        $this->actingAs($superAdmin)
            ->post(route('super-admin.programs.store'), [
                'name' => 'Konten Kreator',
                'slug' => '',
                'type' => 'pelatihan',
                'description' => 'Pelatihan produksi konten digital.',
                'primary_color' => '#7c3aed',
                'secondary_color' => '#111827',
                'accent_color' => '#f97316',
                'institution_id' => $primaryInstitution->id,
                'logo' => UploadedFile::fake()->image('logo.png', 900, 900)->size(1800),
                'banner' => UploadedFile::fake()->image('banner.png', 2400, 1200)->size(4000),
                'is_active' => 1,
            ])
            ->assertRedirect(route('super-admin.programs.index'));

        $program = Program::query()->where('slug', 'konten-kreator')->firstOrFail();
        $this->assertNotNull($program->logo_path);
        $this->assertNotNull($program->banner_path);
        Storage::disk('public')->assertExists($program->logo_path);
        Storage::disk('public')->assertExists($program->banner_path);
        $this->assertLessThanOrEqual(512000, Storage::disk('public')->size($program->logo_path));
        $this->assertLessThanOrEqual(512000, Storage::disk('public')->size($program->banner_path));
        $this->assertDatabaseHas('program_batches', [
            'program_id' => $program->id,
            'institution_id' => $primaryInstitution->id,
            'period_label' => '2026/2027',
            'is_active' => true,
        ]);

        $this->actingAs($superAdmin)
            ->post(route('super-admin.institutions.store'), [
                'name' => 'SMPN 1 Cikampek Selatan',
                'slug' => '',
                'type' => 'sekolah',
                'address' => 'Cikampek Selatan',
                'description' => 'Sekolah mitra program RKDD.',
                'is_active' => 1,
            ])
            ->assertRedirect(route('super-admin.institutions.index'));

        $institution = Institution::query()->where('slug', 'smpn-1-cikampek-selatan')->firstOrFail();

        $this->actingAs($superAdmin)
            ->post(route('super-admin.program-batches.store'), [
                'program_id' => $program->id,
                'institution_id' => $institution->id,
                'name' => 'Konten Kreator SMPN 1 2026/2027',
                'slug' => '',
                'period_label' => '2026/2027',
                'starts_on' => '2026-07-01',
                'ends_on' => '2027-06-30',
                'audience_type' => 'school',
                'participant_label' => 'Siswa',
                'is_active' => 1,
            ])
            ->assertRedirect(route('super-admin.program-batches.index'));

        $this->assertDatabaseHas('program_batches', [
            'slug' => 'konten-kreator-smpn-1-20262027',
            'program_id' => $program->id,
            'institution_id' => $institution->id,
            'participant_label' => 'Siswa',
        ]);

        $this->actingAs($superAdmin)
            ->get(route('super-admin.programs.index'))
            ->assertOk()
            ->assertSee('SMP IT Mentari Ilmu Jatisari')
            ->assertSee('SMPN 1 Cikampek Selatan')
            ->assertSee('program-theme-chip', false);

        $this->actingAs($superAdmin)
            ->get(route('super-admin.programs.edit', $program))
            ->assertOk()
            ->assertSee('Lembaga penyelenggara')
            ->assertSee('SMP IT Mentari Ilmu Jatisari');

        $updatedInstitution = Institution::query()->create([
            'name' => 'SMP IT Mentari Ilmu Jatisari Baru',
            'slug' => 'smp-it-mentari-ilmu-jatisari-baru',
            'type' => 'sekolah',
            'is_active' => true,
        ]);

        $this->actingAs($superAdmin)
            ->put(route('super-admin.programs.update', $program), [
                'name' => 'Konten Kreator',
                'slug' => 'konten-kreator',
                'type' => 'pelatihan',
                'description' => 'Pelatihan produksi konten digital.',
                'primary_color' => '#7c3aed',
                'secondary_color' => '#111827',
                'accent_color' => '#f97316',
                'institution_id' => $updatedInstitution->id,
                'is_active' => 1,
            ])
            ->assertRedirect(route('super-admin.programs.index'));

        $this->assertDatabaseHas('program_batches', [
            'program_id' => $program->id,
            'institution_id' => $updatedInstitution->id,
        ]);
    }

    public function test_program_theme_colors_must_use_safe_hex_values(): void
    {
        $superAdmin = User::factory()->withRole(RoleSlug::SuperAdmin)->create();

        $this->actingAs($superAdmin)
            ->post(route('super-admin.programs.store'), [
                'name' => 'Tema Rusak',
                'slug' => '',
                'type' => 'pelatihan',
                'description' => 'Program dengan warna tidak valid.',
                'primary_color' => 'javascript:alert(1)',
                'secondary_color' => '#111827',
                'accent_color' => '#f97316',
                'is_active' => 1,
            ])
            ->assertSessionHasErrors('primary_color');
    }

    public function test_dashboard_reads_default_program_theme(): void
    {
        $superAdmin = User::factory()->withRole(RoleSlug::SuperAdmin)->create();
        Program::query()->create([
            'name' => 'SKUAD',
            'slug' => 'skuad',
            'type' => 'ekstrakurikuler',
            'primary_color' => '#123456',
            'secondary_color' => '#101820',
            'accent_color' => '#ffcc00',
            'is_active' => true,
        ]);

        $this->actingAs($superAdmin)
            ->get(route('super-admin.dashboard'))
            ->assertOk()
            ->assertSee('--dashboard-primary: #123456', false)
            ->assertSee('--dashboard-secondary: #101820', false)
            ->assertSee('--dashboard-accent: #ffcc00', false)
            ->assertSee('SKUAD');
    }

    public function test_staff_can_switch_active_program_context_in_session(): void
    {
        $superAdmin = User::factory()->withRole(RoleSlug::SuperAdmin)->create();
        $institution = Institution::query()->create([
            'name' => 'RKDD Cikampek Selatan',
            'slug' => 'rkdd-cikampek-selatan',
            'type' => 'rkdd',
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
            'secondary_color' => '#1e1b4b',
            'accent_color' => '#f97316',
            'is_active' => true,
        ]);
        ProgramBatch::query()->create([
            'program_id' => $skuad->id,
            'institution_id' => $institution->id,
            'name' => 'SKUAD 2026/2027',
            'slug' => 'skuad-2026-2027',
            'period_label' => '2026/2027',
            'audience_type' => 'school',
            'participant_label' => 'Siswa',
            'is_active' => true,
        ]);
        $creatorBatch = ProgramBatch::query()->create([
            'program_id' => $creator->id,
            'institution_id' => $institution->id,
            'name' => 'Konten Kreator Batch 1',
            'slug' => 'konten-kreator-batch-1',
            'period_label' => 'Batch 1',
            'audience_type' => 'community',
            'participant_label' => 'Peserta Didik',
            'is_active' => true,
        ]);

        $this->actingAs($superAdmin)
            ->get(route('super-admin.dashboard'))
            ->assertOk()
            ->assertSee('Program aktif')
            ->assertSee('SKUAD · RKDD Cikampek Selatan · 2026/2027')
            ->assertSee('Konten Kreator · RKDD Cikampek Selatan · Batch 1')
            ->assertSee('--dashboard-primary: #0f766e', false);

        $this->actingAs($superAdmin)
            ->put(route('program-context.update'), ['program_batch_id' => $creatorBatch->id])
            ->assertRedirect()
            ->assertSessionHas('active_program_batch_id', $creatorBatch->id);

        $this->actingAs($superAdmin)
            ->get(route('super-admin.dashboard'))
            ->assertOk()
            ->assertSee('--dashboard-primary: #7c3aed', false)
            ->assertSee('Konten Kreator');
    }

    public function test_student_cannot_switch_to_unjoined_program_context(): void
    {
        $student = User::factory()->withRole(RoleSlug::Student)->create();
        $foundation = $this->createTwoProgramBatches();

        $this->actingAs($student)
            ->put(route('program-context.update'), ['program_batch_id' => $foundation['creatorBatch']->id])
            ->assertForbidden();
    }

    public function test_non_super_admin_cannot_access_program_foundation_crud(): void
    {
        $admin = User::factory()->withRole(RoleSlug::Admin)->create();

        $this->actingAs($admin)
            ->get(route('super-admin.programs.index'))
            ->assertForbidden();
    }

    public function test_program_with_batch_cannot_be_deleted(): void
    {
        $superAdmin = User::factory()->withRole(RoleSlug::SuperAdmin)->create();
        $program = Program::query()->create([
            'name' => 'SKUAD',
            'slug' => 'skuad',
            'type' => 'ekstrakurikuler',
            'primary_color' => '#0f766e',
            'secondary_color' => '#0f172a',
            'accent_color' => '#f59e0b',
            'is_active' => true,
        ]);
        $institution = Institution::query()->create([
            'name' => 'RKDD Cikampek Selatan',
            'slug' => 'rkdd-cikampek-selatan',
            'type' => 'rkdd',
            'is_active' => true,
        ]);
        ProgramBatch::query()->create([
            'program_id' => $program->id,
            'institution_id' => $institution->id,
            'name' => 'SKUAD 2026/2027',
            'slug' => 'skuad-2026-2027',
            'period_label' => '2026/2027',
            'audience_type' => 'school',
            'participant_label' => 'Siswa',
            'is_active' => true,
        ]);

        $this->actingAs($superAdmin)
            ->delete(route('super-admin.programs.destroy', $program))
            ->assertSessionHasErrors('program');

        $this->assertDatabaseHas('programs', ['id' => $program->id, 'deleted_at' => null]);
    }

    public function test_each_program_keeps_only_one_active_batch_without_affecting_other_programs(): void
    {
        $superAdmin = User::factory()->withRole(RoleSlug::SuperAdmin)->create();
        $institution = Institution::query()->create([
            'name' => 'SMP IT Mentari Ilmu Jatisari',
            'slug' => 'smp-it-mentari-ilmu-jatisari',
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
        $contentCore = Program::query()->create([
            'name' => 'Content Core',
            'slug' => 'content-core',
            'type' => 'pelatihan',
            'primary_color' => '#7c3aed',
            'secondary_color' => '#1e1b4b',
            'accent_color' => '#f97316',
            'is_active' => true,
        ]);
        $skuadBatch = ProgramBatch::query()->create([
            'program_id' => $skuad->id,
            'institution_id' => $institution->id,
            'name' => 'SKUAD 2026/2027',
            'slug' => 'skuad-2026-2027',
            'period_label' => '2026/2027',
            'audience_type' => 'school',
            'participant_label' => 'Siswa',
            'is_active' => true,
        ]);
        $contentBatch = ProgramBatch::query()->create([
            'program_id' => $contentCore->id,
            'institution_id' => $institution->id,
            'name' => 'Content Core',
            'slug' => 'content-core',
            'period_label' => 'Batch 1',
            'audience_type' => 'school',
            'participant_label' => 'Siswa',
            'is_active' => true,
        ]);

        $this->actingAs($superAdmin)
            ->post(route('super-admin.program-batches.store'), [
                'program_id' => $skuad->id,
                'institution_id' => $institution->id,
                'name' => 'SKUAD 2027/2028',
                'slug' => '',
                'period_label' => '2027/2028',
                'starts_on' => '2027-07-01',
                'ends_on' => '2028-06-30',
                'audience_type' => 'school',
                'participant_label' => 'Siswa',
                'is_active' => 1,
            ])
            ->assertRedirect(route('super-admin.program-batches.index'));

        $this->assertFalse($skuadBatch->fresh()->is_active);
        $this->assertTrue($contentBatch->fresh()->is_active);
        $this->assertSame(1, ProgramBatch::query()->where('program_id', $skuad->id)->where('is_active', true)->count());
        $this->assertSame(1, ProgramBatch::query()->where('program_id', $contentCore->id)->where('is_active', true)->count());

        $this->actingAs($superAdmin)
            ->put(route('super-admin.program-batches.update', $contentBatch), [
                'program_id' => $contentCore->id,
                'institution_id' => $institution->id,
                'name' => 'Content Core',
                'slug' => 'content-core',
                'period_label' => 'Batch 1',
                'starts_on' => '2026-07-27',
                'ends_on' => '2027-07-07',
                'audience_type' => 'school',
                'participant_label' => 'Siswa',
                'is_active' => 0,
            ])
            ->assertSessionHasErrors('is_active');
    }

    public function test_active_program_context_filters_core_operational_pages(): void
    {
        $admin = User::factory()->withRole(RoleSlug::SuperAdmin)->create();
        $teacher = User::factory()->withRole(RoleSlug::Teacher)->create();
        $foundation = $this->createTwoProgramBatches();
        $academicYear = AcademicYear::factory()->active()->create();

        $skuadClass = SchoolClass::factory()->create([
            'academic_year_id' => $academicYear->id,
            'program_batch_id' => $foundation['skuadBatch']->id,
            'name' => 'Kelompok SKUAD Rahasia',
            'code' => 'SKUAD-SECRET',
        ]);
        $creatorClass = SchoolClass::factory()->create([
            'academic_year_id' => $academicYear->id,
            'program_batch_id' => $foundation['creatorBatch']->id,
            'name' => 'Kelompok Konten Kreator Aktif',
            'code' => 'CREATOR-ACTIVE',
        ]);

        RegistrationCode::factory()->forPlainText('SKUAD-ONLY')->create([
            'name' => 'Kode SKUAD Rahasia',
            'academic_year_id' => $academicYear->id,
            'program_batch_id' => $foundation['skuadBatch']->id,
            'class_id' => $skuadClass->id,
        ]);
        RegistrationCode::factory()->forPlainText('CREATOR-ONLY')->create([
            'name' => 'Kode Konten Kreator Aktif',
            'academic_year_id' => $academicYear->id,
            'program_batch_id' => $foundation['creatorBatch']->id,
            'class_id' => $creatorClass->id,
        ]);

        LearningModule::factory()->create([
            'academic_year_id' => $academicYear->id,
            'program_batch_id' => $foundation['skuadBatch']->id,
            'title' => 'Modul SKUAD Tidak Aktif',
        ]);
        LearningModule::factory()->create([
            'academic_year_id' => $academicYear->id,
            'program_batch_id' => $foundation['creatorBatch']->id,
            'title' => 'Modul Konten Kreator Aktif',
        ]);

        $this->withSession(['active_program_batch_id' => $foundation['creatorBatch']->id])
            ->actingAs($admin)
            ->get(route('admin.classes.index'))
            ->assertOk()
            ->assertSee('Kelompok Konten Kreator Aktif')
            ->assertDontSee('Kelompok SKUAD Rahasia');

        $this->withSession(['active_program_batch_id' => $foundation['creatorBatch']->id])
            ->actingAs($admin)
            ->get(route('admin.registration-codes.index'))
            ->assertOk()
            ->assertSee('CREATOR-ONLY')
            ->assertDontSee('SKUAD-ONLY');

        $this->withSession(['active_program_batch_id' => $foundation['creatorBatch']->id])
            ->actingAs($teacher)
            ->get(route('teacher.learning.index'))
            ->assertOk()
            ->assertSee('Modul Konten Kreator Aktif')
            ->assertDontSee('Modul SKUAD Tidak Aktif');
    }

    /**
     * @return array{skuadBatch: ProgramBatch, creatorBatch: ProgramBatch}
     */
    private function createTwoProgramBatches(): array
    {
        $institution = Institution::query()->create([
            'name' => 'RKDD Cikampek Selatan',
            'slug' => 'rkdd-cikampek-selatan',
            'type' => 'rkdd',
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
            'secondary_color' => '#1e1b4b',
            'accent_color' => '#f97316',
            'is_active' => true,
        ]);

        return [
            'skuadBatch' => ProgramBatch::query()->create([
                'program_id' => $skuad->id,
                'institution_id' => $institution->id,
                'name' => 'SKUAD 2026/2027',
                'slug' => 'skuad-2026-2027',
                'period_label' => '2026/2027',
                'audience_type' => 'school',
                'participant_label' => 'Siswa',
                'is_active' => true,
            ]),
            'creatorBatch' => ProgramBatch::query()->create([
                'program_id' => $creator->id,
                'institution_id' => $institution->id,
                'name' => 'Konten Kreator Batch 1',
                'slug' => 'konten-kreator-batch-1',
                'period_label' => 'Batch 1',
                'audience_type' => 'community',
                'participant_label' => 'Peserta Didik',
                'is_active' => true,
            ]),
        ];
    }
}
