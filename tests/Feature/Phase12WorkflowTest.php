<?php

namespace Tests\Feature;

use App\Enums\ImportantNoteStatus;
use App\Enums\RoleSlug;
use App\Enums\TeacherActivityStatus;
use App\Models\AcademicYear;
use App\Models\ActivityDocumentation;
use App\Models\ImportantNote;
use App\Models\TeacherActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Phase12WorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_coach_can_save_draft_submit_private_signature_and_teacher_verify(): void
    {
        Storage::fake('local');
        $coach = User::factory()->withRole(RoleSlug::Coach)->create();
        $teacher = User::factory()->withRole(RoleSlug::Teacher)->create();
        $year = AcademicYear::factory()->active()->create();

        $this->actingAs($teacher)->post(route('activity-logs.store'), $this->activityPayload($year, ['submit_now' => 0]))
            ->assertForbidden();

        $this->actingAs($coach)->post(route('activity-logs.store'), $this->activityPayload($year, ['submit_now' => 0]))
            ->assertRedirect();
        $log = TeacherActivityLog::query()->firstOrFail();
        $this->assertSame(TeacherActivityStatus::Draft, $log->status);
        $this->assertSame(1, $log->log_number);

        $this->actingAs($coach)->put(route('activity-logs.update', $log), $this->activityPayload($year, [
            'submit_now' => 1,
            'signature' => UploadedFile::fake()->image('signature.png', 300, 120),
        ]))->assertRedirect(route('activity-logs.show', $log));

        $log->refresh();
        $this->assertSame(TeacherActivityStatus::Submitted, $log->status);
        Storage::disk('local')->assertExists($log->signature_path);
        $this->actingAs($coach)->get(route('activity-logs.signature', $log))->assertOk();

        $this->actingAs($teacher)->patch(route('activity-logs.review', $log), [
            'decision' => 'verified',
            'reviewer_signature' => UploadedFile::fake()->image('teacher-signature.png', 300, 120),
        ])
            ->assertSessionHas('success');
        $this->assertSame(TeacherActivityStatus::Verified, $log->fresh()->status);
        $this->assertDatabaseHas('teacher_activity_log_audits', ['teacher_activity_log_id' => $log->id, 'event' => 'verified']);
        $this->actingAs($coach)->get(route('activity-logs.edit', $log))->assertForbidden();
    }

    public function test_rejected_activity_can_be_corrected_and_cross_teacher_access_is_denied(): void
    {
        Storage::fake('local');
        $teacher = User::factory()->withRole(RoleSlug::Teacher)->create();
        $other = User::factory()->withRole(RoleSlug::Teacher)->create();
        $coach = User::factory()->withRole(RoleSlug::Coach)->create();
        $year = AcademicYear::factory()->create();
        $this->actingAs($coach)->post(route('activity-logs.store'), $this->activityPayload($year, ['submit_now' => 1, 'signature' => UploadedFile::fake()->image('sign.jpg')]))->assertRedirect();
        $log = TeacherActivityLog::query()->firstOrFail();

        $this->actingAs($teacher)->patch(route('activity-logs.review', $log), ['decision' => 'rejected', 'rejection_note' => 'Kegiatan perlu dijelaskan lebih rinci.'])->assertSessionHas('success');
        $this->assertSame(TeacherActivityStatus::Rejected, $log->fresh()->status);
        $this->actingAs($coach)->get(route('activity-logs.edit', $log))->assertOk();
        $this->actingAs($other)->get(route('activity-logs.show', $log))->assertForbidden();
        $this->actingAs($other)->get(route('activity-logs.signature', $log))->assertForbidden();
    }

    public function test_activity_submission_requires_signature_and_unique_teacher_date(): void
    {
        $teacher = User::factory()->withRole(RoleSlug::Teacher)->create();
        $coach = User::factory()->withRole(RoleSlug::Coach)->create();
        $year = AcademicYear::factory()->create();
        $this->actingAs($teacher)->post(route('activity-logs.store'), $this->activityPayload($year, ['submit_now' => 0]))->assertForbidden();
        $this->actingAs($coach)->post(route('activity-logs.store'), $this->activityPayload($year, ['submit_now' => 1]))->assertSessionHasErrors('signature');
        $this->actingAs($coach)->post(route('activity-logs.store'), $this->activityPayload($year, ['submit_now' => 0]))->assertRedirect();
        $this->actingAs($coach)->post(route('activity-logs.store'), $this->activityPayload($year, ['submit_now' => 0]))->assertSessionHasErrors('activity_date');
    }

    public function test_coach_can_submit_activity_with_drawn_signature_and_cannot_mix_signature_methods(): void
    {
        Storage::fake('local');
        $coach = User::factory()->withRole(RoleSlug::Coach)->create();
        $year = AcademicYear::factory()->create();
        $drawnSignature = 'data:image/png;base64,'.base64_encode('fake-png-signature');

        $this->actingAs($coach)->get(route('activity-logs.create'))
            ->assertOk()
            ->assertSee('Tanda tangan langsung')
            ->assertSee('Unggah file');

        $this->actingAs($coach)->post(route('activity-logs.store'), $this->activityPayload($year, [
            'submit_now' => 1,
            'signature_drawn' => $drawnSignature,
        ]))->assertRedirect();

        $log = TeacherActivityLog::query()->firstOrFail();
        $this->assertSame(TeacherActivityStatus::Submitted, $log->status);
        $this->assertSame('tanda-tangan-langsung.png', $log->signature_original_name);
        Storage::disk('local')->assertExists($log->signature_path);

        $otherYear = AcademicYear::factory()->create();
        $this->actingAs($coach)->post(route('activity-logs.store'), $this->activityPayload($otherYear, [
            'activity_date' => today()->subDay()->toDateString(),
            'signature' => UploadedFile::fake()->image('signature.png'),
            'signature_drawn' => $drawnSignature,
        ]))->assertSessionHasErrors('signature');
    }

    public function test_important_note_requires_resolution_and_auto_verifies_after_dual_private_initials(): void
    {
        Storage::fake('local');
        $coach = User::factory()->withRole(RoleSlug::Coach)->create();
        $teacher = User::factory()->withRole(RoleSlug::Teacher)->create();
        $year = AcademicYear::factory()->active()->create();

        $this->actingAs($teacher)->post(route('important-notes.store'), $this->notePayload($year))->assertForbidden();
        $this->actingAs($coach)->post(route('important-notes.store'), $this->notePayload($year, ['status' => 'resolved', 'resolution' => '']))->assertSessionHasErrors('resolution');
        $this->actingAs($coach)->post(route('important-notes.store'), $this->notePayload($year))->assertRedirect();
        $note = ImportantNote::query()->firstOrFail();

        $this->actingAs($coach)->put(route('important-notes.update', $note), $this->notePayload($year, ['status' => 'resolved', 'resolution' => 'Pendampingan selesai dan perangkat pengganti tersedia.']))->assertRedirect();
        $this->actingAs($coach)->post(route('important-notes.sign', $note), ['initial' => UploadedFile::fake()->image('coach.png')])->assertSessionHas('success');
        $this->assertSame(ImportantNoteStatus::Resolved, $note->fresh()->status);
        $this->actingAs($teacher)->post(route('important-notes.sign', $note), ['initial' => UploadedFile::fake()->image('teacher.png')])->assertSessionHas('success');

        $note->refresh();
        $this->assertSame(ImportantNoteStatus::Verified, $note->status);
        $this->assertNotNull($note->verified_at);
        Storage::disk('local')->assertExists($note->teacher_initial_path);
        Storage::disk('local')->assertExists($note->coach_initial_path);
        $this->actingAs($teacher)->get(route('important-notes.initial', [$note, 'coach']))->assertOk();
        $this->actingAs($teacher)->get(route('important-notes.edit', $note))->assertForbidden();
        $this->assertDatabaseHas('important_note_audits', ['important_note_id' => $note->id, 'event' => 'verified']);
    }

    public function test_important_note_initial_can_be_drawn_or_uploaded_but_not_both(): void
    {
        Storage::fake('local');
        $teacher = User::factory()->withRole(RoleSlug::Teacher)->create();
        $coach = User::factory()->withRole(RoleSlug::Coach)->create();
        $year = AcademicYear::factory()->active()->create();
        $drawnInitial = 'data:image/png;base64,'.base64_encode('fake-png-initial');
        $note = ImportantNote::factory()->create(['academic_year_id' => $year->id, 'created_by' => $coach->id]);

        $this->actingAs($teacher)->get(route('important-notes.show', $note))
            ->assertOk()
            ->assertSee('Paraf langsung')
            ->assertSee('Unggah file')
            ->assertSee('jari/stylus');

        $this->actingAs($teacher)->post(route('important-notes.sign', $note), [
            'initial_drawn' => $drawnInitial,
        ])->assertSessionHas('success');

        $note->refresh();
        $this->assertNotNull($note->teacher_initial_path);
        Storage::disk('local')->assertExists($note->teacher_initial_path);

        $this->actingAs($coach)->post(route('important-notes.sign', $note), [
            'initial' => UploadedFile::fake()->image('coach.png'),
            'initial_drawn' => $drawnInitial,
        ])->assertSessionHasErrors('initial');
    }

    public function test_phase12_pages_filters_print_and_role_boundaries_render(): void
    {
        $teacher = User::factory()->withRole(RoleSlug::Teacher)->create();
        $coach = User::factory()->withRole(RoleSlug::Coach)->create();
        $admin = User::factory()->withRole(RoleSlug::Admin)->create();
        $principal = User::factory()->withRole(RoleSlug::Principal)->create();
        $student = User::factory()->withRole(RoleSlug::Student)->create();
        $year = AcademicYear::factory()->create();
        $log = TeacherActivityLog::factory()->create(['academic_year_id' => $year->id, 'teacher_id' => $coach->id, 'log_number' => 1, 'status' => TeacherActivityStatus::Submitted, 'submitted_at' => now()]);
        $note = ImportantNote::factory()->create(['academic_year_id' => $year->id, 'created_by' => $coach->id]);

        $this->actingAs($teacher)->get(route('activity-logs.index', ['month' => now()->format('Y-m')]))->assertOk()->assertSee('Absen pengajar');
        $this->actingAs($teacher)->get(route('activity-logs.print', $log))->assertOk()->assertSee('ABSEN PENGAJAR SKUAD');
        $this->actingAs($teacher)->get(route('activity-logs.create'))->assertForbidden();
        $this->actingAs($coach)->get(route('important-notes.index', ['priority' => 'medium']))->assertOk()->assertSee('Catatan penting');
        $this->actingAs($coach)->get(route('important-notes.print', $note))->assertOk()->assertSee('CATATAN PENTING SKUAD');
        $this->actingAs($coach)->get(route('activity-logs.create'))->assertOk()->assertSee('Tanda tangan langsung');
        $this->actingAs($admin)->get(route('activity-logs.index'))->assertOk()->assertSee('Absen pengajar');
        $this->actingAs($admin)->get(route('activity-logs.print-index'))->assertOk()->assertSee('Laporan Absen Pengajar SKUAD');
        $this->actingAs($teacher)->get(route('important-notes.create'))->assertForbidden();
        $this->actingAs($admin)->get(route('important-notes.create'))->assertForbidden();
        $this->actingAs($principal)->get(route('important-notes.index'))->assertOk()->assertSee('Catatan penting');
        $this->actingAs($principal)->get(route('important-notes.print-index'))->assertOk()->assertSee('CATATAN PENTING ESKUL SKUAD');
        $this->actingAs($student)->get(route('important-notes.index'))->assertForbidden();
    }

    public function test_instructor_activity_log_requires_teacher_signature_before_admin_can_read(): void
    {
        Storage::fake('local');
        $coach = User::factory()->withRole(RoleSlug::Coach)->create();
        $teacher = User::factory()->withRole(RoleSlug::Teacher)->create();
        $admin = User::factory()->withRole(RoleSlug::Admin)->create();
        $year = AcademicYear::factory()->active()->create();
        $drawnSignature = 'data:image/png;base64,'.base64_encode('fake-instructor-signature');
        $drawnTeacherSignature = 'data:image/png;base64,'.base64_encode('fake-teacher-signature');

        $this->actingAs($coach)->post(route('activity-logs.store'), $this->activityPayload($year, [
            'signature_drawn' => $drawnSignature,
            'submit_now' => 1,
        ]))->assertRedirect();

        $log = TeacherActivityLog::query()->firstOrFail();
        $this->assertSame($coach->id, $log->teacher_id);
        $this->actingAs($admin)->get(route('activity-logs.show', $log))->assertForbidden();
        $this->actingAs($teacher)->get(route('activity-logs.show', $log))->assertOk()->assertSee('Tanda tangan Guru/Pembina wajib diisi');

        $this->actingAs($teacher)->patch(route('activity-logs.review', $log), [
            'decision' => 'verified',
            'reviewer_signature_drawn' => $drawnTeacherSignature,
        ])->assertSessionHas('success');

        $log->refresh();
        $this->assertSame(TeacherActivityStatus::Verified, $log->status);
        $this->assertNotNull($log->reviewer_signature_path);
        Storage::disk('local')->assertExists($log->reviewer_signature_path);
        $this->actingAs($admin)->get(route('activity-logs.show', $log))->assertOk()->assertSee('Tanda tangan verifikator');
    }

    public function test_activity_documentation_photo_is_compressed_and_visible_to_leadership(): void
    {
        Storage::fake('public');
        $coach = User::factory()->withRole(RoleSlug::Coach)->create();
        $principal = User::factory()->withRole(RoleSlug::Principal)->create();
        $student = User::factory()->withRole(RoleSlug::Student)->create();
        $year = AcademicYear::factory()->active()->create();

        $this->actingAs($coach)->get(route('activity-documentations.create'))->assertOk()->assertSee('URL video');
        $this->actingAs($coach)->post(route('activity-documentations.store'), [
            'academic_year_id' => $year->id,
            'activity_date' => today()->toDateString(),
            'title' => 'Dokumentasi Latihan Fotografi',
            'description' => 'Praktik hunting foto di lingkungan sekolah.',
            'photo' => UploadedFile::fake()->image('foto-besar.jpg', 2200, 1500),
            'resource_url' => 'https://example.com/album',
            'video_url' => 'https://example.com/video',
        ])->assertRedirect();

        $documentation = ActivityDocumentation::query()->firstOrFail();
        Storage::disk('public')->assertExists($documentation->photo_path);
        $this->assertLessThanOrEqual(512000, Storage::disk('public')->size($documentation->photo_path));
        $this->actingAs($principal)->get(route('activity-documentations.show', $documentation))->assertOk()->assertSee('Dokumentasi Latihan Fotografi')->assertSee('Buka video');
        $this->actingAs($student)->get(route('activity-documentations.index'))->assertForbidden();
    }

    private function activityPayload(AcademicYear $year, array $overrides = []): array
    {
        return array_merge(['academic_year_id' => $year->id, 'activity_date' => today()->toDateString(), 'material' => 'Etika Digital', 'activities' => 'Diskusi dan praktik keamanan akun.', 'assignment' => 'Membuat daftar kebiasaan aman.', 'submit_now' => 0], $overrides);
    }

    private function notePayload(AcademicYear $year, array $overrides = []): array
    {
        return array_merge(['academic_year_id' => $year->id, 'note_date' => today()->toDateString(), 'note' => 'Siswa mengalami kendala perangkat untuk praktik.', 'resolution' => '', 'priority' => 'high', 'status' => 'open'], $overrides);
    }
}
