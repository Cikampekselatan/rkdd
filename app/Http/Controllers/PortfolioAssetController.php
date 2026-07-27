<?php

namespace App\Http\Controllers;

use App\Enums\PortfolioApprovalStatus;
use App\Enums\PortfolioVisibility;
use App\Models\PortfolioItem;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PortfolioAssetController extends Controller
{
    public function __invoke(PortfolioItem $portfolioItem, string $kind): StreamedResponse
    {
        abort_unless(in_array($kind, ['thumbnail', 'initial', 'final'], true), 404);

        $isPublic = $portfolioItem->visibility === PortfolioVisibility::PublicApproved
            && $portfolioItem->approval_status === PortfolioApprovalStatus::Approved;

        if (! $isPublic) {
            abort_unless(auth()->check(), 403);
            Gate::authorize('view', $portfolioItem);
        }

        [$path, $name] = $this->resolve($portfolioItem, $kind);
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return $kind === 'thumbnail'
            ? Storage::disk('local')->response($path)
            : Storage::disk('local')->download($path, $name);
    }

    private function resolve(PortfolioItem $item, string $kind): array
    {
        if ($kind === 'thumbnail') {
            return [$item->thumbnail_path, 'thumbnail-'.$item->slug];
        }

        $privatePath = $kind === 'initial' ? $item->initial_file_path : $item->final_file_path;
        $originalName = $kind === 'initial' ? $item->initial_original_name : $item->final_original_name;

        if ($privatePath) {
            return [$privatePath, $originalName ?? 'versi-'.$kind];
        }

        $version = $kind === 'initial' ? $item->initialVersion : $item->finalVersion;
        $file = $version?->files()->first();

        return [$file?->stored_path, $file?->original_name ?? $kind];
    }
}
