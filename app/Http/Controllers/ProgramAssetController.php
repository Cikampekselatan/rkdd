<?php

namespace App\Http\Controllers;

use App\Models\Program;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class ProgramAssetController extends Controller
{
    public function __invoke(Program $program, string $kind): Response
    {
        abort_unless(in_array($kind, ['logo', 'banner'], true), 404);

        $path = $kind === 'logo' ? $program->logo_path : $program->banner_path;
        if (! $path || ! Storage::disk('public')->exists($path)) {
            return $this->fallbackSvg($program, $kind);
        }

        return Storage::disk('public')->response($path);
    }

    private function fallbackSvg(Program $program, string $kind): Response
    {
        $primary = preg_match('/^#[0-9a-fA-F]{6}$/', (string) $program->primary_color)
            ? $program->primary_color
            : '#0f766e';
        $secondary = preg_match('/^#[0-9a-fA-F]{6}$/', (string) $program->secondary_color)
            ? $program->secondary_color
            : '#0f172a';
        $initials = str($program->name)
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn ($word) => str($word)->substr(0, 1)->upper())
            ->implode('') ?: 'R';
        $title = htmlspecialchars($program->name, ENT_QUOTES, 'UTF-8');
        $letters = htmlspecialchars($initials, ENT_QUOTES, 'UTF-8');

        $svg = $kind === 'logo'
            ? <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 160 160" role="img" aria-label="{$title}">
  <rect width="160" height="160" rx="36" fill="{$primary}"/>
  <circle cx="124" cy="36" r="28" fill="#ffffff" opacity=".16"/>
  <text x="80" y="96" text-anchor="middle" font-family="Arial, sans-serif" font-size="54" font-weight="800" fill="#ffffff">{$letters}</text>
</svg>
SVG
            : <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 960 420" role="img" aria-label="{$title}">
  <defs>
    <linearGradient id="g" x1="0" x2="1" y1="0" y2="1">
      <stop offset="0" stop-color="{$primary}"/>
      <stop offset="1" stop-color="{$secondary}"/>
    </linearGradient>
  </defs>
  <rect width="960" height="420" fill="url(#g)"/>
  <circle cx="820" cy="70" r="130" fill="#ffffff" opacity=".12"/>
  <circle cx="110" cy="360" r="180" fill="#ffffff" opacity=".08"/>
  <text x="72" y="230" font-family="Arial, sans-serif" font-size="56" font-weight="800" fill="#ffffff">{$title}</text>
</svg>
SVG;

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
