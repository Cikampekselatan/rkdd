<?php

namespace Tests\Feature;

use App\Enums\LearningSessionStatus;
use App\Enums\RoleSlug;
use App\Enums\StudentMembershipStatus;
use App\Enums\UserStatus;
use App\Models\AcademicYear;
use App\Models\ClassStudent;
use App\Models\Institution;
use App\Models\LearningModule;
use App\Models\LearningSession;
use App\Models\Program;
use App\Models\ProgramBatch;
use App\Models\RegistrationCode;
use App\Models\SchoolClass;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase9ProgramDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_dashboard_follows_selected_active_program(): void
    {
        [$skuadBatch, $skuadClass] = $this->programContext('SKUAD', 'skuad-phase-9', 'Siswa', 'Pertemuan SKUAD Lama');
        [$creatorBatch, $creatorClass] = $this->programContext('Konten Kreator', 'konten-kreator-phase-9', 'Peserta Didik', 'Pertemuan Creator Aktif');
        $student = User::factory()->withRole(RoleSlug::Student)->create([
            'name' => 'Nadia Dashboard Program',
            'status' => UserStatus::Active,
            'password' => null,
        ]);

        StudentProfile::factory()->create([
            'user_id' => $student->id,
            'program_batch_id' => $skuadBatch->id,
            'class_id' => $skuadClass->id,
            'membership_status' => StudentMembershipStatus::Active,
        ]);
        foreach ([[$skuadBatch, $skuadClass], [$creatorBatch, $creatorClass]] as [$batch, $class]) {
            ClassStudent::query()->create([
                'academic_year_id' => $class->academic_year_id,
                'program_batch_id' => $batch->id,
                'class_id' => $class->id,
                'user_id' => $student->id,
                'joined_at' => now(),
                'status' => StudentMembershipStatus::Active->value,
            ]);
        }

        $this->withSession(['active_program_batch_id' => $creatorBatch->id])
            ->actingAs($student)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('Konten Kreator')
            ->assertSee('Dashboard pribadi peserta didik')
            ->assertSee('Kelompok Konten Kreator')
            ->assertSee('Pertemuan Creator Aktif')
            ->assertDontSee('Pertemuan SKUAD Lama');
    }

    public function test_super_admin_dashboard_displays_cross_program_operational_summary(): void
    {
        $superAdmin = User::factory()->withRole(RoleSlug::SuperAdmin)->create();
        [$skuadBatch, $skuadClass] = $this->programContext('SKUAD', 'skuad-summary', 'Siswa', 'Pertemuan SKUAD Summary');
        [$creatorBatch, $creatorClass] = $this->programContext('Konten Kreator', 'konten-kreator-summary', 'Peserta', 'Pertemuan Creator Summary');
        $student = User::factory()->withRole(RoleSlug::Student)->create(['status' => UserStatus::Active]);
        ClassStudent::query()->create([
            'academic_year_id' => $creatorClass->academic_year_id,
            'program_batch_id' => $creatorBatch->id,
            'class_id' => $creatorClass->id,
            'user_id' => $student->id,
            'joined_at' => now(),
            'status' => StudentMembershipStatus::Active->value,
        ]);
        RegistrationCode::factory()->forPlainText('CREATOR-SUMMARY')->create([
            'academic_year_id' => $creatorClass->academic_year_id,
            'program_batch_id' => $creatorBatch->id,
            'class_id' => $creatorClass->id,
            'max_uses' => 10,
            'used_count' => 0,
        ]);
        RegistrationCode::factory()->forPlainText('SKUAD-SUMMARY')->create([
            'academic_year_id' => $skuadClass->academic_year_id,
            'program_batch_id' => $skuadBatch->id,
            'class_id' => $skuadClass->id,
            'max_uses' => 1,
            'used_count' => 1,
        ]);

        $this->actingAs($superAdmin)
            ->get(route('super-admin.dashboard'))
            ->assertOk()
            ->assertSee('Operasional per program')
            ->assertSee('Konten Kreator')
            ->assertSee('1 peserta · 1 kelompok · 1 pertemuan · 1 kode aktif')
            ->assertSee('SKUAD')
            ->assertSee('0 peserta · 1 kelompok · 1 pertemuan · 0 kode aktif');
    }

    /**
     * @return array{ProgramBatch, SchoolClass}
     */
    private function programContext(string $name, string $slug, string $participantLabel, string $sessionTitle): array
    {
        $program = Program::query()->create([
            'name' => $name,
            'slug' => $slug,
            'type' => 'pelatihan',
            'primary_color' => $name === 'Konten Kreator' ? '#7c3aed' : '#0f766e',
            'secondary_color' => '#0f172a',
            'accent_color' => '#f59e0b',
            'is_active' => true,
        ]);
        $institution = Institution::query()->firstOrCreate(
            ['slug' => 'rkdd-cikampek-selatan-phase-9'],
            ['name' => 'RKDD Cikampek Selatan', 'type' => 'rkdd', 'is_active' => true],
        );
        $batch = ProgramBatch::query()->create([
            'program_id' => $program->id,
            'institution_id' => $institution->id,
            'name' => $name.' 2026',
            'slug' => $slug.'-2026',
            'period_label' => '2026',
            'audience_type' => $participantLabel === 'Siswa' ? 'school' : 'community',
            'participant_label' => $participantLabel,
            'is_active' => true,
        ]);
        $year = AcademicYear::factory()->active()->create(['name' => $name.' 2026']);
        $class = SchoolClass::factory()->create([
            'academic_year_id' => $year->id,
            'program_batch_id' => $batch->id,
            'name' => 'Kelompok '.$name,
        ]);
        $module = LearningModule::factory()->create([
            'academic_year_id' => $year->id,
            'program_batch_id' => $batch->id,
            'title' => 'Modul '.$name,
        ]);
        LearningSession::factory()->create([
            'learning_module_id' => $module->id,
            'academic_year_id' => $year->id,
            'program_batch_id' => $batch->id,
            'session_number' => 1,
            'title' => $sessionTitle,
            'slug' => str($sessionTitle)->slug().'-'.$batch->id,
            'status' => LearningSessionStatus::Published,
            'published_at' => now(),
        ]);

        return [$batch, $class];
    }
}
