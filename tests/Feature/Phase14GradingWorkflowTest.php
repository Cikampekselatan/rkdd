<?php

namespace Tests\Feature;

use App\Enums\RemedialStatus;
use App\Enums\RoleSlug;
use App\Enums\SubmissionStatus;
use App\Models\Assignment;
use App\Models\AssignmentQuestion;
use App\Models\Grade;
use App\Models\Rubric;
use App\Models\RubricCriterion;
use App\Models\Submission;
use App\Models\SubmissionVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase14GradingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_grading_pages_render_for_teacher_and_published_student(): void
    {
        $teacher = User::factory()->withRole(RoleSlug::Teacher)->create();
        $student = User::factory()->withRole(RoleSlug::Student)->create();
        $rubric = $this->createRubric();
        $assignment = Assignment::factory()->create(['rubric_id' => $rubric->id]);
        $submission = Submission::factory()->create(['assignment_id' => $assignment->id, 'user_id' => $student->id, 'status' => SubmissionStatus::UnderReview]);
        $version = SubmissionVersion::create(['submission_id' => $submission->id, 'version_number' => 1, 'text_content' => 'Karya untuk dinilai', 'submitted_at' => now()]);
        $question = AssignmentQuestion::query()->create(['assignment_id' => $assignment->id, 'sort_order' => 1, 'prompt' => 'Apa bukti proses terbaikmu?', 'answer_type' => 'paragraph', 'is_required' => true]);
        $version->answers()->create(['assignment_question_id' => $question->id, 'answer_text' => 'Bukti proses terbaik adalah storyboard.']);
        $this->actingAs($teacher)->get(route('teacher.grades.edit', $submission))->assertOk()->assertSee('Pemahaman')->assertSee('Bukti proses terbaik adalah storyboard.');
        $this->actingAs($teacher)->put(route('teacher.grades.update', $submission), $this->gradePayload($rubric, ['action' => 'publish']))->assertSessionHas('success');
        $this->actingAs($student)->get(route('student.grades.index'))->assertOk()->assertSee($assignment->title);
    }

    public function test_teacher_creates_reusable_rubric_and_weight_must_equal_one_hundred(): void
    {
        $teacher = User::factory()->withRole(RoleSlug::Teacher)->create();
        $bad = $this->rubricPayload();
        $bad['criteria'][1]['weight'] = 50;
        $this->actingAs($teacher)->post(route('teacher.rubrics.store'), $bad)->assertSessionHasErrors('criteria');
        $this->actingAs($teacher)->post(route('teacher.rubrics.store'), $this->rubricPayload())->assertRedirect();
        $rubric = Rubric::firstOrFail();
        $this->assertSame(2, $rubric->criteria()->count());
        $this->assertSame(8, $rubric->criteria()->withCount('levels')->get()->sum('levels_count'));
        $this->actingAs($teacher)->get(route('teacher.rubrics.show', $rubric))->assertOk()->assertSee('Pemahaman');
    }

    public function test_weighted_calculation_publish_visibility_private_note_and_audit(): void
    {
        [$teacher,$student,$other,$submission,$rubric] = $this->context();
        $payload = $this->gradePayload($rubric, ['levels' => [3, 4], 'action' => 'publish', 'feedback' => 'Karya kuat dan terstruktur.', 'private_note' => 'Perhatikan konsistensi proses.']);
        $this->actingAs($teacher)->put(route('teacher.grades.update', $submission), $payload)->assertSessionHas('success');
        $grade = Grade::firstOrFail();
        $this->assertSame('90.00', $grade->total_score);
        $this->assertSame(4, $grade->achievement_level);
        $this->assertTrue($grade->is_published);
        $this->assertSame(SubmissionStatus::Graded, $submission->fresh()->status);
        $this->assertDatabaseHas('grade_audits', ['grade_id' => $grade->id, 'event' => 'published']);
        $this->actingAs($student)->get(route('student.grades.show', $grade))->assertOk()->assertSee('Karya kuat')->assertSee('90.00')->assertDontSee('Perhatikan konsistensi proses');
        $this->actingAs($other)->get(route('student.grades.show', $grade))->assertForbidden();
    }

    public function test_draft_grade_is_hidden_and_grading_can_request_revision(): void
    {
        [$teacher,$student,,$submission,$rubric] = $this->context();
        $this->actingAs($teacher)->put(route('teacher.grades.update', $submission), $this->gradePayload($rubric, ['levels' => [2, 2], 'action' => 'draft']))->assertSessionHas('success');
        $grade = Grade::firstOrFail();
        $this->actingAs($student)->get(route('student.grades.show', $grade))->assertForbidden();
        $this->actingAs($teacher)->put(route('teacher.grades.update', $submission), $this->gradePayload($rubric, ['levels' => [2, 3], 'action' => 'revision', 'revision_note' => 'Perbaiki refleksi dan bukti proses.']))->assertSessionHas('success');
        $this->assertSame(SubmissionStatus::RevisionRequested, $submission->fresh()->status);
        $this->assertDatabaseHas('grade_audits', ['grade_id' => $grade->id, 'event' => 'revision_requested']);
    }

    public function test_remedial_workflow_student_submission_and_teacher_completion(): void
    {
        [$teacher,$student,,$submission,$rubric] = $this->context();
        $payload = $this->gradePayload($rubric, ['levels' => [1, 2], 'action' => 'publish', 'feedback' => 'Perlu penguatan.', 'remedial_status' => 'assigned', 'remedial_note' => 'Tulis ulang refleksi minimal 200 kata.', 'remedial_due_at' => now()->addWeek()->format('Y-m-d H:i:s')]);
        $this->actingAs($teacher)->put(route('teacher.grades.update', $submission), $payload)->assertSessionHas('success');
        $grade = Grade::firstOrFail();
        $this->assertSame(RemedialStatus::Assigned, $grade->remedial_status);
        $this->actingAs($student)->post(route('student.grades.remedial', $grade), ['remedial_response' => 'Saya memperbaiki refleksi dengan menjelaskan proses, kendala, dan solusi secara lengkap.'])->assertSessionHas('success');
        $this->assertSame(RemedialStatus::Submitted, $grade->fresh()->remedial_status);
        $this->actingAs($teacher)->patch(route('teacher.grades.remedial.complete', $grade))->assertSessionHas('success');
        $this->assertSame(RemedialStatus::Completed, $grade->fresh()->remedial_status);
        $this->assertDatabaseHas('grade_audits', ['grade_id' => $grade->id, 'event' => 'remedial_completed']);
    }

    public function test_score_requires_exact_rubric_criteria_and_used_rubric_structure_is_locked(): void
    {
        [$teacher,,,$submission,$rubric] = $this->context();
        $payload = $this->gradePayload($rubric, ['levels' => [4, 4], 'action' => 'draft']);
        array_pop($payload['scores']);
        $this->actingAs($teacher)->put(route('teacher.grades.update', $submission), $payload)->assertSessionHasErrors('scores');
        $this->actingAs($teacher)->put(route('teacher.grades.update', $submission), $this->gradePayload($rubric, ['levels' => [4, 4], 'action' => 'draft']))->assertSessionHas('success');
        $this->actingAs($teacher)->put(route('teacher.rubrics.update', $rubric), $this->rubricPayload())->assertSessionHasErrors('criteria');
    }

    private function context(): array
    {
        $teacher = User::factory()->withRole(RoleSlug::Teacher)->create();
        $student = User::factory()->withRole(RoleSlug::Student)->create();
        $other = User::factory()->withRole(RoleSlug::Student)->create();
        $rubric = $this->createRubric();
        $assignment = Assignment::factory()->create(['rubric_id' => $rubric->id]);
        $submission = Submission::factory()->create(['assignment_id' => $assignment->id, 'user_id' => $student->id, 'status' => SubmissionStatus::UnderReview]);
        SubmissionVersion::create(['submission_id' => $submission->id, 'version_number' => 1, 'text_content' => 'Karya final siswa', 'submitted_at' => now()]);

        return [$teacher, $student, $other, $submission, $rubric];
    }

    private function createRubric(): Rubric
    {
        $rubric = Rubric::factory()->create();
        foreach ([['Pemahaman', 40], ['Kreativitas', 60]] as $i => [$name,$weight]) {
            $criterion = RubricCriterion::create(['rubric_id' => $rubric->id, 'name' => $name, 'weight' => $weight, 'sort_order' => $i + 1]);
            foreach ([1 => 'Perlu Pendampingan', 2 => 'Berkembang', 3 => 'Terampil', 4 => 'Kreator Mandiri'] as $level => $label) {
                $criterion->levels()->create(['level' => $level, 'label' => $label, 'description' => $label.' pada '.$name]);
            }
        }

        return $rubric->refresh()->load('criteria.levels');
    }

    private function gradePayload(Rubric $rubric, array $overrides = []): array
    {
        $levels = $overrides['levels'] ?? [3, 4];
        unset($overrides['levels']);

        return array_merge(['scores' => $rubric->criteria->values()->map(fn ($c, $i) => ['criterion_id' => $c->id, 'level' => $levels[$i], 'teacher_note' => 'Catatan '.$c->name])->all(), 'feedback' => 'Feedback siswa', 'private_note' => 'Catatan privat', 'action' => 'draft', 'revision_note' => '', 'remedial_status' => 'none', 'remedial_note' => '', 'remedial_due_at' => ''], $overrides);
    }

    private function rubricPayload(): array
    {
        return ['academic_year_id' => '', 'name' => 'Rubrik Proyek Digital', 'description' => 'Rubrik reusable', 'is_active' => 1, 'criteria' => [['name' => 'Pemahaman', 'description' => 'Pemahaman konsep', 'weight' => 40, 'levels' => ['Belum memahami', 'Mulai memahami', 'Memahami', 'Sangat memahami']], ['name' => 'Kreativitas', 'description' => 'Kreativitas karya', 'weight' => 60, 'levels' => ['Meniru', 'Mulai berkembang', 'Orisinal', 'Sangat orisinal']]]];
    }
}
