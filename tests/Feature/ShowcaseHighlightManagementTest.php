<?php

namespace Tests\Feature;

use App\Enums\RoleSlug;
use App\Enums\ShowcaseHighlightPeriod;
use App\Enums\ShowcaseMediaType;
use App\Models\ShowcaseHighlight;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowcaseHighlightManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_teacher_can_manage_public_highlight_url(): void
    {
        $teacher = User::factory()->withRole(RoleSlug::Teacher)->create();

        $this->actingAs($teacher)
            ->get(route('showcase-highlights.create'))
            ->assertOk()
            ->assertSee('URL hasil terbaik');

        $this->actingAs($teacher)
            ->post(route('showcase-highlights.store'), [
                'period' => ShowcaseHighlightPeriod::Weekly->value,
                'title' => 'Poster Anti Hoaks',
                'student_name' => 'Alya',
                'caption' => 'Karya minggu ini yang paling kuat secara pesan.',
                'url' => 'https://example.com/poster.png',
                'display_order' => 7,
                'is_active' => 1,
            ])
            ->assertRedirect(route('showcase-highlights.index'));

        $highlight = ShowcaseHighlight::query()->firstOrFail();
        $this->assertSame(ShowcaseMediaType::Image, $highlight->media_type);
        $this->assertSame($teacher->id, $highlight->created_by);

        $this->actingAs($teacher)
            ->put(route('showcase-highlights.update', $highlight), [
                'period' => ShowcaseHighlightPeriod::Monthly->value,
                'title' => 'Video Mini Dokumenter',
                'student_name' => 'Alya',
                'caption' => 'Versi final kurasi bulanan.',
                'url' => 'https://youtu.be/dQw4w9WgXcQ',
                'display_order' => 9,
                'is_active' => 1,
            ])
            ->assertRedirect(route('showcase-highlights.index'));

        $highlight->refresh();
        $this->assertSame(ShowcaseHighlightPeriod::Monthly, $highlight->period);
        $this->assertSame(ShowcaseMediaType::Video, $highlight->media_type);

        $this->actingAs($teacher)
            ->delete(route('showcase-highlights.destroy', $highlight))
            ->assertRedirect(route('showcase-highlights.index'));

        $this->assertSoftDeleted($highlight);
    }

    public function test_guest_and_student_cannot_manage_public_highlights(): void
    {
        $student = User::factory()->withRole(RoleSlug::Student)->create();

        $this->get(route('showcase-highlights.index'))->assertRedirect(route('login'));
        $this->actingAs($student)->get(route('showcase-highlights.index'))->assertForbidden();
    }
}
