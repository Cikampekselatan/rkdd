<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\LandingCarouselSlideRequest;
use App\Models\LandingCarouselSlide;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LandingCarouselSlideController extends Controller
{
    public function index(): View
    {
        return view('super-admin.landing-slides.index', [
            'slides' => LandingCarouselSlide::query()->latest()->orderByDesc('display_order')->paginate(12),
        ]);
    }

    public function create(): View
    {
        return view('super-admin.landing-slides.form', ['slide' => new LandingCarouselSlide]);
    }

    public function store(LandingCarouselSlideRequest $request): RedirectResponse
    {
        LandingCarouselSlide::query()->create($request->validated() + [
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return redirect()->route('super-admin.landing-slides.index')->with('success', 'Foto karusel beranda berhasil ditambahkan.');
    }

    public function edit(LandingCarouselSlide $landingSlide): View
    {
        return view('super-admin.landing-slides.form', ['slide' => $landingSlide]);
    }

    public function update(LandingCarouselSlideRequest $request, LandingCarouselSlide $landingSlide): RedirectResponse
    {
        $landingSlide->update($request->validated() + ['updated_by' => $request->user()->id]);

        return redirect()->route('super-admin.landing-slides.index')->with('success', 'Foto karusel beranda berhasil diperbarui.');
    }

    public function destroy(LandingCarouselSlide $landingSlide): RedirectResponse
    {
        $landingSlide->delete();

        return redirect()->route('super-admin.landing-slides.index')->with('success', 'Foto karusel beranda berhasil diarsipkan.');
    }
}
