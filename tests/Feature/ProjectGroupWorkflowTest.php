<?php

namespace Tests\Feature;

use App\Enums\RoleSlug;
use App\Enums\StudentMembershipStatus;
use App\Models\AcademicYear;
use App\Models\ClassStudent;
use App\Models\GroupProject;
use App\Models\GroupProjectAssessment;
use App\Models\ProjectGroup;
use App\Models\SchoolClass;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectGroupWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_coach_creates_group_project_and_published_grade_reaches_each_member(): void
    {
        [$coach, $year, $class, $students, $outsider] = $this->context();

        $this->actingAs($coach)->get(route('teacher.project-groups.index'))
            ->assertOk()
            ->assertSee('Kelompok proyek program');

        $this->actingAs($coach)->get(route('teacher.project-groups.create'))
            ->assertOk()
            ->assertSee('Kelompok peserta asal')
            ->assertDontSee('Pilih kelompok SKUAD');

        $this->actingAs($coach)->post(route('teacher.project-groups.store'), [
            'academic_year_id' => $year->id,
            'class_id' => $class->id,
            'name' => 'Tim Garuda Digital',
            'description' => 'Membuat video dokumenter kegiatan SKUAD.',
            'status' => 'active',
            'member_ids' => $students->pluck('id')->all(),
        ])->assertRedirect();

        $group = ProjectGroup::query()->firstOrFail();
        $this->assertSame(2, $group->activeMembers()->count());

        $this->actingAs($coach)->post(route('teacher.project-groups.projects.store', $group), [
            'title' => 'Video Profil SKUAD',
            'description' => 'Buat video profil singkat dengan pembagian peran yang jelas.',
            'evidence_url' => 'https://drive.google.com/example-group',
            'due_at' => now()->addWeek()->format('Y-m-d H:i:s'),
            'status' => 'active',
            'is_published' => 1,
        ])->assertSessionHas('success');

        $project = GroupProject::query()->firstOrFail();

        $this->actingAs($coach)->put(route('teacher.group-projects.assessment.update', $project), [
            'final_score' => 88,
            'feedback' => 'Kolaborasi kuat dan hasil video rapi.',
            'private_note' => 'Catatan internal coach.',
            'is_published' => 1,
        ])->assertRedirect(route('teacher.project-groups.show', $group));

        $assessment = GroupProjectAssessment::query()->firstOrFail();
        $this->assertTrue($assessment->is_published);
        $this->assertSame(3, $assessment->achievement_level);

        foreach ($students as $student) {
            $this->actingAs($student)->get(route('student.project-groups.index'))
                ->assertOk()
                ->assertSee('Tim Garuda Digital');

            $this->actingAs($student)->get(route('student.grades.index'))
                ->assertOk()
                ->assertSee('Nilai proyek kelompok')
                ->assertSee('Video Profil SKUAD')
                ->assertSee('Kolaborasi kuat dan hasil video rapi.');

            $this->actingAs($student)->get(route('student.grades.group-projects.show', $assessment))
                ->assertOk()
                ->assertSee('Video Profil SKUAD')
                ->assertSee('88.00')
                ->assertDontSee('Catatan internal coach.');
        }

        $this->actingAs($outsider)->get(route('student.project-groups.show', $group))->assertForbidden();
        $this->actingAs($outsider)->get(route('student.grades.group-projects.show', $assessment))->assertForbidden();
    }

    public function test_group_members_must_be_active_students_in_selected_program_group(): void
    {
        [$teacher, $year, $class, $students] = $this->context(RoleSlug::Teacher);
        $otherClass = SchoolClass::factory()->create(['academic_year_id' => $year->id]);
        $otherStudent = User::factory()->withRole(RoleSlug::Student)->create();
        StudentProfile::factory()->create(['user_id' => $otherStudent->id, 'class_id' => $otherClass->id, 'membership_status' => StudentMembershipStatus::Active]);
        ClassStudent::query()->create([
            'academic_year_id' => $year->id,
            'class_id' => $otherClass->id,
            'user_id' => $otherStudent->id,
            'joined_at' => now(),
            'status' => StudentMembershipStatus::Active,
        ]);

        $this->actingAs($teacher)->post(route('teacher.project-groups.store'), [
            'academic_year_id' => $year->id,
            'class_id' => $class->id,
            'name' => 'Tim Salah Kelas',
            'description' => null,
            'status' => 'active',
            'member_ids' => [$students->first()->id, $otherStudent->id],
        ])->assertSessionHasErrors('member_ids');
    }

    private function context(RoleSlug $staffRole = RoleSlug::Coach): array
    {
        $staff = User::factory()->withRole($staffRole)->create();
        $year = AcademicYear::factory()->active()->create(['name' => '2026/2027', 'starts_on' => '2026-07-01', 'ends_on' => '2027-06-30']);
        $class = SchoolClass::factory()->create(['academic_year_id' => $year->id, 'name' => 'SKUAD 2026']);
        $students = User::factory()->count(2)->withRole(RoleSlug::Student)->create();

        foreach ($students as $student) {
            StudentProfile::factory()->create(['user_id' => $student->id, 'class_id' => $class->id, 'membership_status' => StudentMembershipStatus::Active]);
            ClassStudent::query()->create([
                'academic_year_id' => $year->id,
                'class_id' => $class->id,
                'user_id' => $student->id,
                'joined_at' => now(),
                'status' => StudentMembershipStatus::Active,
            ]);
        }

        $outsider = User::factory()->withRole(RoleSlug::Student)->create();

        return [$staff, $year, $class, $students, $outsider];
    }
}
