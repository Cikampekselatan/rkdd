<?php

namespace App\Services;

use App\Enums\PortfolioWorkType;
use App\Models\PortfolioWorkTypeOption;
use App\Models\Program;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class PortfolioWorkTypeOptionService
{
    /**
     * @return array<int, array{name: string, slug: string}>
     */
    public function defaultsFor(Program $program): array
    {
        $base = [
            ['name' => 'Poster', 'slug' => 'poster'],
            ['name' => 'Infografis', 'slug' => 'infographic'],
            ['name' => 'Foto', 'slug' => 'photo'],
            ['name' => 'Video', 'slug' => 'video'],
            ['name' => 'Presentasi', 'slug' => 'presentation'],
            ['name' => 'Laporan', 'slug' => 'report'],
            ['name' => 'Proyek akhir', 'slug' => 'final_project'],
            ['name' => 'Sertifikat', 'slug' => 'certificate'],
        ];

        $name = Str::lower($program->name.' '.$program->type);

        $specific = match (true) {
            str_contains($name, 'jurnal') => [
                ['name' => 'Artikel berita', 'slug' => 'artikel_berita'],
                ['name' => 'Foto jurnalistik', 'slug' => 'foto_jurnalistik'],
                ['name' => 'Video liputan', 'slug' => 'video_liputan'],
                ['name' => 'Naskah wawancara', 'slug' => 'naskah_wawancara'],
                ['name' => 'Majalah digital', 'slug' => 'majalah_digital'],
                ['name' => 'Laporan liputan', 'slug' => 'laporan_liputan'],
            ],
            str_contains($name, 'affiliate') || str_contains($name, 'affiliator') || str_contains($name, 'umkm') => [
                ['name' => 'Katalog produk', 'slug' => 'katalog_produk'],
                ['name' => 'Landing page produk', 'slug' => 'landing_page_produk'],
                ['name' => 'Copywriting promosi', 'slug' => 'copywriting_promosi'],
                ['name' => 'Konten affiliate', 'slug' => 'konten_affiliate'],
                ['name' => 'Link campaign', 'slug' => 'link_campaign'],
                ['name' => 'Analisis performa', 'slug' => 'analisis_performa'],
            ],
            str_contains($name, 'content') || str_contains($name, 'konten') => [
                ['name' => 'Video pendek', 'slug' => 'video_pendek'],
                ['name' => 'Thumbnail', 'slug' => 'thumbnail'],
                ['name' => 'Script konten', 'slug' => 'script_konten'],
                ['name' => 'Kalender konten', 'slug' => 'kalender_konten'],
                ['name' => 'Paket branding', 'slug' => 'paket_branding'],
                ['name' => 'Copywriting', 'slug' => 'copywriting'],
            ],
            str_contains($name, 'coding') || str_contains($name, 'ai') => [
                ['name' => 'Website', 'slug' => 'website'],
                ['name' => 'Aplikasi sederhana', 'slug' => 'aplikasi_sederhana'],
                ['name' => 'Prompt project', 'slug' => 'prompt_project'],
                ['name' => 'Automasi', 'slug' => 'automasi'],
                ['name' => 'Dokumentasi teknis', 'slug' => 'dokumentasi_teknis'],
            ],
            default => [],
        };

        return collect([...$specific, ...$base])
            ->unique('slug')
            ->values()
            ->all();
    }

    public function ensureDefaults(Program $program): void
    {
        foreach ($this->defaultsFor($program) as $index => $option) {
            PortfolioWorkTypeOption::query()->firstOrCreate(
                ['program_id' => $program->id, 'slug' => $option['slug']],
                ['name' => $option['name'], 'sort_order' => ($index + 1) * 10, 'is_active' => true],
            );
        }
    }

    /**
     * @return Collection<int, PortfolioWorkTypeOption>
     */
    public function activeFor(?Program $program): Collection
    {
        if (! $program) {
            return new Collection(collect(PortfolioWorkType::cases())->map(
                fn (PortfolioWorkType $type, int $index): PortfolioWorkTypeOption => new PortfolioWorkTypeOption([
                    'name' => $type->label(),
                    'slug' => $type->value,
                    'sort_order' => ($index + 1) * 10,
                    'is_active' => true,
                ]),
            )->all());
        }

        $this->ensureDefaults($program);

        return PortfolioWorkTypeOption::query()
            ->where('program_id', $program->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function labelFor(?string $slug): string
    {
        if (! $slug) {
            return '-';
        }

        if ($legacy = PortfolioWorkType::tryFrom($slug)) {
            return $legacy->label();
        }

        return PortfolioWorkTypeOption::query()->where('slug', $slug)->value('name')
            ?? Str::headline(str_replace(['_', '-'], ' ', $slug));
    }
}
