<?php

namespace App\Http\Controllers\Staff;

use App\Enums\RoleSlug;
use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\ReviewTeacherActivityLogRequest;
use App\Http\Requests\Staff\TeacherActivityLogRequest;
use App\Models\AcademicYear;
use App\Models\TeacherActivityLog;
use App\Models\User;
use App\Services\ProgramContextService;
use App\Services\TeacherActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TeacherActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', TeacherActivityLog::class);
        $filters = $this->validatedFilters($request);
        $query = $this->filteredQuery($filters, $request);

        return view('staff.activity-logs.index', ['logs' => $query->paginate(12)->withQueryString(), 'academicYears' => app(ProgramContextService::class)->academicYears($request->user()), 'teachers' => User::query()->whereHas('roles', fn ($q) => $q->whereIn('slug', [RoleSlug::Teacher->value, RoleSlug::Coach->value]))->orderBy('name')->get(['id', 'name']), 'filters' => $filters]);
    }

    public function printIndex(Request $request): View
    {
        $this->authorize('viewAny', TeacherActivityLog::class);
        $filters = $this->validatedFilters($request);
        $logs = $this->filteredQuery($filters, $request)
            ->with('verifier:id,name')
            ->limit(500)
            ->get();

        return view('staff.activity-logs.print-index', [
            'logs' => $logs,
            'filters' => $filters,
            'selectedYear' => isset($filters['academic_year_id']) ? AcademicYear::find($filters['academic_year_id']) : null,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', TeacherActivityLog::class);

        return view('staff.activity-logs.form', ['log' => new TeacherActivityLog, 'academicYears' => app(ProgramContextService::class)->academicYears(request()->user())]);
    }

    public function store(TeacherActivityLogRequest $request, TeacherActivityLogService $service): RedirectResponse
    {
        $log = $service->save(null, $request->validated(), $request->file('signature'), $request->user());

        return redirect()->route('activity-logs.show', $log)->with('success', 'Absen pengajar berhasil disimpan.');
    }

    public function show(TeacherActivityLog $teacherActivityLog): View
    {
        $this->authorize('view', $teacherActivityLog);
        $teacherActivityLog->load(['teacher.roles', 'academicYear:id,name', 'verifier:id,name', 'audits.actor:id,name']);

        return view('staff.activity-logs.show', ['log' => $teacherActivityLog]);
    }

    public function edit(TeacherActivityLog $teacherActivityLog): View
    {
        $this->authorize('update', $teacherActivityLog);

        return view('staff.activity-logs.form', ['log' => $teacherActivityLog, 'academicYears' => app(ProgramContextService::class)->academicYears(request()->user())]);
    }

    public function update(TeacherActivityLogRequest $request, TeacherActivityLog $teacherActivityLog, TeacherActivityLogService $service): RedirectResponse
    {
        $service->save($teacherActivityLog, $request->validated(), $request->file('signature'), $request->user());

        return redirect()->route('activity-logs.show', $teacherActivityLog)->with('success', 'Absen pengajar berhasil diperbarui.');
    }

    public function review(ReviewTeacherActivityLogRequest $request, TeacherActivityLog $teacherActivityLog, TeacherActivityLogService $service): RedirectResponse
    {
        $service->review($teacherActivityLog, $request->string('decision')->toString(), $request->input('rejection_note'), $request->file('reviewer_signature'), $request->string('reviewer_signature_drawn')->toString(), $request->user());

        return back()->with('success', 'Keputusan verifikasi berhasil disimpan.');
    }

    public function print(TeacherActivityLog $teacherActivityLog): View
    {
        $this->authorize('view', $teacherActivityLog);
        $teacherActivityLog->load(['teacher.roles', 'academicYear:id,name', 'verifier:id,name']);

        return view('staff.activity-logs.print', ['log' => $teacherActivityLog]);
    }

    public function signature(Request $request, TeacherActivityLog $teacherActivityLog): StreamedResponse
    {
        $this->authorize('downloadSignature', $teacherActivityLog);
        $kind = $request->query('kind') === 'reviewer' ? 'reviewer' : 'creator';
        $pathColumn = $kind === 'reviewer' ? 'reviewer_signature_path' : 'signature_path';
        $nameColumn = $kind === 'reviewer' ? 'reviewer_signature_original_name' : 'signature_original_name';

        abort_unless($teacherActivityLog->{$pathColumn} && Storage::disk('local')->exists($teacherActivityLog->{$pathColumn}), 404);

        return Storage::disk('local')->download($teacherActivityLog->{$pathColumn}, $teacherActivityLog->{$nameColumn} ?? 'tanda-tangan.png');
    }

    private function validatedFilters(Request $request): array
    {
        return $request->validate(['academic_year_id' => ['nullable', 'exists:academic_years,id'], 'month' => ['nullable', 'date_format:Y-m'], 'teacher_id' => ['nullable', 'exists:users,id'], 'status' => ['nullable', 'in:draft,submitted,verified,rejected']]);
    }

    private function filteredQuery(array $filters, Request $request)
    {
        $activeBatchId = app(ProgramContextService::class)->activeBatchId($request->user());
        $query = TeacherActivityLog::query()
            ->with(['teacher.roles', 'academicYear:id,name'])
            ->when($activeBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))
            ->latest('activity_date');
        if ($request->user()->hasRole(RoleSlug::Teacher)) {
            $query->where(function ($scope) use ($request): void {
                $scope->where('teacher_id', $request->user()->id)
                    ->orWhereHas('teacher.roles', fn ($roleQuery) => $roleQuery->where('slug', RoleSlug::Coach->value));
            });
        }
        if ($request->user()->hasRole(RoleSlug::Coach)) {
            $query->where('teacher_id', $request->user()->id);
        }
        if ($request->user()->hasAnyRole([RoleSlug::SuperAdmin, RoleSlug::Admin, RoleSlug::Principal])) {
            $query->where('status', 'verified');
        }

        return $query->when($filters['academic_year_id'] ?? null, fn ($q, $v) => $q->where('academic_year_id', $v))
            ->when($filters['month'] ?? null, fn ($q, $v) => $q->whereYear('activity_date', substr($v, 0, 4))->whereMonth('activity_date', substr($v, 5, 2)))
            ->when($filters['teacher_id'] ?? null, fn ($q, $v) => $q->where('teacher_id', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v));
    }
}
