<?php

namespace App\Http\Controllers;

use App\Enums\ShowcaseHighlightPeriod;
use App\Models\ShowcaseHighlight;
use Illuminate\View\View;

class PublicBestWorkController extends Controller
{
    public function __invoke(): View
    {
        $highlights = ShowcaseHighlight::query()
            ->active()
            ->with('programBatch.program:id,name,primary_color,accent_color')
            ->orderByDesc('display_order')
            ->latest()
            ->paginate(12);

        return view('best-works.index', [
            'highlights' => $highlights,
            'weeklyCount' => ShowcaseHighlight::query()->active()->where('period', ShowcaseHighlightPeriod::Weekly)->count(),
            'monthlyCount' => ShowcaseHighlight::query()->active()->where('period', ShowcaseHighlightPeriod::Monthly)->count(),
        ]);
    }
}
