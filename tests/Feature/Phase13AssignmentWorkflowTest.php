<?php

namespace Tests\Feature;

use App\Enums\LearningSessionStatus;
use App\Enums\RoleSlug;
use App\Enums\StudentMembershipStatus;
use App\Enums\SubmissionStatus;
use App\Models\AcademicYear;
use App\Models\Assignment;
use App\Models\ClassStudent;
use App\Models\LearningModule;
use App\Models\LearningSession;
use App\Models\SchoolClass;
use App\Models\Submission;
use App\Models\SubmissionFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Phase13AssignmentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_creates_published_assignment_and_student_only_sees_own_class(): void
    {
        [$teacher,$student,$year,$class,$session] = $this->context();
        $this->actingAs($teacher)->post(route('teacher.assignments.store'), array_merge($this->assignmentPayload($year, $class, $session), ['available_from' => '']))->assertRedirect();
        $assignment = Assignment::firstOrFail();
        $this->assertTrue($assignment->is_published);
        $this->assertSame(['application/pdf', 'image/png'], $assignment->allowed_mime_types);
        $this->actingAs($student)->get(route('student.assignments.index'))->assertOk()->assertSee('Proyek Etika Digital');

        [, $outsider] = $this->context('outsider@example.test');
        $this->actingAs($outsider)->get(route('student.assignments.show', $assignment))->assertForbidden();
    }

    public function test_student_draft_upload_submit_review_revision_and_resubmit_creates_versions(): void
    {
        Storage::fake('local');
        [$teacher,$student,$year,$class,$session] = $this->context();
        $assignment = Assignment::factory()->create(['academic_year_id' => $year->id, 'class_id' => $class->id, 'learning_session_id' => $session->id, 'type' => 'mixed', 'max_revisions' => 1, 'allowed_mime_types' => ['application/pdf']]);

        $this->actingAs($student)->post(route('student.assignments.save', $assignment), ['text_content' => 'Draf awal', 'files' => [UploadedFile::fake()->create('karya.pdf', 100, 'application/pdf')], 'action' => 'draft'])->assertSessionHas('success');
        $submission = Submission::firstOrFail();
        $file = SubmissionFile::firstOrFail();
        $this->assertSame(SubmissionStatus::Draft, $submission->status);
        $this->assertStringNotContainsString('karya.pdf', $file->stored_path);
        Storage::disk('local')->assertExists($file->stored_path);
        $this->actingAs($student)->get(route('submission-files.download', $file))->assertOk();

        $this->actingAs($student)->post(route('student.assignments.save', $assignment), ['text_content' => 'Versi pertama', 'action' => 'submit'])->assertSessionHas('success');
        $this->assertSame(SubmissionStatus::Submitted, $submission->fresh()->status);
        $this->actingAs($teacher)->patch(route('teacher.submissions.review', $submission))->assertSessionHas('success');
        $this->actingAs($teacher)->patch(route('teacher.submissions.revision', $submission), ['revision_note' => 'Tambahkan refleksi proses.'])->assertSessionHas('success');
        $this->assertSame(SubmissionStatus::RevisionRequested, $submission->fresh()->status);

        $this->actingAs($student)->post(route('student.assignments.save', $assignment), ['text_content' => 'Versi revisi dengan refleksi', 'action' => 'submit'])->assertSessionHas('success');
        $submission->refresh()->load('versions');
        $this->assertSame(SubmissionStatus::Resubmitted, $submission->status);
        $this->assertSame(2, $submission->versions->count());
        $this->assertSame('Versi pertama', $submission->versions[0]->text_content);
        $this->assertSame('Versi revisi dengan refleksi', $submission->versions[1]->text_content);
        $this->assertSame(1, $submission->revision_count);
    }

    public function test_teacher_assignment_questions_are_answered_per_student_submission_version(): void
    {
        [$teacher,$student,$year,$class,$session] = $this->context();

        $this->actingAs($teacher)->post(route('teacher.assignments.store'), array_merge($this->assignmentPayload($year, $class, $session), [
            'title' => 'Refleksi Proyek Bertanya',
            'type' => 'mixed',
            'questions' => [
                ['prompt' => 'Apa ide utama proyekmu?', 'help_text' => 'Jawab 2-3 kalimat.', 'answer_type' => 'paragraph', 'is_required' => 1],
                ['prompt' => 'Tempel link bukti karya.', 'help_text' => '', 'answer_type' => 'url', 'is_required' => 1],
                ['prompt' => 'Apa peranmu?', 'help_text' => '', 'answer_type' => 'short_text', 'is_required' => 0],
                ['prompt' => 'Seberapa siap proyekmu dipresentasikan?', 'help_text' => '', 'answer_type' => 'multiple_choice', 'options_text' => "Sangat siap\nCukup siap\nBelum siap", 'is_required' => 1],
            ],
        ]))->assertRedirect();

        $assignment = Assignment::query()->with('questions')->firstOrFail();
        $this->assertSame(4, $assignment->questions->count());

        $this->actingAs($student)->get(route('student.assignments.show', $assignment))
            ->assertOk()
            ->assertSee('Apa ide utama proyekmu?')
            ->assertSee('Tempel link bukti karya.')
            ->assertSee('Sangat siap');

        $this->actingAs($student)->post(route('student.assignments.save', $assignment), [
            'answers' => [
                ['question_id' => $assignment->questions[0]->id, 'answer_text' => 'Ide utama proyek adalah kampanye aman digital.'],
            ],
            'action' => 'submit',
        ])->assertSessionHasErrors('answers');

        $this->actingAs($student)->post(route('student.assignments.save', $assignment), [
            'answers' => [
                ['question_id' => $assignment->questions[0]->id, 'answer_text' => 'Ide utama proyek adalah kampanye aman digital.'],
                ['question_id' => $assignment->questions[1]->id, 'answer_url' => 'https://drive.google.com/project-answer'],
                ['question_id' => $assignment->questions[3]->id, 'answer_text' => 'Pilihan palsu'],
            ],
            'action' => 'submit',
        ])->assertSessionHasErrors('answers');

        $this->actingAs($student)->post(route('student.assignments.save', $assignment), [
            'answers' => [
                ['question_id' => $assignment->questions[0]->id, 'answer_text' => 'Ide utama proyek adalah kampanye aman digital.'],
                ['question_id' => $assignment->questions[1]->id, 'answer_url' => 'https://drive.google.com/project-answer'],
                ['question_id' => $assignment->questions[2]->id, 'answer_text' => 'Editor video'],
                ['question_id' => $assignment->questions[3]->id, 'answer_text' => 'Cukup siap'],
            ],
            'text_content' => 'Catatan tambahan siswa.',
            'action' => 'submit',
        ])->assertSessionHas('success');

        $submission = $assignment->submissions()->with('versions.answers.question')->firstOrFail();
        $this->assertSame(4, $submission->versions->first()->answers->count());

        $this->actingAs($teacher)->get(route('teacher.submissions.show', $submission))
            ->assertOk()
            ->assertSee('Apa ide utama proyekmu?')
            ->assertSee('Ide utama proyek adalah kampanye aman digital.')
            ->assertSee('https://drive.google.com/project-answer')
            ->assertSee('Cukup siap');
    }

    public function test_private_file_policy_mime_size_count_and_orphan_cleanup_are_enforced(): void
    {
        Storage::fake('local');
        [$teacher,$student,$year,$class,$session] = $this->context();
        $other = User::factory()->withRole(RoleSlug::Student)->create();
        $assignment = Assignment::factory()->create(['academic_year_id' => $year->id, 'class_id' => $class->id, 'learning_session_id' => $session->id, 'type' => 'document', 'max_files' => 1, 'max_file_size_kb' => 200, 'allowed_mime_types' => ['application/pdf']]);
        $this->actingAs($student)->post(route('student.assignments.save', $assignment), ['files' => [UploadedFile::fake()->image('wrong.png')], 'action' => 'draft'])->assertSessionHasErrors('files');
        $this->actingAs($student)->post(route('student.assignments.save', $assignment), ['files' => [UploadedFile::fake()->create('a.pdf', 300, 'application/pdf')], 'action' => 'draft'])->assertSessionHasErrors('files.0');
        $this->actingAs($student)->post(route('student.assignments.save', $assignment), ['files' => [UploadedFile::fake()->create('a.pdf', 50, 'application/pdf')], 'action' => 'submit'])->assertSessionHas('success');
        $file = SubmissionFile::firstOrFail();
        $this->actingAs($other)->get(route('submission-files.download', $file))->assertForbidden();
        $this->actingAs($teacher)->get(route('submission-files.download', $file))->assertOk();
        Storage::disk('local')->put('submissions/orphan.bin', 'orphan');
        $this->assertSame(0, Artisan::call('submissions:cleanup-orphans'));
        Storage::disk('local')->assertMissing('submissions/orphan.bin');
        Storage::disk('local')->assertExists($file->stored_path);
    }

    public function test_deadline_late_rules_content_requirements_and_locked_submission_are_enforced(): void
    {
        [$teacher,$student,$year,$class,$session] = $this->context();
        $closed = Assignment::factory()->create(['academic_year_id' => $year->id, 'class_id' => $class->id, 'learning_session_id' => $session->id, 'type' => 'text', 'due_at' => now()->subMinute(), 'allow_late' => false]);
        $this->actingAs($student)->post(route('student.assignments.save', $closed), ['text_content' => 'Jawaban', 'action' => 'submit'])->assertSessionHasErrors('submission');
        $late = Assignment::factory()->create(['academic_year_id' => $year->id, 'class_id' => $class->id, 'learning_session_id' => $session->id, 'type' => 'text', 'due_at' => now()->subMinute(), 'allow_late' => true]);
        $this->actingAs($student)->post(route('student.assignments.save', $late), ['text_content' => '', 'action' => 'submit'])->assertSessionHasErrors('submission');
        $this->actingAs($student)->post(route('student.assignments.save', $late), ['text_content' => 'Jawaban terlambat', 'action' => 'submit'])->assertSessionHas('success');
        $submission = Submission::where('assignment_id', $late->id)->firstOrFail();
        $this->assertSame(SubmissionStatus::Late, $submission->status);
        $this->actingAs($student)->post(route('student.assignments.save', $late), ['text_content' => 'Ubah diam-diam', 'action' => 'draft'])->assertSessionHasErrors('submission');
    }

    public function test_assignment_validation_and_delete_protection_render_teacher_and_student_pages(): void
    {
        [$teacher,$student,$year,$class,$session] = $this->context();
        $otherYear = AcademicYear::factory()->create();
        $otherClass = SchoolClass::factory()->create(['academic_year_id' => $otherYear->id]);
        $this->actingAs($teacher)->post(route('teacher.assignments.store'), $this->assignmentPayload($year, $otherClass, $session))->assertSessionHasErrors('academic_year_id');
        $assignment = Assignment::factory()->create(['academic_year_id' => $year->id, 'class_id' => $class->id, 'learning_session_id' => $session->id]);
        $this->actingAs($teacher)->get(route('teacher.assignments.show', $assignment))->assertOk()->assertSee($assignment->title);
        $this->actingAs($student)->get(route('student.assignments.show', $assignment))->assertOk()->assertSee('Simpan draf');
        Submission::factory()->create(['assignment_id' => $assignment->id, 'user_id' => $student->id]);
        $this->actingAs($teacher)->delete(route('teacher.assignments.destroy', $assignment))->assertForbidden();
    }

    private function context(?string $email = null): array
    {
        $teacher = User::factory()->withRole(RoleSlug::Teacher)->create();
        $year = AcademicYear::factory()->create();
        $class = SchoolClass::factory()->create(['academic_year_id' => $year->id]);
        $module = LearningModule::factory()->create(['academic_year_id' => $year->id]);
        $session = LearningSession::factory()->create(['academic_year_id' => $year->id, 'learning_module_id' => $module->id, 'status' => LearningSessionStatus::Published]);
        $student = User::factory()->withRole(RoleSlug::Student)->create(['email' => $email ?? fake()->unique()->safeEmail()]);
        ClassStudent::create(['academic_year_id' => $year->id, 'class_id' => $class->id, 'user_id' => $student->id, 'joined_at' => now(), 'status' => StudentMembershipStatus::Active]);

        return [$teacher, $student, $year, $class, $session];
    }

    private function assignmentPayload(AcademicYear $year, SchoolClass $class, LearningSession $session): array
    {
        return ['academic_year_id' => $year->id, 'class_id' => $class->id, 'learning_session_id' => $session->id, 'title' => 'Proyek Etika Digital', 'instructions' => 'Buat karya tentang keamanan akun.', 'type' => 'mixed', 'available_from' => now()->subHour()->format('Y-m-d H:i:s'), 'due_at' => now()->addWeek()->format('Y-m-d H:i:s'), 'allow_late' => 1, 'max_files' => 3, 'max_file_size_kb' => 5120, 'allowed_mime_types_text' => 'application/pdf, image/png', 'max_revisions' => 2, 'is_published' => 1];
    }
}
