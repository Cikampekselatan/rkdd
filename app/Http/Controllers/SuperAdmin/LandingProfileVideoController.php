<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\LandingProfileVideoRequest;
use App\Models\LandingProfileVideo;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LandingProfileVideoController extends Controller
{
    public function edit(): View
    {
        return view('super-admin.profile-video.edit', [
            'video' => LandingProfileVideo::query()->latest()->first() ?? new LandingProfileVideo([
                'title' => 'Kenali RKDD Cikampek Selatan',
                'is_active' => true,
            ]),
        ]);
    }

    public function update(LandingProfileVideoRequest $request): RedirectResponse
    {
        $video = LandingProfileVideo::query()->latest()->first();
        $data = $request->validated() + ['updated_by' => $request->user()->id];

        if ($video) {
            $video->update($data);
        } else {
            LandingProfileVideo::query()->create($data + ['created_by' => $request->user()->id]);
        }

        return redirect()->route('super-admin.profile-video.edit')->with('success', 'Video profil RKDD berhasil disimpan.');
    }
}
