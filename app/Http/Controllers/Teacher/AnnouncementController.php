<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\AnnouncementAudience;
use App\Enums\AnnouncementPriority;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\AnnouncementRequest;
use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\LearningSession;
use App\Models\SchoolClass;
use App\Services\AnnouncementService;
use App\Services\ProgramContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function create(): View
    {
        $this->authorize('create', Announcement::class);

        return view('teacher.announcements.form', ['announcement' => new Announcement, ...$this->formData()]);
    }

    public function store(AnnouncementRequest $request, AnnouncementService $service): RedirectResponse
    {
        $item = $service->save(null, $request->validated(), $request->user());

        return redirect()->route('interactions.announcements.show', $item)->with('success', 'Pengumuman berhasil disimpan.');
    }

    public function edit(Announcement $announcement): View
    {
        $this->authorize('update', $announcement);

        return view('teacher.announcements.form', ['announcement' => $announcement, ...$this->formData()]);
    }

    public function update(AnnouncementRequest $request, Announcement $announcement, AnnouncementService $service): RedirectResponse
    {
        $service->save($announcement, $request->validated(), $request->user());

        return redirect()->route('interactions.announcements.show', $announcement)->with('success', 'Pengumuman berhasil diperbarui.');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $this->authorize('delete', $announcement);
        $announcement->delete();

        return redirect()->route('interactions.announcements.index')->with('success', 'Pengumuman dihapus.');
    }

    private function formData(): array
    {
        $activeBatchId = app(ProgramContextService::class)->activeBatchId(request()->user());

        return [
            'audiences' => AnnouncementAudience::cases(),
            'priorities' => AnnouncementPriority::cases(),
            'years' => AcademicYear::latest('starts_on')->get(),
            'classes' => SchoolClass::where('is_active', true)->when($activeBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))->orderBy('name')->get(),
            'sessions' => LearningSession::when($activeBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))->orderBy('session_number')->get(),
        ];
    }
}
