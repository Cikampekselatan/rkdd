<?php

namespace Tests\Feature;

use App\Enums\ShowcaseHighlightPeriod;
use App\Enums\ShowcaseMediaType;
use App\Models\Institution;
use App\Models\Program;
use App\Models\ProgramBatch;
use App\Models\ShowcaseHighlight;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_is_available(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('RKDD Cikampek Selatan')
            ->assertSee('Ruang Komunitas Digital Desa')
            ->assertSee('Ruang tumbuh generasi digital Cikampek Selatan')
            ->assertSee('Masuk Ruang Ilmu')
            ->assertSee('Karya Terbaik')
            ->assertSee('http://digicomciksel.com')
            ->assertSee('SKUAD Digital')
            ->assertSee('Ruang digital yang baik perlu etika')
            ->assertSee('Tata tertib dan etika komunikasi')
            ->assertSee('Aturan penggunaan AI secara jujur dan bertanggung jawab')
            ->assertSee('Masuk peserta')
            ->assertSee('Gabung program')
            ->assertSee('Konten Kreator')
            ->assertSee('Jurnalis Digital')
            ->assertSee('Affiliate &amp; UMKM', false)
            ->assertSee('Teknologi menjadi dekat ketika dipakai untuk belajar')
            ->assertDontSee('Fase fondasi');
    }

    public function test_public_dashboard_displays_weekly_and_monthly_highlights(): void
    {
        ShowcaseHighlight::query()->create([
            'period' => ShowcaseHighlightPeriod::Weekly,
            'title' => 'Poster Literasi Digital',
            'student_name' => 'Tim Kelas 8',
            'caption' => 'Komposisi visual rapi dan pesan kampanye jelas.',
            'url' => 'https://example.com/poster.jpg',
            'media_type' => ShowcaseMediaType::Image,
            'display_order' => 10,
            'is_active' => true,
        ]);
        ShowcaseHighlight::query()->create([
            'period' => ShowcaseHighlightPeriod::Monthly,
            'title' => 'Video Profil Sekolah',
            'student_name' => 'Nabila',
            'url' => 'https://youtu.be/dQw4w9WgXcQ',
            'media_type' => ShowcaseMediaType::Video,
            'is_active' => true,
        ]);
        ShowcaseHighlight::query()->create([
            'period' => ShowcaseHighlightPeriod::Weekly,
            'title' => 'Karya Nonaktif',
            'url' => 'https://example.com/hidden.jpg',
            'media_type' => ShowcaseMediaType::Image,
            'is_active' => false,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Karya peserta adalah bukti bahwa belajar bisa menghasilkan dampak')
            ->assertSee('Poster Literasi Digital')
            ->assertSee('Video Profil Sekolah')
            ->assertDontSee('Karya Nonaktif');
    }

    public function test_landing_program_cards_render_uploaded_logo_and_banner(): void
    {
        Program::query()->create([
            'name' => 'Konten Kreator',
            'slug' => 'konten-kreator',
            'type' => 'pelatihan',
            'description' => 'Program produksi konten.',
            'primary_color' => '#7c3aed',
            'secondary_color' => '#111827',
            'accent_color' => '#f97316',
            'logo_path' => 'program-logos/konten-kreator.jpg',
            'banner_path' => 'program-banners/konten-kreator.jpg',
            'is_active' => true,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('public-program-banner', false)
            ->assertSee('program-assets/1/banner')
            ->assertSee('public-program-logo', false)
            ->assertSee('program-assets/1/logo');
    }

    public function test_landing_program_cards_show_fallback_logo_mark_when_logo_is_missing(): void
    {
        Program::query()->create([
            'name' => 'Content Core',
            'slug' => 'content-core',
            'type' => 'ekstrakurikuler',
            'description' => 'Program produksi konten.',
            'primary_color' => '#0f766e',
            'secondary_color' => '#111827',
            'accent_color' => '#f97316',
            'is_active' => true,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('public-program-logo-fallback', false)
            ->assertSee('CC');
    }

    public function test_missing_program_asset_files_return_svg_placeholders(): void
    {
        $program = Program::query()->create([
            'name' => 'Content Core',
            'slug' => 'content-core',
            'type' => 'ekstrakurikuler',
            'description' => 'Program produksi konten.',
            'primary_color' => '#0f766e',
            'secondary_color' => '#111827',
            'accent_color' => '#f97316',
            'logo_path' => 'program-logos/missing.jpg',
            'banner_path' => 'program-banners/missing.jpg',
            'is_active' => true,
        ]);

        $this->get(route('program.assets', [$program, 'logo']))
            ->assertOk()
            ->assertHeader('content-type', 'image/svg+xml')
            ->assertSee('CC', false);

        $this->get(route('program.assets', [$program, 'banner']))
            ->assertOk()
            ->assertHeader('content-type', 'image/svg+xml')
            ->assertSee('Content Core', false);
    }

    public function test_student_registration_page_explains_profile_questions_before_google(): void
    {
        $batch = $this->programBatch('SKUAD Digital');

        $this->get(route('student.register'))
            ->assertOk()
            ->assertSee('Isi profil sebelum masuk Google')
            ->assertSee('Program yang dipilih')
            ->assertSee('SKUAD Digital')
            ->assertSee('Kelas sekolah asal / asal komunitas')
            ->assertSee('Simpan form, lalu tampilkan Google')
            ->assertDontSee('Lanjut daftar dengan Google');
    }

    public function test_student_must_complete_pre_registration_before_google_button_is_shown(): void
    {
        $batch = $this->programBatch('Konten Kreator');
        $payload = [
            'intended_program_batch_id' => $batch->id,
            'name' => 'Nadia Putri',
            'nickname' => 'Nadia',
            'student_number' => 'SIS-001',
            'nisn' => '12345678',
            'gender' => 'female',
            'birth_date' => now()->subYears(13)->format('Y-m-d'),
            'grade_level' => 8,
            'school_class_name' => '8B',
            'parent_name' => 'Ibu Nadia',
            'parent_phone' => '081234567890',
            'guardian_relationship' => 'Ibu',
            'address' => 'Jatisari',
            'device_access' => ['android', 'laptop'],
            'internet_access' => 'stable',
            'willing_to_share_device' => '1',
            'digital_apps_text' => 'Canva, CapCut',
            'interests' => ['design', 'video'],
            'initial_skills' => ['design'],
            'experience' => 'Pernah membuat poster.',
            'expectation' => 'Ingin belajar membuat karya digital.',
            'learning_targets' => 'Bisa membuat portofolio digital.',
        ];

        $this->post(route('student.register.store'), $payload)
            ->assertRedirect(route('student.register'))
            ->assertSessionHas('student.pre_registration', fn (array $draft): bool => (int) $draft['intended_program_batch_id'] === $batch->id);

        $this->get(route('student.register'))
            ->assertOk()
            ->assertSee('Profil sudah lengkap')
            ->assertSee('Nadia Putri')
            ->assertSee('Konten Kreator')
            ->assertSee('Lanjut daftar dengan Google')
            ->assertDontSee('Simpan form, lalu tampilkan Google');
    }

    public function test_application_uses_indonesian_configuration(): void
    {
        $this->assertSame('RKDD Cikampek Selatan', config('app.name'));
        $this->assertSame('id', config('app.locale'));
        $this->assertSame('id', config('app.fallback_locale'));
        $this->assertSame('Asia/Jakarta', config('app.timezone'));
    }

    private function programBatch(string $programName): ProgramBatch
    {
        $program = Program::query()->create([
            'name' => $programName,
            'slug' => str($programName)->slug().'-test',
            'type' => 'pelatihan',
            'primary_color' => '#0f766e',
            'secondary_color' => '#0f172a',
            'accent_color' => '#f59e0b',
            'is_active' => true,
        ]);
        $institution = Institution::query()->create([
            'name' => 'RKDD Cikampek Selatan',
            'slug' => str($programName)->slug().'-institution-test',
            'type' => 'rkdd',
            'is_active' => true,
        ]);

        return ProgramBatch::query()->create([
            'program_id' => $program->id,
            'institution_id' => $institution->id,
            'name' => $programName.' 2026',
            'slug' => str($programName)->slug().'-2026-test',
            'period_label' => '2026',
            'audience_type' => 'community',
            'participant_label' => 'Peserta',
            'is_active' => true,
        ]);
    }
}
