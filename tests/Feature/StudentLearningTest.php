<?php

namespace Tests\Feature;

use App\Enums\LearningSessionStatus;
use App\Enums\RoleSlug;
use App\Enums\StudentMembershipStatus;
use App\Enums\UserStatus;
use App\Models\AcademicYear;
use App\Models\LearningMaterial;
use App\Models\LearningModule;
use App\Models\LearningSession;
use App\Models\SchoolClass;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentLearningTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_only_sees_published_content_from_their_academic_year(): void
    {
        $year = AcademicYear::factory()->active()->create();
        $otherYear = AcademicYear::factory()->create();
        $student = $this->studentFor($year);
        $published = $this->sessionFor($year, 1, LearningSessionStatus::Published, 'Materi Terbuka');
        $draft = $this->sessionFor($year, 2, LearningSessionStatus::Draft, 'Materi Rahasia Draf');
        $other = $this->sessionFor($otherYear, 1, LearningSessionStatus::Published, 'Materi Tahun Lain');

        $this->actingAs($student)
            ->get(route('student.learning.index'))
            ->assertOk()
            ->assertSee('Materi Terbuka')
            ->assertDontSee('Materi Rahasia Draf')
            ->assertDontSee('Materi Tahun Lain');

        $this->actingAs($student)->get(route('student.learning.show', $published))->assertOk();
        $this->actingAs($student)->get(route('student.learning.show', $draft))->assertForbidden();
        $this->actingAs($student)->get(route('student.learning.show', $other))->assertForbidden();
    }

    public function test_opening_and_completing_components_persists_progress_for_current_student_only(): void
    {
        $year = AcademicYear::factory()->active()->create();
        $student = $this->studentFor($year);
        $otherStudent = $this->studentFor($year);
        $session = $this->sessionFor($year, 1, LearningSessionStatus::Published, 'Belajar Aman');

        $this->actingAs($student)
            ->get(route('student.learning.show', $session))
            ->assertOk()
            ->assertSee('Progress pertemuan');

        $this->assertDatabaseHas('student_learning_progress', [
            'user_id' => $student->id,
            'learning_session_id' => $session->id,
            'progress_percent' => 25,
        ]);

        foreach (['materials' => 50, 'exercise' => 75, 'reflection' => 100] as $component => $percent) {
            $this->actingAs($student)->post(route('student.learning.progress', $session), [
                'component' => $component,
            ])->assertSessionHas('success');

            $this->assertDatabaseHas('student_learning_progress', [
                'user_id' => $student->id,
                'learning_session_id' => $session->id,
                'progress_percent' => $percent,
            ]);
        }

        $this->assertDatabaseMissing('student_learning_progress', [
            'user_id' => $otherStudent->id,
            'learning_session_id' => $session->id,
        ]);
        $this->assertNotNull($student->learningProgress()->firstOrFail()->completed_at);
    }

    public function test_progress_component_requires_corresponding_content_and_suspended_student_is_denied(): void
    {
        $year = AcademicYear::factory()->create();
        $student = $this->studentFor($year);
        $session = $this->sessionFor($year, 1, LearningSessionStatus::Published, 'Materi Ringkas', false);

        $this->actingAs($student)->post(route('student.learning.progress', $session), [
            'component' => 'exercise',
        ])->assertSessionHasErrors('component');

        $student->update(['status' => UserStatus::Suspended]);
        $this->actingAs($student)->get(route('student.learning.index'))->assertForbidden();
        $this->actingAs($student)->get(route('student.learning.show', $session))->assertForbidden();
    }

    private function studentFor(AcademicYear $academicYear): User
    {
        $class = SchoolClass::factory()->create(['academic_year_id' => $academicYear->id]);
        $student = User::factory()->withRole(RoleSlug::Student)->create([
            'status' => UserStatus::Active,
            'password' => null,
        ]);
        StudentProfile::factory()->create([
            'user_id' => $student->id,
            'class_id' => $class->id,
            'membership_status' => StudentMembershipStatus::Active,
        ]);

        return $student->refresh();
    }

    private function sessionFor(
        AcademicYear $academicYear,
        int $number,
        LearningSessionStatus $status,
        string $title,
        bool $withActivities = true,
    ): LearningSession {
        $module = LearningModule::factory()->create([
            'academic_year_id' => $academicYear->id,
            'module_number' => $number,
            'slug' => "module-{$number}",
        ]);
        $session = LearningSession::factory()->create([
            'academic_year_id' => $academicYear->id,
            'learning_module_id' => $module->id,
            'session_number' => $number,
            'semester' => $number <= 15 ? 1 : 2,
            'title' => $title,
            'slug' => "session-{$number}",
            'status' => $status,
            'published_at' => $status->isVisibleToStudents() ? now() : null,
            'practice_instructions' => $withActivities ? 'Kerjakan latihan.' : null,
            'reflection_prompt' => $withActivities ? 'Apa yang kamu pelajari?' : null,
        ]);
        LearningMaterial::factory()->create(['learning_session_id' => $session->id]);

        return $session;
    }
}
