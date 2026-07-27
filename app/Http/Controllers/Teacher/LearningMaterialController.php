<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\LearningMaterialRequest;
use App\Models\LearningMaterial;
use App\Models\LearningSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LearningMaterialController extends Controller
{
    public function store(LearningMaterialRequest $request, LearningSession $learningSession): RedirectResponse
    {
        LearningMaterial::query()->create([
            ...$request->validated(),
            'learning_session_id' => $learningSession->id,
        ]);

        return back()->with('success', 'Materi berhasil ditambahkan.');
    }

    public function edit(LearningMaterial $learningMaterial): View
    {
        $this->authorize('update', $learningMaterial);

        return view('teacher.learning.materials.edit', compact('learningMaterial'));
    }

    public function update(LearningMaterialRequest $request, LearningMaterial $learningMaterial): RedirectResponse
    {
        $learningMaterial->update($request->validated());

        return redirect()->route('teacher.learning.sessions.edit', $learningMaterial->learning_session_id)->with('success', 'Materi berhasil diperbarui.');
    }

    public function destroy(LearningMaterial $learningMaterial): RedirectResponse
    {
        $this->authorize('delete', $learningMaterial);
        $sessionId = $learningMaterial->learning_session_id;
        $learningMaterial->delete();

        return redirect()->route('teacher.learning.sessions.edit', $sessionId)->with('success', 'Materi berhasil dihapus.');
    }
}
