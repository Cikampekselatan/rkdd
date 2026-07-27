<?php

namespace Tests\Feature;

use App\Enums\AnnouncementAudience;
use App\Enums\AnnouncementPriority;
use App\Enums\RoleSlug;
use App\Enums\StudentMembershipStatus;
use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\ClassStudent;
use App\Models\DiscussionTopic;
use App\Models\Institution;
use App\Models\LearningModule;
use App\Models\LearningSession;
use App\Models\Program;
use App\Models\ProgramBatch;
use App\Models\SchoolClass;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase13CommunicationProgramIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_announcements_are_scoped_to_active_program_for_lists_direct_access_and_notifications(): void
    {
        $teacher = User::factory()->withRole(RoleSlug::Teacher)->create();
        $year = AcademicYear::factory()->active()->create();
        [$skuadBatch, $skuadClass] = $this->programContext($year, 'SKUAD', 'skuad-phase-13');
        [$creatorBatch, $creatorClass] = $this->programContext($year, 'Konten Kreator', 'creator-phase-13');
        $skuadStudent = User::factory()->withRole(RoleSlug::Student)->create(['name' => 'Siswa SKUAD']);
        $creatorStudent = User::factory()->withRole(RoleSlug::Student)->create(['name' => 'Peserta Creator']);
        $this->join($skuadStudent, $skuadBatch, $skuadClass);
        $this->join($creatorStudent, $creatorBatch, $creatorClass);

        $this->withSession(['active_program_batch_id' => $skuadBatch->id])
            ->actingAs($teacher)
            ->post(route('teacher.announcements.store'), $this->announcementPayload($year, $skuadClass, ['action' => 'publish']))
            ->assertRedirect();

        $announcement = Announcement::query()->firstOrFail();
        $this->assertSame($skuadBatch->id, $announcement->program_batch_id);
        $this->assertTrue($skuadStudent->notifications()->where('data->program_batch_id', $skuadBatch->id)->exists());
        $this->assertFalse($creatorStudent->notifications()->where('data->announcement_id', $announcement->id)->exists());

        $this->withSession(['active_program_batch_id' => $creatorBatch->id])
            ->actingAs($teacher)
            ->get(route('interactions.announcements.show', $announcement))
            ->assertForbidden();

        $this->withSession(['active_program_batch_id' => $creatorBatch->id])
            ->actingAs($creatorStudent)
            ->get(route('interactions.announcements.index'))
            ->assertOk()
            ->assertDontSee($announcement->title);

        $this->withSession(['active_program_batch_id' => $creatorBatch->id])
            ->actingAs($teacher)
            ->post(route('teacher.announcements.store'), $this->announcementPayload($year, $skuadClass, ['action' => 'publish']))
            ->assertSessionHasErrors('class_id');
    }

    public function test_discussions_are_scoped_to_active_program_and_student_topics_use_active_membership(): void
    {
        $teacher = User::factory()->withRole(RoleSlug::Teacher)->create();
        $year = AcademicYear::factory()->active()->create();
        [$skuadBatch, $skuadClass] = $this->programContext($year, 'SKUAD', 'skuad-discussion-phase-13');
        [$creatorBatch, $creatorClass] = $this->programContext($year, 'Konten Kreator', 'creator-discussion-phase-13');
        $student = User::factory()->withRole(RoleSlug::Student)->create(['name' => 'Nadia Multi Program']);
        $this->join($student, $skuadBatch, $skuadClass);
        $this->join($student, $creatorBatch, $creatorClass);

        $skuadTopic = DiscussionTopic::query()->create([
            'academic_year_id' => $year->id,
            'program_batch_id' => $skuadBatch->id,
            'class_id' => $skuadClass->id,
            'created_by' => $teacher->id,
            'title' => 'Forum SKUAD tertutup program',
            'body' => 'Topik ini hanya untuk peserta program SKUAD.',
        ]);

        $this->withSession(['active_program_batch_id' => $creatorBatch->id])
            ->actingAs($teacher)
            ->get(route('interactions.discussions.show', $skuadTopic))
            ->assertForbidden();

        $this->withSession(['active_program_batch_id' => $creatorBatch->id])
            ->actingAs($student)
            ->post(route('interactions.discussions.store'), [
                'title' => 'Diskusi aktif konten kreator',
                'body' => 'Saya ingin bertanya tentang alur produksi konten untuk minggu ini.',
            ])
            ->assertRedirect();

        $createdTopic = DiscussionTopic::query()->where('title', 'Diskusi aktif konten kreator')->firstOrFail();
        $this->assertSame($creatorBatch->id, $createdTopic->program_batch_id);
        $this->assertSame($creatorClass->id, $createdTopic->class_id);

        $this->withSession(['active_program_batch_id' => $creatorBatch->id])
            ->actingAs($student)
            ->get(route('interactions.discussions.index'))
            ->assertOk()
            ->assertSee($createdTopic->title)
            ->assertDontSee($skuadTopic->title);

        $this->withSession(['active_program_batch_id' => $creatorBatch->id])
            ->actingAs($teacher)
            ->post(route('interactions.discussions.store'), [
                'class_id' => $skuadClass->id,
                'title' => 'Salah program',
                'body' => 'Topik ini seharusnya ditolak karena kelas bukan dari program aktif.',
            ])
            ->assertSessionHasErrors('class_id');
    }

    public function test_discussion_reply_notifications_include_program_context(): void
    {
        $teacher = User::factory()->withRole(RoleSlug::Teacher)->create();
        $year = AcademicYear::factory()->active()->create();
        [$batch, $class] = $this->programContext($year, 'Jurnalis Digital', 'jurnalis-phase-13');
        $student = User::factory()->withRole(RoleSlug::Student)->create();
        $this->join($student, $batch, $class);

        $topic = DiscussionTopic::query()->create([
            'academic_year_id' => $year->id,
            'program_batch_id' => $batch->id,
            'class_id' => $class->id,
            'created_by' => $teacher->id,
            'title' => 'Ruang editor jurnalis',
            'body' => 'Diskusi untuk menata naskah berita.',
        ]);

        $this->withSession(['active_program_batch_id' => $batch->id])
            ->actingAs($student)
            ->post(route('interactions.discussions.posts.store', $topic), ['body' => 'Saya sudah menyiapkan outline berita desa.'])
            ->assertSessionHas('success');

        $notification = $teacher->notifications()->where('data->kind', 'discussion_reply')->firstOrFail();
        $this->assertSame($batch->id, $notification->data['program_batch_id']);
        $this->assertSame('Jurnalis Digital', $notification->data['program_name']);
        $this->assertStringContainsString('RKDD Cikampek Selatan', $notification->data['program_context']);
    }

    /**
     * @return array{ProgramBatch, SchoolClass}
     */
    private function programContext(AcademicYear $year, string $name, string $slug): array
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
            ['slug' => 'rkdd-phase-13'],
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
        $module = LearningModule::factory()->create(['academic_year_id' => $year->id, 'program_batch_id' => $batch->id]);
        LearningSession::factory()->create([
            'academic_year_id' => $year->id,
            'program_batch_id' => $batch->id,
            'learning_module_id' => $module->id,
        ]);
        $class = SchoolClass::factory()->create([
            'academic_year_id' => $year->id,
            'program_batch_id' => $batch->id,
            'name' => $name.' 2026',
        ]);

        return [$batch, $class];
    }

    private function join(User $student, ProgramBatch $batch, SchoolClass $class): void
    {
        if (! $student->studentProfile()->exists()) {
            StudentProfile::factory()->create(['user_id' => $student->id, 'class_id' => $class->id]);
        }
        ClassStudent::query()->create([
            'academic_year_id' => $class->academic_year_id,
            'program_batch_id' => $batch->id,
            'class_id' => $class->id,
            'user_id' => $student->id,
            'joined_at' => now(),
            'status' => StudentMembershipStatus::Active->value,
        ]);
    }

    private function announcementPayload(AcademicYear $year, SchoolClass $class, array $overrides = []): array
    {
        return array_merge([
            'academic_year_id' => $year->id,
            'class_id' => $class->id,
            'learning_session_id' => '',
            'title' => 'Pengumuman program '.$class->name,
            'body' => 'Informasi ini hanya untuk peserta pada program aktif.',
            'audience' => AnnouncementAudience::ClassRoom->value,
            'priority' => AnnouncementPriority::Important->value,
            'published_at' => now()->format('Y-m-d H:i:s'),
            'expires_at' => '',
            'is_pinned' => 0,
            'action' => 'draft',
        ], $overrides);
    }
}
