<?php

namespace Tests\Feature;

use App\Enums\LearningMaterialType;
use App\Enums\LearningSessionStatus;
use App\Enums\RoleSlug;
use App\Models\AcademicYear;
use App\Models\Institution;
use App\Models\LearningMaterial;
use App\Models\LearningModule;
use App\Models\LearningSession;
use App\Models\Program;
use App\Models\ProgramBatch;
use App\Models\StudentLearningProgress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class TeacherLearningManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_create_module_session_material_preview_and_publish(): void
    {
        $teacher = User::factory()->withRole(RoleSlug::Teacher)->create();
        $academicYear = AcademicYear::factory()->active()->create();

        $this->actingAs($teacher)->post(route('teacher.learning.modules.store'), [
            'academic_year_id' => $academicYear->id,
            'module_number' => 1,
            'title' => 'Fondasi Digital',
            'description' => 'Etika dan keamanan digital.',
            'url' => 'https://drive.google.com/drive/folders/fondasi-digital',
            'sort_order' => 1,
            'is_active' => 1,
        ])->assertRedirect(route('teacher.learning.index'));

        $module = LearningModule::query()->firstOrFail();
        $this->assertSame('fondasi-digital-1', $module->slug);
        $this->assertSame('https://drive.google.com/drive/folders/fondasi-digital', $module->url);

        $this->actingAs($teacher)->post(route('teacher.learning.sessions.store'), $this->sessionPayload($academicYear, $module))
            ->assertRedirect();

        $session = LearningSession::query()->firstOrFail();
        $this->assertSame(1, $session->semester);
        $this->assertSame(['Memahami etika digital.', 'Menjaga keamanan akun.'], $session->objectives);

        $this->actingAs($teacher)
            ->get(route('teacher.learning.sessions.preview', $session))
            ->assertOk()
            ->assertSee('Mode preview guru')
            ->assertSee('Orientasi Digital');

        $this->actingAs($teacher)
            ->patch(route('teacher.learning.sessions.publish', $session))
            ->assertSessionHasErrors('learning_session');

        $this->actingAs($teacher)->post(route('teacher.learning.materials.store', $session), [
            'type' => LearningMaterialType::Text->value,
            'title' => 'Bacaan utama',
            'content' => 'Materi etika digital untuk siswa.',
            'url' => '',
            'sort_order' => 1,
            'is_required' => 1,
        ])->assertSessionHas('success');

        $material = LearningMaterial::query()->firstOrFail();
        $this->actingAs($teacher)->put(route('teacher.learning.materials.update', $material), [
            'type' => LearningMaterialType::Link->value,
            'title' => 'Referensi etika',
            'content' => '',
            'url' => 'https://example.com/etika',
            'sort_order' => 2,
            'is_required' => 0,
        ])->assertRedirect(route('teacher.learning.sessions.edit', $session));

        $this->actingAs($teacher)
            ->patch(route('teacher.learning.sessions.publish', $session))
            ->assertSessionHas('success');

        $session->refresh();
        $this->assertSame(LearningSessionStatus::Published, $session->status);
        $this->assertSame($teacher->id, $session->published_by);
        $this->assertNotNull($session->published_at);
    }

    public function test_coach_can_create_learning_session_inside_active_program(): void
    {
        $coach = User::factory()->withRole(RoleSlug::Coach)->create();
        $program = Program::query()->create([
            'name' => 'SKUAD',
            'slug' => 'skuad-coach-learning',
            'type' => 'ekskul',
            'primary_color' => '#0f766e',
            'secondary_color' => '#0f172a',
            'accent_color' => '#f59e0b',
            'is_active' => true,
        ]);
        $institution = Institution::query()->create([
            'name' => 'SMP IT Mentari Ilmu Jatisari',
            'slug' => 'mentari-ilmu-coach-learning',
            'type' => 'school',
            'is_active' => true,
        ]);
        $batch = ProgramBatch::query()->create([
            'program_id' => $program->id,
            'institution_id' => $institution->id,
            'name' => 'SKUAD 2026/2027',
            'slug' => 'skuad-coach-learning-2026',
            'period_label' => '2026/2027',
            'audience_type' => 'school',
            'participant_label' => 'Siswa',
            'is_active' => true,
        ]);
        $coach->assignedProgramBatches()->attach($batch->id);
        $academicYear = AcademicYear::factory()->active()->create(['name' => '2026/2027']);
        $module = LearningModule::factory()->create([
            'academic_year_id' => $academicYear->id,
            'program_batch_id' => $batch->id,
            'module_number' => 1,
        ]);

        $this->withSession(['active_program_batch_id' => $batch->id])
            ->actingAs($coach)
            ->post(route('teacher.learning.sessions.store'), $this->sessionPayload($academicYear, $module))
            ->assertRedirect();

        $this->assertDatabaseHas('learning_sessions', [
            'academic_year_id' => $academicYear->id,
            'program_batch_id' => $batch->id,
            'learning_module_id' => $module->id,
            'session_number' => 1,
            'title' => 'Orientasi Digital',
        ]);
    }

    public function test_learning_forms_enforce_year_number_schedule_and_material_rules(): void
    {
        $teacher = User::factory()->withRole(RoleSlug::Teacher)->create();
        $firstYear = AcademicYear::factory()->create();
        $secondYear = AcademicYear::factory()->create();
        $module = LearningModule::factory()->create(['academic_year_id' => $firstYear->id, 'module_number' => 1]);
        LearningSession::factory()->create([
            'academic_year_id' => $firstYear->id,
            'learning_module_id' => $module->id,
            'session_number' => 1,
        ]);

        $this->actingAs($teacher)->post(route('teacher.learning.sessions.store'), [
            ...$this->sessionPayload($secondYear, $module),
            'session_number' => 256,
            'objectives_text' => '',
            'status' => LearningSessionStatus::Scheduled->value,
            'scheduled_at' => now()->subHour()->format('Y-m-d H:i:s'),
        ])->assertSessionHasErrors(['learning_module_id', 'session_number', 'objectives', 'scheduled_at']);

        $this->actingAs($teacher)->post(route('teacher.learning.modules.store'), [
            'academic_year_id' => $firstYear->id,
            'module_number' => 2,
            'title' => 'Modul URL Rusak',
            'description' => 'URL harus valid.',
            'url' => 'bukan-url',
            'sort_order' => 2,
            'is_active' => 1,
        ])->assertSessionHasErrors('url');

        $session = LearningSession::factory()->create([
            'academic_year_id' => $firstYear->id,
            'learning_module_id' => $module->id,
            'session_number' => 2,
        ]);
        $this->actingAs($teacher)->post(route('teacher.learning.materials.store', $session), [
            'type' => LearningMaterialType::Video->value,
            'title' => 'Video tanpa URL',
            'content' => '',
            'url' => '',
            'sort_order' => 1,
            'is_required' => 1,
        ])->assertSessionHasErrors('url');
    }

    public function test_delete_protection_and_teacher_only_routes_are_enforced(): void
    {
        $teacher = User::factory()->withRole(RoleSlug::Teacher)->create();
        $admin = User::factory()->withRole(RoleSlug::Admin)->create();
        $module = LearningModule::factory()->create();
        $session = LearningSession::factory()->create([
            'academic_year_id' => $module->academic_year_id,
            'learning_module_id' => $module->id,
            'status' => LearningSessionStatus::Draft,
        ]);
        StudentLearningProgress::factory()->create(['learning_session_id' => $session->id]);

        $this->actingAs($teacher)
            ->delete(route('teacher.learning.modules.destroy', $module))
            ->assertSessionHasErrors('learning_module');
        $this->actingAs($teacher)
            ->delete(route('teacher.learning.sessions.destroy', $session))
            ->assertSessionHasErrors('learning_session');
        $this->actingAs($admin)
            ->get(route('teacher.learning.index'))
            ->assertForbidden();
    }

    public function test_teacher_can_remove_a_draft_and_reuse_its_number_for_a_manual_title(): void
    {
        $teacher = User::factory()->withRole(RoleSlug::Teacher)->create();
        $academicYear = AcademicYear::factory()->create();
        $module = LearningModule::factory()->create([
            'academic_year_id' => $academicYear->id,
            'module_number' => 1,
        ]);
        $session = LearningSession::factory()->create([
            'academic_year_id' => $academicYear->id,
            'learning_module_id' => $module->id,
            'session_number' => 1,
            'status' => LearningSessionStatus::Draft,
        ]);
        $material = LearningMaterial::factory()->create(['learning_session_id' => $session->id]);

        $this->actingAs($teacher)
            ->delete(route('teacher.learning.sessions.destroy', $session))
            ->assertRedirect(route('teacher.learning.index'));

        $this->assertSoftDeleted($session);
        $this->assertSoftDeleted($material);

        $this->actingAs($teacher)->post(route('teacher.learning.sessions.store'), [
            ...$this->sessionPayload($academicYear, $module),
            'title' => 'Judul Pertemuan Manual',
        ])->assertRedirect();

        $restored = LearningSession::query()->where('session_number', 1)->firstOrFail();
        $this->assertSame($session->id, $restored->id);
        $this->assertSame('Judul Pertemuan Manual', $restored->title);
        $this->assertSame(0, $restored->materials()->count());
    }

    public function test_due_scheduled_sessions_are_published_automatically_when_material_is_ready(): void
    {
        $teacher = User::factory()->withRole(RoleSlug::Teacher)->create();
        $module = LearningModule::factory()->create();
        $due = LearningSession::factory()->create([
            'academic_year_id' => $module->academic_year_id,
            'learning_module_id' => $module->id,
            'session_number' => 1,
            'status' => LearningSessionStatus::Scheduled,
            'scheduled_at' => now()->subMinute(),
            'created_by' => $teacher->id,
            'updated_by' => $teacher->id,
        ]);
        $future = LearningSession::factory()->create([
            'academic_year_id' => $module->academic_year_id,
            'learning_module_id' => $module->id,
            'session_number' => 2,
            'status' => LearningSessionStatus::Scheduled,
            'scheduled_at' => now()->addHour(),
            'created_by' => $teacher->id,
        ]);
        $withoutMaterial = LearningSession::factory()->create([
            'academic_year_id' => $module->academic_year_id,
            'learning_module_id' => $module->id,
            'session_number' => 3,
            'status' => LearningSessionStatus::Scheduled,
            'scheduled_at' => now()->subMinute(),
            'created_by' => $teacher->id,
        ]);
        LearningMaterial::factory()->create(['learning_session_id' => $due->id]);
        LearningMaterial::factory()->create(['learning_session_id' => $future->id]);

        $this->assertSame(0, Artisan::call('learning:publish-scheduled'));

        $this->assertSame(LearningSessionStatus::Published, $due->fresh()->status);
        $this->assertSame($teacher->id, $due->fresh()->published_by);
        $this->assertSame(LearningSessionStatus::Scheduled, $future->fresh()->status);
        $this->assertSame(LearningSessionStatus::Scheduled, $withoutMaterial->fresh()->status);
    }

    private function sessionPayload(AcademicYear $academicYear, LearningModule $module): array
    {
        return [
            'academic_year_id' => $academicYear->id,
            'learning_module_id' => $module->id,
            'session_number' => 1,
            'title' => 'Orientasi Digital',
            'duration_minutes' => 90,
            'objectives_text' => "Memahami etika digital.\nMenjaga keamanan akun.",
            'introduction' => 'Pengantar materi.',
            'summary' => 'Rangkuman materi.',
            'practice_instructions' => 'Tulis tiga kebiasaan digital yang aman.',
            'reflection_prompt' => 'Apa kebiasaan digital yang akan kamu ubah?',
            'status' => LearningSessionStatus::Draft->value,
            'scheduled_at' => '',
        ];
    }
}
