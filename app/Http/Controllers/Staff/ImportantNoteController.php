<?php

namespace App\Http\Controllers\Staff;

use App\Enums\ImportantNotePriority;
use App\Enums\ImportantNoteStatus;
use App\Enums\RoleSlug;
use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\ImportantNoteRequest;
use App\Http\Requests\Staff\SignImportantNoteRequest;
use App\Models\AcademicYear;
use App\Models\ImportantNote;
use App\Services\ImportantNoteService;
use App\Services\ProgramContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportantNoteController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', ImportantNote::class);
        $filters = $this->validatedFilters($request);
        $notes = $this->filteredQuery($filters, $request)->paginate(12)->withQueryString();

        return view('staff.important-notes.index', ['notes' => $notes, 'filters' => $filters, 'academicYears' => app(ProgramContextService::class)->academicYears($request->user()), 'priorities' => ImportantNotePriority::cases(), 'statuses' => ImportantNoteStatus::cases()]);
    }

    public function printIndex(Request $request): View
    {
        $this->authorize('viewAny', ImportantNote::class);
        $filters = $this->validatedFilters($request);
        $notes = $this->filteredQuery($filters, $request)
            ->with(['teacherInitialer:id,name', 'coachInitialer:id,name'])
            ->limit(500)
            ->get();

        return view('staff.important-notes.print-index', [
            'notes' => $notes,
            'filters' => $filters,
            'selectedYear' => isset($filters['academic_year_id']) ? AcademicYear::find($filters['academic_year_id']) : null,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', ImportantNote::class);

        return view('staff.important-notes.form', $this->formData(new ImportantNote));
    }

    public function store(ImportantNoteRequest $request, ImportantNoteService $service): RedirectResponse
    {
        $note = $service->save(null, $request->validated(), $request->user());

        return redirect()->route('important-notes.show', $note)->with('success', 'Catatan penting berhasil dibuat.');
    }

    public function show(ImportantNote $importantNote): View
    {
        $this->authorize('view', $importantNote);
        $importantNote->load(['academicYear:id,name', 'creator:id,name', 'teacherInitialer:id,name', 'coachInitialer:id,name', 'audits.actor:id,name']);

        return view('staff.important-notes.show', ['note' => $importantNote]);
    }

    public function edit(ImportantNote $importantNote): View
    {
        $this->authorize('update', $importantNote);

        return view('staff.important-notes.form', $this->formData($importantNote));
    }

    public function update(ImportantNoteRequest $request, ImportantNote $importantNote, ImportantNoteService $service): RedirectResponse
    {
        $service->save($importantNote, $request->validated(), $request->user());

        return redirect()->route('important-notes.show', $importantNote)->with('success', 'Catatan penting berhasil diperbarui.');
    }

    public function sign(SignImportantNoteRequest $request, ImportantNote $importantNote, ImportantNoteService $service): RedirectResponse
    {
        $service->sign($importantNote, $request->file('initial'), $request->string('initial_drawn')->toString(), $request->user());

        return back()->with('success', 'Paraf privat berhasil disimpan.');
    }

    public function print(ImportantNote $importantNote): View
    {
        $this->authorize('view', $importantNote);
        $importantNote->load(['academicYear:id,name', 'creator:id,name', 'teacherInitialer:id,name', 'coachInitialer:id,name']);

        return view('staff.important-notes.print', ['note' => $importantNote]);
    }

    public function initial(ImportantNote $importantNote, string $role): StreamedResponse
    {
        $this->authorize('downloadInitial', $importantNote);
        abort_unless(in_array($role, ['teacher', 'coach'], true), 404);
        $path = $importantNote->{$role.'_initial_path'};
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path, 'paraf-'.$role.'.png');
    }

    private function formData(ImportantNote $note): array
    {
        return ['note' => $note, 'academicYears' => app(ProgramContextService::class)->academicYears(request()->user()), 'priorities' => ImportantNotePriority::cases(), 'statuses' => array_filter(ImportantNoteStatus::cases(), fn ($status) => $status !== ImportantNoteStatus::Verified)];
    }

    private function validatedFilters(Request $request): array
    {
        return $request->validate(['academic_year_id' => ['nullable', 'exists:academic_years,id'], 'priority' => ['nullable', 'in:low,medium,high,urgent'], 'status' => ['nullable', 'in:open,in_progress,resolved,verified'], 'from' => ['nullable', 'date'], 'to' => ['nullable', 'date', 'after_or_equal:from']]);
    }

    private function filteredQuery(array $filters, Request $request)
    {
        $activeBatchId = app(ProgramContextService::class)->activeBatchId($request->user());

        return ImportantNote::query()->with(['creator:id,name', 'academicYear:id,name'])->latest('note_date')
            ->when($activeBatchId, fn ($q, int $batchId) => $q->where('program_batch_id', $batchId))
            ->when($request->user()->hasAnyRole([RoleSlug::SuperAdmin, RoleSlug::Admin, RoleSlug::Principal]), fn ($q) => $q->where('status', 'verified'))
            ->when($filters['academic_year_id'] ?? null, fn ($q, $v) => $q->where('academic_year_id', $v))->when($filters['priority'] ?? null, fn ($q, $v) => $q->where('priority', $v))->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))->when($filters['from'] ?? null, fn ($q, $v) => $q->whereDate('note_date', '>=', $v))->when($filters['to'] ?? null, fn ($q, $v) => $q->whereDate('note_date', '<=', $v));
    }
}
