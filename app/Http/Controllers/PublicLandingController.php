<?php

namespace App\Http\Controllers;

use App\Enums\ShowcaseHighlightPeriod;
use App\Models\KnowledgeResource;
use App\Models\LandingCarouselSlide;
use App\Models\LandingProfileVideo;
use App\Models\Program;
use App\Models\ShowcaseHighlight;
use App\Support\StudentAgreementRules;
use Illuminate\View\View;

class PublicLandingController extends Controller
{
    public function __invoke(): View
    {
        $highlights = ShowcaseHighlight::query()
            ->active()
            ->with('programBatch.program:id,name,primary_color,accent_color')
            ->latest('updated_at')
            ->orderByDesc('display_order')
            ->limit(8)
            ->get()
            ->groupBy(fn (ShowcaseHighlight $highlight): string => $highlight->period->value);

        return view('landing', [
            'weeklyHighlights' => $highlights->get(ShowcaseHighlightPeriod::Weekly->value, collect()),
            'monthlyHighlights' => $highlights->get(ShowcaseHighlightPeriod::Monthly->value, collect()),
            'slides' => LandingCarouselSlide::query()->active()->orderByDesc('display_order')->latest()->limit(5)->get(),
            'knowledgeResources' => KnowledgeResource::query()->active()->orderByDesc('is_featured')->orderByDesc('display_order')->latest()->limit(6)->get(),
            'profileVideo' => LandingProfileVideo::query()->active()->latest()->first(),
            'publicPrograms' => Program::query()->where('is_active', true)->withCount('batches')->latest()->limit(6)->get(),
            'agreementRules' => StudentAgreementRules::all(),
        ]);
    }
}
