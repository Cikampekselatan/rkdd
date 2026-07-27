<?php

namespace Tests\Feature;

use App\Enums\AttendanceSessionStatus;
use App\Enums\AttendanceStatus;
use App\Enums\LearningSessionStatus;
use App\Enums\RoleSlug;
use App\Enums\StudentMembershipStatus;
use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\ClassStudent;
use App\Models\Institution;
use App\Models\LearningModule;
use App\Models\LearningSession;
use App\Models\Program;
use App\Models\ProgramBatch;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class Phase11AttendanceMultiProgramTest extends TestCase
{
    use RefreshDatabase;

    public function test_attendance_matrix_uses_dynamic_sessions_from_active_program(): void
    {
        $teacher = User::factory()->withRole(RoleSlug::Teacher)->create();
        $year = AcademicYear::factory()->active()->create();
        [$shortBatch, $shortClass, $shortSessions] = $this->programWithSessions($year, 'Jurnalis Digital', 'jurnalis-phase-11', 3);
        [$longBatch] = $this->programWithSessions($year, 'Konten Kreator', 'creator-phase-11', 5);
        $student = User::factory()->withRole(RoleSlug::Student)->create(['name' => 'Nadia Matriks Dinamis']);
        ClassStudent::query()->create([
            'academic_year_id' => $year->id,
            'program_batch_id' => $shortBatch->id,
            'class_id' => $shortClass->id,
            'user_id' => $student->id,
            'joined_at' => now(),
            'status' => StudentMembershipStatus::Active->value,
        ]);
        $attendance = AttendanceSession::query()->create([
            'learning_session_id' => $shortSessions[2]->id,
            'academic_year_id' => $year->id,
            'program_batch_id' => $shortBatch->id,
            'class_id' => $shortClass->id,
            'attendance_date' => today(),
            'status' => AttendanceSessionStatus::Closed,
            'opened_by' => $teacher->id,
            'opened_at' => now(),
            'closed_by' => $teacher->id,
            'closed_at' => now(),
        ]);
        AttendanceRecord::query()->create([
            'attendance_session_id' => $attendance->id,
            'user_id' => $student->id,
            'status' => AttendanceStatus::Present,
            'recorded_by' => $teacher->id,
            'recorded_at' => now(),
        ]);

        $this->withSession(['active_program_batch_id' => $shortBatch->id])
            ->actingAs($teacher)
            ->get(route('reports.matrix', ['type' => 'attendance', 'year' => $year->id, 'class' => $shortClass->id]))
            ->assertOk()
            ->assertSee('colspan="3"', false)
            ->assertSee('Nadia Matriks Dinamis')
            ->assertSee('Pertemuan 3 · Pertemuan Jurnalis Digital 3')
            ->assertDontSee('Pertemuan Konten Kreator 4');

        $this->withSession(['active_program_batch_id' => $longBatch->id])
            ->actingAs($teacher)
            ->get(route('reports.matrix', ['type' => 'attendance', 'year' => $year->id]))
            ->assertOk()
            ->assertSee('colspan="5"', false)
            ->assertSee('Pertemuan Konten Kreator 5')
            ->assertDontSee('Pertemuan Jurnalis Digital 3');
    }

    public function test_attendance_session_cannot_mix_class_and_learning_session_from_different_programs(): void
    {
        $teacher = User::factory()->withRole(RoleSlug::Teacher)->create();
        $year = AcademicYear::factory()->active()->create();
        [$skuadBatch, $skuadClass] = $this->programWithSessions($year, 'SKUAD', 'skuad-attendance-phase-11', 2);
        [$creatorBatch, , $creatorSessions] = $this->programWithSessions($year, 'Konten Kreator', 'creator-attendance-phase-11', 2);
        $student = User::factory()->withRole(RoleSlug::Student)->create();
        ClassStudent::query()->create([
            'academic_year_id' => $year->id,
            'program_batch_id' => $skuadBatch->id,
            'class_id' => $skuadClass->id,
            'user_id' => $student->id,
            'joined_at' => now(),
            'status' => StudentMembershipStatus::Active->value,
        ]);

        $this->withSession(['active_program_batch_id' => $creatorBatch->id])
            ->actingAs($teacher)
            ->post(route('teacher.attendance.store'), [
                'learning_session_id' => $creatorSessions[0]->id,
                'class_id' => $skuadClass->id,
                'attendance_date' => today()->toDateString(),
            ])
            ->assertSessionHasErrors('class_id');
    }

    /**
     * @return array{ProgramBatch, SchoolClass, Collection<int, LearningSession>}
     */
    private function programWithSessions(AcademicYear $year, string $name, string $slug, int $sessionCount): array
    {
        $program = Program::query()->create([
            'name' => $name,
            'slug' => $slug,
            'type' => 'pelatihan',
            'primary_color' => '#0f766e',
            'secondary_color' => '#0f172a',
            'accent_color' => '#f59e0b',
            'is_active' => true,
        ]);
        $institution = Institution::query()->firstOrCreate(
            ['slug' => 'rkdd-phase-11'],
            ['name' => 'RKDD Cikampek Selatan', 'type' => 'rkdd', 'is_active' => true],
        );
        $batch = ProgramBatch::query()->create([
            'program_id' => $program->id,
            'institution_id' => $institution->id,
            'name' => $name.' 2026',
            'slug' => $slug.'-2026',
            'period_label' => '2026',
            'audience_type' => 'community',
            'participant_label' => 'Peserta',
            'is_active' => true,
        ]);
        $class = SchoolClass::factory()->create([
            'academic_year_id' => $year->id,
            'program_batch_id' => $batch->id,
            'name' => 'Kelompok '.$name,
        ]);
        $module = LearningModule::factory()->create([
            'academic_year_id' => $year->id,
            'program_batch_id' => $batch->id,
            'module_number' => 1,
            'slug' => str($slug)->slug().'-module',
            'title' => 'Modul '.$name,
        ]);
        $sessions = collect(range(1, $sessionCount))->map(fn (int $number) => LearningSession::factory()->create([
            'academic_year_id' => $year->id,
            'program_batch_id' => $batch->id,
            'learning_module_id' => $module->id,
            'session_number' => $number,
            'semester' => $number <= 15 ? 1 : 2,
            'title' => 'Pertemuan '.$name.' '.$number,
            'slug' => str('Pertemuan '.$name.' '.$number)->slug().'-'.$batch->id,
            'status' => LearningSessionStatus::Published,
            'published_at' => now(),
        ]));

        return [$batch, $class, $sessions];
    }
}
