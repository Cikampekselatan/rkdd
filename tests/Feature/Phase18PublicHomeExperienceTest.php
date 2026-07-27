<?php

namespace Tests\Feature;

use App\Enums\RoleSlug;
use App\Enums\ShowcaseHighlightPeriod;
use App\Enums\ShowcaseMediaType;
use App\Models\KnowledgeResource;
use App\Models\LandingCarouselSlide;
use App\Models\LandingProfileVideo;
use App\Models\ShowcaseHighlight;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase18PublicHomeExperienceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_public_home_presents_rkdd_program_story_knowledge_room_video_and_best_works(): void
    {
        LandingCarouselSlide::query()->create([
            'title' => 'Kelas Kreator Digital Desa',
            'eyebrow' => 'Dokumentasi RKDD',
            'description' => 'Peserta belajar membuat karya digital bersama instruktur.',
            'image_url' => 'https://example.com/kegiatan-rkdd.jpg',
            'cta_label' => 'Lihat cerita',
            'cta_url' => 'https://example.com/cerita',
            'display_order' => 10,
            'is_active' => true,
        ]);
        LandingProfileVideo::query()->create([
            'title' => 'Profil RKDD Cikampek Selatan',
            'description' => 'Cerita singkat tentang ruang komunitas digital desa.',
            'video_url' => 'https://youtu.be/dQw4w9WgXcQ',
            'is_active' => true,
        ]);
        KnowledgeResource::query()->create([
            'title' => 'eBook Literasi Digital untuk Siswa',
            'slug' => 'ebook-literasi-digital-untuk-siswa',
            'category' => 'Literasi Digital',
            'content_type' => 'ebook',
            'thumbnail_url' => 'https://example.com/ebook.jpg',
            'description' => 'Bacaan awal untuk menggunakan teknologi secara bijak.',
            'resource_url' => 'https://example.com/ebook.pdf',
            'display_order' => 8,
            'is_featured' => true,
            'is_active' => true,
        ]);
        ShowcaseHighlight::query()->create([
            'period' => ShowcaseHighlightPeriod::Weekly,
            'title' => 'Poster Desa Digital',
            'student_name' => 'Tim Garuda',
            'caption' => 'Karya visual dengan pesan kuat.',
            'url' => 'https://example.com/poster.jpg',
            'media_type' => ShowcaseMediaType::Image,
            'is_active' => true,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Ruang tumbuh generasi digital Cikampek Selatan')
            ->assertSee('http://digicomciksel.com')
            ->assertSee('Kelas Kreator Digital Desa')
            ->assertSee('rkdd-hero-video-card', false)
            ->assertSee('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', false)
            ->assertSee('Profil RKDD Cikampek Selatan')
            ->assertSee('eBook Literasi Digital untuk Siswa')
            ->assertSee('Poster Desa Digital')
            ->assertSee('Masuk Ruang Ilmu')
            ->assertSee('Karya Terbaik');
    }

    public function test_public_knowledge_room_lists_only_published_resources_and_supports_filter(): void
    {
        KnowledgeResource::query()->create([
            'title' => 'Video Tutorial Canva',
            'slug' => 'video-tutorial-canva',
            'category' => 'Desain',
            'content_type' => 'video',
            'thumbnail_url' => 'https://example.com/canva.jpg',
            'description' => 'Belajar desain poster dari dasar.',
            'resource_url' => 'https://youtu.be/dQw4w9WgXcQ',
            'is_active' => true,
        ]);
        KnowledgeResource::query()->create([
            'title' => 'Materi Arsip',
            'slug' => 'materi-arsip',
            'category' => 'Arsip',
            'content_type' => 'ebook',
            'thumbnail_url' => 'https://example.com/arsip.jpg',
            'resource_url' => 'https://example.com/arsip.pdf',
            'is_active' => false,
        ]);

        $this->get(route('knowledge.index', ['type' => 'video']))
            ->assertOk()
            ->assertSee('Ruang Ilmu')
            ->assertSee('Video Tutorial Canva')
            ->assertSee('Video tutorial')
            ->assertDontSee('Materi Arsip');
    }

    public function test_public_knowledge_card_uses_drive_thumbnail_and_has_fallback_visual(): void
    {
        $fileId = '1AbCdEfGhIjKlMnOpQrStUvWxYz';

        KnowledgeResource::query()->create([
            'title' => 'Modul dari Drive',
            'slug' => 'modul-dari-drive',
            'category' => 'Bacaan',
            'content_type' => 'ebook',
            'thumbnail_url' => "https://drive.google.com/file/d/{$fileId}/view",
            'description' => 'Bacaan dari Google Drive.',
            'resource_url' => 'https://example.com/modul',
            'is_active' => true,
        ]);

        $this->get(route('knowledge.index'))
            ->assertOk()
            ->assertSee("https://drive.google.com/thumbnail?id={$fileId}&amp;sz=w1200", false)
            ->assertSee('rkdd-knowledge-thumb-fallback', false)
            ->assertSee('Modul dari Drive');
    }

    public function test_only_super_admin_can_manage_knowledge_resources(): void
    {
        $superAdmin = User::factory()->withRole(RoleSlug::SuperAdmin)->create();
        $admin = User::factory()->withRole(RoleSlug::Admin)->create();

        $this->actingAs($admin)->get(route('super-admin.knowledge-resources.index'))->assertForbidden();

        $this->actingAs($superAdmin)
            ->post(route('super-admin.knowledge-resources.store'), [
                'title' => 'Panduan Membuat Portofolio',
                'category' => 'Portofolio',
                'content_type' => 'guide',
                'thumbnail_url' => 'https://example.com/thumb.jpg',
                'description' => 'Panduan singkat menyusun karya terbaik.',
                'resource_url' => 'https://example.com/panduan',
                'display_order' => 4,
                'is_featured' => 1,
                'is_active' => 1,
            ])
            ->assertRedirect(route('super-admin.knowledge-resources.index'));

        $this->assertDatabaseHas('knowledge_resources', [
            'slug' => 'panduan-membuat-portofolio',
            'content_type' => 'guide',
            'is_featured' => true,
        ]);
    }

    public function test_best_work_page_displays_curated_public_highlights(): void
    {
        ShowcaseHighlight::query()->create([
            'period' => ShowcaseHighlightPeriod::Monthly,
            'title' => 'Video Profil UMKM',
            'student_name' => 'Kelompok Ciksel',
            'url' => 'https://youtu.be/dQw4w9WgXcQ',
            'media_type' => ShowcaseMediaType::Video,
            'is_active' => true,
        ]);

        $this->get(route('best-works.index'))
            ->assertOk()
            ->assertSee('Panggung apresiasi untuk karya peserta RKDD')
            ->assertSee('Video Profil UMKM')
            ->assertSee('Kelompok Ciksel');
    }
}
