<?php

namespace Tests\Feature;

use App\Enums\AnnouncementAudience;
use App\Enums\DiscussionStatus;
use App\Enums\RoleSlug;
use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\ClassStudent;
use App\Models\DiscussionPost;
use App\Models\DiscussionTopic;
use App\Models\SchoolClass;
use App\Models\StudentProfile;
use App\Models\User;
use App\Notifications\AnnouncementPublishedNotification;
use App\Notifications\DiscussionReplyNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class Phase16InteractionsWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_publishes_class_announcement_only_class_members_see_it_and_read_is_persisted(): void
    {
        Notification::fake();
        [$year, $class] = $this->classContext();
        $teacher = User::factory()->withRole(RoleSlug::Teacher)->create();
        [$student] = $this->studentContext($year, $class);
        [$outsider] = $this->studentContext($year, SchoolClass::factory()->create(['academic_year_id' => $year->id]));

        $response = $this->actingAs($teacher)->post(route('teacher.announcements.store'), $this->announcementPayload($year, $class, ['audience' => 'class', 'action' => 'publish']));
        $response->assertRedirect();
        $announcement = Announcement::firstOrFail();
        Notification::assertSentTo($student, AnnouncementPublishedNotification::class);
        Notification::assertNotSentTo($outsider, AnnouncementPublishedNotification::class);

        $this->actingAs($student)->get(route('interactions.announcements.index'))->assertOk()->assertSee($announcement->title);
        $this->actingAs($outsider)->get(route('interactions.announcements.index'))->assertOk()->assertDontSee($announcement->title);
        $this->actingAs($student)->get(route('interactions.announcements.show', $announcement))->assertOk();
        $this->assertDatabaseHas('announcement_reads', ['announcement_id' => $announcement->id, 'user_id' => $student->id]);
    }

    public function test_announcement_audience_schedule_expiry_and_validation_are_enforced(): void
    {
        [$year, $class] = $this->classContext();
        $teacher = User::factory()->withRole(RoleSlug::Teacher)->create();
        [$student] = $this->studentContext($year, $class);
        $future = Announcement::factory()->create(['created_by' => $teacher->id, 'audience' => AnnouncementAudience::All, 'published_at' => now()->addDay(), 'is_published' => true]);
        $expired = Announcement::factory()->create(['created_by' => $teacher->id, 'audience' => AnnouncementAudience::All, 'expires_at' => now()->subMinute(), 'is_published' => true]);
        $draft = Announcement::factory()->create(['created_by' => $teacher->id, 'audience' => AnnouncementAudience::All, 'is_published' => false, 'published_at' => null]);
        $this->actingAs($student)->get(route('interactions.announcements.index'))->assertDontSee($future->title)->assertDontSee($expired->title)->assertDontSee($draft->title);
        $this->actingAs($teacher)->post(route('teacher.announcements.store'), $this->announcementPayload($year, $class, ['audience' => 'class', 'class_id' => '', 'action' => 'publish']))->assertSessionHasErrors('class_id');
        $this->actingAs($student)->get(route('teacher.announcements.create'))->assertForbidden();
    }

    public function test_teacher_can_open_announcement_create_form_with_academic_years(): void
    {
        [$year, $class] = $this->classContext();
        $teacher = User::factory()->withRole(RoleSlug::Teacher)->create();

        $this->actingAs($teacher)->get(route('teacher.announcements.create'))
            ->assertOk()
            ->assertSee('Buat pengumuman')
            ->assertSee($year->name)
            ->assertSee($class->name);
    }

    public function test_student_forum_is_class_scoped_and_supports_one_level_replies_reports_and_teacher_moderation(): void
    {
        [$year, $class] = $this->classContext();
        [$student] = $this->studentContext($year, $class);
        [$peer] = $this->studentContext($year, $class);
        $student->forceFill(['profile_photo_path' => 'profile-photos/student-comment.jpg'])->save();
        $peer->forceFill(['profile_photo_path' => 'profile-photos/peer-comment.jpg'])->save();
        [$outsider] = $this->studentContext($year, SchoolClass::factory()->create(['academic_year_id' => $year->id]));
        $teacher = User::factory()->withRole(RoleSlug::Teacher)->create();

        $this->actingAs($student)->post(route('interactions.discussions.store'), ['title' => 'Bagaimana menyusun refleksi?', 'body' => 'Saya ingin memahami cara membuat refleksi yang lebih kuat.'])->assertRedirect();
        $topic = DiscussionTopic::firstOrFail();
        $this->actingAs($outsider)->get(route('interactions.discussions.show', $topic))->assertForbidden();
        $this->actingAs($peer)->post(route('interactions.discussions.posts.store', $topic), ['body' => 'Mulai dari tantangan dan keputusanmu.'])->assertSessionHas('success');
        $post = DiscussionPost::firstOrFail();
        $this->actingAs($student)->post(route('interactions.discussions.posts.store', $topic), ['body' => 'Terima kasih.', 'parent_id' => $post->id])->assertSessionHas('success');
        $reply = DiscussionPost::latest('id')->firstOrFail();
        $this->actingAs($student)->post(route('interactions.discussions.posts.store', $topic), ['body' => 'Balasan tingkat dua.', 'parent_id' => $reply->id])->assertSessionHasErrors('parent_id');
        $this->actingAs($student)->post(route('interactions.discussions.posts.report', $post), ['reason' => 'Isi tidak sesuai topik.'])->assertSessionHas('success');
        $this->actingAs($student)->post(route('interactions.discussions.posts.report', $post), ['reason' => 'Laporan yang sama.'])->assertSessionHas('success');
        $this->assertDatabaseHas('discussion_reports', ['post_id' => $post->id, 'reported_by' => $student->id]);
        $this->assertSame(1, $post->reports()->count());
        $this->actingAs($teacher)->get(route('interactions.discussions.show', $topic))
            ->assertOk()
            ->assertSee('1 laporan')
            ->assertSee('storage/profile-photos/peer-comment.jpg')
            ->assertSee('storage/profile-photos/student-comment.jpg');
        $this->actingAs($teacher)->patch(route('teacher.discussion-posts.moderate', $post))->assertSessionHas('success');
        $this->assertTrue($post->fresh()->is_hidden);
        $this->actingAs($teacher)->patch(route('teacher.discussions.moderate', $topic), ['action' => 'close'])->assertSessionHas('success');
        $this->assertSame(DiscussionStatus::Closed, $topic->fresh()->status);
        $this->actingAs($peer)->post(route('interactions.discussions.posts.store', $topic), ['body' => 'Terlambat membalas.'])->assertForbidden();
    }

    public function test_teacher_can_create_open_forum_topic_and_reply_notifies_topic_author(): void
    {
        Notification::fake();
        [$year, $class] = $this->classContext();
        $teacher = User::factory()->withRole(RoleSlug::Teacher)->create();
        [$student] = $this->studentContext($year, $class);
        $this->actingAs($teacher)->post(route('interactions.discussions.store'), ['class_id' => $class->id, 'title' => 'Etika penggunaan sumber', 'body' => 'Mari berbagi praktik mencantumkan sumber.'])->assertRedirect();
        $topic = DiscussionTopic::firstOrFail();
        $this->actingAs($student)->post(route('interactions.discussions.posts.store', $topic), ['body' => 'Saya selalu menuliskan tautan dan penulis.'])->assertSessionHas('success');
        Notification::assertSentTo($teacher, DiscussionReplyNotification::class);
    }

    public function test_discussion_space_is_active_for_all_internal_roles_while_students_remain_class_scoped(): void
    {
        [$year, $class] = $this->classContext();
        [$student] = $this->studentContext($year, $class);
        [$outsider] = $this->studentContext($year, SchoolClass::factory()->create(['academic_year_id' => $year->id]));
        $teacher = User::factory()->withRole(RoleSlug::Teacher)->create();
        $admin = User::factory()->withRole(RoleSlug::Admin)->create();
        $superAdmin = User::factory()->withRole(RoleSlug::SuperAdmin)->create();
        $coach = User::factory()->withRole(RoleSlug::Coach)->create();
        $principal = User::factory()->withRole(RoleSlug::Principal)->create();

        $this->actingAs($admin)
            ->post(route('interactions.discussions.store'), [
                'class_id' => $class->id,
                'title' => 'Ruang diskusi lintas dashboard',
                'body' => 'Topik ini dibuat oleh admin agar semua peran internal bisa aktif membantu siswa.',
            ])
            ->assertRedirect();

        $topic = DiscussionTopic::firstOrFail();

        foreach ([$teacher, $admin, $superAdmin, $coach, $principal] as $staff) {
            $this->actingAs($staff)
                ->get(route('interactions.discussions.index'))
                ->assertOk()
                ->assertSee($topic->title);

            $this->actingAs($staff)
                ->get(route('interactions.discussions.show', $topic))
                ->assertOk()
                ->assertSee('Kirim tanggapan');

            $this->actingAs($staff)
                ->post(route('interactions.discussions.posts.store', $topic), ['body' => 'Tanggapan internal dari '.$staff->name])
                ->assertSessionHas('success');
        }

        $this->actingAs($student)
            ->get(route('interactions.discussions.show', $topic))
            ->assertOk();

        $this->actingAs($outsider)
            ->get(route('interactions.discussions.show', $topic))
            ->assertForbidden();

        $this->actingAs($coach)
            ->patch(route('teacher.discussions.moderate', $topic), ['action' => 'pin'])
            ->assertSessionHas('success');

        $this->actingAs($principal)
            ->patch(route('teacher.discussions.moderate', $topic), ['action' => 'pin'])
            ->assertForbidden();
    }

    public function test_database_notification_can_be_opened_and_marked_read(): void
    {
        [$year, $class] = $this->classContext();
        $teacher = User::factory()->withRole(RoleSlug::Teacher)->create();
        [$student] = $this->studentContext($year, $class);
        $this->actingAs($teacher)->post(route('teacher.announcements.store'), $this->announcementPayload($year, $class, ['audience' => 'class', 'action' => 'publish']))->assertRedirect();
        $notification = $student->notifications()->firstOrFail();
        $this->assertNull($notification->read_at);
        $this->actingAs($student)->get(route('interactions.notifications.read', $notification->id))->assertRedirect();
        $this->assertNotNull($notification->fresh()->read_at);
    }

    private function classContext(): array
    {
        $year = AcademicYear::factory()->create();

        return [$year, SchoolClass::factory()->create(['academic_year_id' => $year->id])];
    }

    private function studentContext(AcademicYear $year, SchoolClass $class): array
    {
        $student = User::factory()->withRole(RoleSlug::Student)->create();
        StudentProfile::factory()->create(['user_id' => $student->id, 'class_id' => $class->id]);
        ClassStudent::create(['academic_year_id' => $year->id, 'class_id' => $class->id, 'user_id' => $student->id, 'joined_at' => now(), 'status' => 'active']);

        return [$student];
    }

    private function announcementPayload(AcademicYear $year, SchoolClass $class, array $overrides = []): array
    {
        return array_merge(['academic_year_id' => $year->id, 'class_id' => $class->id, 'learning_session_id' => '', 'title' => 'Jadwal refleksi mingguan', 'body' => 'Silakan siapkan refleksi sebelum sesi berikutnya.', 'audience' => 'students', 'priority' => 'important', 'published_at' => now()->format('Y-m-d H:i:s'), 'expires_at' => '', 'is_pinned' => 0, 'action' => 'draft'], $overrides);
    }
}
