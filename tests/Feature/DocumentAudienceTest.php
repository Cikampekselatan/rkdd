<?php

namespace Tests\Feature;

use App\Enums\DocumentAudience;
use App\Enums\DocumentCategory;
use App\Enums\RoleSlug;
use App\Enums\StudentMembershipStatus;
use App\Enums\UserStatus;
use App\Models\AcademicYear;
use App\Models\DocumentResource;
use App\Models\SchoolClass;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentAudienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_only_sees_published_student_audiences_for_their_year(): void
    {
        $year = AcademicYear::factory()->active()->create();
        $otherYear = AcademicYear::factory()->create();
        $student = $this->studentFor($year);
        $visible = DocumentResource::factory()->published(DocumentAudience::Students)->create(['academic_year_id' => $year->id, 'title' => 'Panduan Siswa']);
        DocumentResource::factory()->published(DocumentAudience::InternalPublic)->create(['academic_year_id' => null, 'title' => 'Informasi Internal']);
        $rppForStudents = DocumentResource::factory()->published(DocumentAudience::Students)->create(['academic_year_id' => $year->id, 'category' => DocumentCategory::LessonPlan, 'title' => 'RPP Salah Audience']);
        $administrationForStudents = DocumentResource::factory()->published(DocumentAudience::InternalPublic)->create(['academic_year_id' => null, 'category' => DocumentCategory::AdministrationForm, 'title' => 'Form Administrasi Salah Audience']);
        $staffOnly = DocumentResource::factory()->published(DocumentAudience::StaffOnly)->create(['academic_year_id' => $year->id, 'title' => 'Rahasia Staff']);
        $otherYearDocument = DocumentResource::factory()->published(DocumentAudience::Students)->create(['academic_year_id' => $otherYear->id, 'title' => 'Tahun Lain']);
        $draft = DocumentResource::factory()->create(['academic_year_id' => $year->id, 'audience' => DocumentAudience::Students, 'title' => 'Draf Siswa']);
        $archived = DocumentResource::factory()->published(DocumentAudience::Students)->create(['academic_year_id' => $year->id, 'title' => 'Arsip Siswa', 'is_active' => false]);

        $this->actingAs($student)
            ->get(route('student.documents.index'))
            ->assertOk()
            ->assertSee('Panduan Siswa')
            ->assertSee('Informasi Internal')
            ->assertSee('Materi dan Bacaan Peserta')
            ->assertDontSee('value="rpp"', false)
            ->assertDontSee('value="form_administrasi"', false)
            ->assertDontSee('value="silabus"', false)
            ->assertDontSee('value="kurikulum"', false)
            ->assertDontSee('RPP Salah Audience')
            ->assertDontSee('Form Administrasi Salah Audience')
            ->assertDontSee('Rahasia Staff')
            ->assertDontSee('Tahun Lain')
            ->assertDontSee('Draf Siswa')
            ->assertDontSee('Arsip Siswa');

        $this->actingAs($student)->get(route('student.documents.show', $visible))->assertOk();
        $this->actingAs($student)->get(route('student.documents.show', $rppForStudents))->assertForbidden();
        $this->actingAs($student)->get(route('student.documents.show', $administrationForStudents))->assertForbidden();
        $this->actingAs($student)->get(route('student.documents.show', $staffOnly))->assertForbidden()->assertSee('Akses ditolak');
        $this->actingAs($student)->get(route('student.documents.show', $otherYearDocument))->assertForbidden();
        $this->actingAs($student)->get(route('student.documents.show', $draft))->assertForbidden();
        $this->actingAs($student)->get(route('student.documents.show', $archived))->assertForbidden();
    }

    public function test_staff_cannot_publish_staff_only_categories_to_student_audience(): void
    {
        $teacher = User::factory()->withRole(RoleSlug::Teacher)->create();

        $this->actingAs($teacher)->post(route('documents.store'), [
            'title' => 'RPP untuk siswa',
            'category' => DocumentCategory::LessonPlan->value,
            'description' => 'Dokumen seharusnya khusus staff.',
            'drive_url' => 'https://drive.google.com/file/d/123456789abcdefghijklmnop/view',
            'audience' => DocumentAudience::Students->value,
            'academic_year_id' => '',
            'semester' => '',
            'sort_order' => 0,
            'is_pinned' => 0,
            'publish_now' => 1,
        ])->assertSessionHasErrors(['category', 'audience']);

        $this->actingAs($teacher)->post(route('documents.store'), [
            'title' => 'Modul untuk siswa',
            'category' => DocumentCategory::Module->value,
            'description' => 'Bahan pembelajaran siswa.',
            'drive_url' => 'https://drive.google.com/file/d/123456789abcdefghijklmnop/view',
            'audience' => DocumentAudience::Students->value,
            'academic_year_id' => '',
            'semester' => '',
            'sort_order' => 0,
            'is_pinned' => 0,
            'publish_now' => 1,
        ])->assertRedirect(route('documents.index'));

        $this->assertDatabaseHas('document_resources', [
            'title' => 'Modul untuk siswa',
            'category' => DocumentCategory::Module->value,
            'audience' => DocumentAudience::Students->value,
        ]);
    }

    public function test_staff_readers_follow_audience_while_managers_can_preview_drafts(): void
    {
        $coach = User::factory()->withRole(RoleSlug::Coach)->create();
        $principal = User::factory()->withRole(RoleSlug::Principal)->create();
        $teacher = User::factory()->withRole(RoleSlug::Teacher)->create();
        $teacherCoach = DocumentResource::factory()->published(DocumentAudience::TeacherCoach)->create(['title' => 'Untuk Guru Coach']);
        DocumentResource::factory()->published(DocumentAudience::AllStaff)->create(['title' => 'Untuk Semua Staff']);
        $staffOnly = DocumentResource::factory()->published(DocumentAudience::StaffOnly)->create(['title' => 'Untuk Pengelola']);
        $draft = DocumentResource::factory()->create(['title' => 'Draf Pengelola']);

        $this->actingAs($coach)->get(route('documents.index'))->assertOk()->assertSee('Untuk Guru Coach')->assertDontSee('Untuk Pengelola');
        $this->actingAs($principal)->get(route('documents.index'))->assertOk()->assertSee('Untuk Semua Staff')->assertDontSee('Untuk Guru Coach');
        $this->actingAs($coach)->get(route('documents.show', $staffOnly))->assertForbidden();
        $this->actingAs($principal)->get(route('documents.show', $teacherCoach))->assertForbidden();
        $this->actingAs($teacher)->get(route('documents.show', $draft))->assertOk()->assertSee('Draf Pengelola');
    }

    public function test_suspended_student_cannot_open_document_center(): void
    {
        $student = User::factory()->withRole(RoleSlug::Student)->create([
            'status' => UserStatus::Suspended,
            'password' => null,
        ]);

        $this->actingAs($student)->get(route('student.documents.index'))->assertForbidden();
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
}
