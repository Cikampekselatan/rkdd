<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Services\ProfilePhotoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfilePhotoController extends Controller
{
    public function edit(Request $request): View
    {
        return view('account.profile-photo', ['user' => $request->user()]);
    }

    public function update(Request $request, ProfilePhotoService $photos): RedirectResponse
    {
        $data = $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ]);

        $photos->store($request->user(), $data['photo']);

        return back()->with('success', 'Foto profil berhasil diperbarui dan dikompresi maksimal 500 KB.');
    }

    public function destroy(Request $request, ProfilePhotoService $photos): RedirectResponse
    {
        $photos->delete($request->user());

        return back()->with('success', 'Foto profil berhasil dihapus.');
    }
}
