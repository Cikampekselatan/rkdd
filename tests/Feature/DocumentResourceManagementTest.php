<?php

namespace Tests\Feature;

use App\Enums\DocumentAudience;
use App\Enums\DocumentCategory;
use App\Enums\RoleSlug;
use App\Models\AcademicYear;
use App\Models\DocumentResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentResourceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manually_create_publish_pin_archive_and_update_a_guide(): void
    {
        $admin = User::factory()->withRole(RoleSlug::Admin)->create();
        $academicYear = AcademicYear::factory()->active()->create();
        $fileId = '1AbCdEfGhIjKlMnOpQrStUvWxYz';

        $this->actingAs($admin)->post(route('documents.store'), $this->payload($academicYear, [
            'drive_url' => "https://docs.google.com/document/d/{$fileId}/edit",
            'publish_now' => 1,
        ]))->assertRedirect(route('documents.index'));

        $resource = DocumentResource::query()->firstOrFail();
        $this->assertSame($fileId, $resource->drive_file_id);
        $this->assertSame("https://docs.google.com/document/d/{$fileId}/preview", $resource->preview_url);
        $this->assertTrue($resource->is_active);
        $this->assertSame($admin->id, $resource->published_by);
        $this->assertDatabaseHas('document_resource_logs', ['document_resource_id' => $resource->id, 'event' => 'created']);

        $this->actingAs($admin)->patch(route('documents.pin', $resource))->assertSessionHas('success');
        $this->assertTrue($resource->fresh()->is_pinned);

        $this->actingAs($admin)->put(route('documents.update', $resource), $this->payload($academicYear, [
            'title' => 'Panduan Canva Diperbarui',
            'sort_order' => 8,
            'publish_now' => 0,
        ]))->assertRedirect(route('documents.index'));

        $this->assertDatabaseHas('document_resources', [
            'id' => $resource->id,
            'title' => 'Panduan Canva Diperbarui',
            'sort_order' => 8,
        ]);

        $this->actingAs($admin)->patch(route('documents.archive', $resource))->assertSessionHas('success');
        $this->assertFalse($resource->fresh()->is_active);
        $this->assertDatabaseHas('document_resource_logs', ['document_resource_id' => $resource->id, 'event' => 'archived']);
    }

    public function test_teacher_can_delete_and_readd_a_manual_guide_with_the_same_title(): void
    {
        $teacher = User::factory()->withRole(RoleSlug::Teacher)->create();
        $academicYear = AcademicYear::factory()->create();

        $this->actingAs($teacher)->post(route('documents.store'), $this->payload($academicYear))->assertRedirect();
        $first = DocumentResource::query()->firstOrFail();

        $this->actingAs($teacher)
            ->delete(route('documents.destroy', $first))
            ->assertRedirect(route('documents.index'));

        $this->assertSoftDeleted($first);
        $this->assertDatabaseHas('document_resource_logs', ['document_resource_id' => $first->id, 'event' => 'deleted']);

        $this->actingAs($teacher)->post(route('documents.store'), $this->payload($academicYear))->assertRedirect();

        $this->assertSame(1, DocumentResource::query()->where('title', 'Panduan Canva')->count());
        $this->assertSame(2, DocumentResource::withTrashed()->where('title', 'Panduan Canva')->count());
    }

    public function test_document_validation_and_manager_authorization_are_enforced(): void
    {
        $admin = User::factory()->withRole(RoleSlug::Admin)->create();
        $coach = User::factory()->withRole(RoleSlug::Coach)->create();
        $academicYear = AcademicYear::factory()->create();

        $this->actingAs($admin)->post(route('documents.store'), $this->payload($academicYear, [
            'title' => '',
            'category' => 'invalid',
            'audience' => 'public',
            'drive_url' => 'https://example.com/document.pdf',
        ]))->assertSessionHasErrors(['title', 'category', 'audience', 'drive_url']);

        $this->actingAs($coach)
            ->post(route('documents.store'), $this->payload($academicYear))
            ->assertForbidden();

        $resource = DocumentResource::factory()->create();
        $this->actingAs($coach)
            ->delete(route('documents.destroy', $resource))
            ->assertForbidden();
    }

    public function test_staff_index_supports_search_and_category_filter(): void
    {
        $admin = User::factory()->withRole(RoleSlug::Admin)->create();
        DocumentResource::factory()->create(['title' => 'Panduan Khusus Canva', 'category' => DocumentCategory::Guide]);
        DocumentResource::factory()->create(['title' => 'Silabus Semester', 'category' => DocumentCategory::Syllabus]);

        $this->actingAs($admin)
            ->get(route('documents.index', ['q' => 'Canva', 'category' => DocumentCategory::Guide->value]))
            ->assertOk()
            ->assertSee('Panduan Khusus Canva')
            ->assertDontSee('Silabus Semester');
    }

    private function payload(AcademicYear $academicYear, array $overrides = []): array
    {
        return [
            'academic_year_id' => $academicYear->id,
            'title' => 'Panduan Canva',
            'category' => DocumentCategory::Guide->value,
            'description' => 'Panduan penggunaan Canva untuk kegiatan SKUAD.',
            'drive_url' => 'https://drive.google.com/file/d/1AbCdEfGhIjKlMnOpQrStUvWxYz/view',
            'audience' => DocumentAudience::Students->value,
            'semester' => 1,
            'sort_order' => 1,
            'is_pinned' => 0,
            'publish_now' => 0,
            ...$overrides,
        ];
    }
}
