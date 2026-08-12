<?php

namespace App\Http\Controllers;

use App\Enums\ReportType;
use App\Enums\RoleSlug;
use App\Http\Requests\ReportFilterRequest;
use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\ClassStudent;
use App\Models\LearningSession;
use App\Models\Report;
use App\Models\SchoolClass;
use App\Models\User;
use App\Services\ProgramContextService;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Report::class);
        $types = collect(ReportType::cases())->filter(fn ($type) => $request->user()->can('viewType', [Report::class, $type]));

        return view('reports.index', compact('types'));
    }

    public function show(ReportFilterRequest $request, string $type, ReportService $service): View
    {
        return $this->render($request, ReportType::from($type), $service, false);
    }

    public function print(ReportFilterRequest $request, string $type, ReportService $service): View
    {
        return $this->render($request, ReportType::from($type), $service, true);
    }

    public function exportCsv(ReportFilterRequest $request, string $type, ReportService $service): StreamedResponse
    {
        $type = ReportType::from($type);
        $context = $this->reportContext($request);
        $filters = $this->filters($request, $context);
        $export = $service->export($type, $filters, $request->user());
        $filename = str('laporan-'.$type->value.'-'.($context['selectedProgramBatch']?->slug ?? 'semua-program').'-'.($filters['year'] ?? 'semua'))->slug().'.csv';

        return response()->streamDownload(function () use ($export): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['No', ...array_values($export['columns'])]);
            foreach ($export['items'] as $index => $row) {
                fputcsv($handle, [$index + 1, ...array_map(fn ($key) => $row[$key] ?? '', array_keys($export['columns']))]);
            }
            if ($export['truncated']) {
                fputcsv($handle, []);
                fputcsv($handle, ['Catatan', 'Export dibatasi 5000 baris pertama. Persempit filter untuk mengambil data penuh.']);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function matrix(ReportFilterRequest $request, string $type): View
    {
        $type = ReportType::from($type);
        abort_unless($type === ReportType::Attendance, 404);
        $context = $this->reportContext($request);
        $activeBatchId = $context['programBatchId'];
        $programContext = $context['programContext'];

        $years = $programContext->academicYearsForBatch($context['selectedProgramBatch']);
        $activeYear = $years->firstWhere('is_active', true) ?? $years->first();
        $filters = $this->filters($request, ['activeYear' => $activeYear, ...$context]);
        $classes = SchoolClass::query()->when($filters['year'], fn ($q, $year) => $q->where('academic_year_id', $year))->when($activeBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))->orderBy('grade_level')->orderBy('name')->get();
        $selectedYear = $years->firstWhere('id', (int) $filters['year']);
        $selectedClass = $filters['class'] ? $classes->firstWhere('id', (int) $filters['class']) : null;
        $matrixProgramBatchId = $activeBatchId;
        $sessions = LearningSession::query()
            ->where('academic_year_id', $filters['year'])
            ->when($matrixProgramBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))
            ->when($filters['semester'] ?? null, fn ($query, $semester) => $query->where('semester', $semester))
            ->orderBy('session_number')
            ->get(['id', 'session_number', 'title']);
        $students = User::query()
            ->whereHas('classMemberships', function ($query) use ($filters, $matrixProgramBatchId): void {
                $query->where('academic_year_id', $filters['year'])
                    ->when($matrixProgramBatchId, fn ($item, int $batchId) => $item->where('program_batch_id', $batchId))
                    ->when($filters['class'] ?? null, fn ($item, $id) => $item->where('class_id', $id));
            })
            ->with(['classMemberships' => fn ($query) => $query->where('academic_year_id', $filters['year'])->when($matrixProgramBatchId, fn ($item, int $batchId) => $item->where('program_batch_id', $batchId))->with('schoolClass:id,name,grade_level')])
            ->orderBy('name')
            ->get();
        $records = AttendanceRecord::query()
            ->with('attendanceSession.learningSession:id,session_number')
            ->whereIn('user_id', $students->pluck('id'))
            ->whereHas('attendanceSession', fn ($query) => $query
                ->where('academic_year_id', $filters['year'])
                ->when($matrixProgramBatchId, fn ($item, int $batchId) => $item->where('program_batch_id', $batchId))
                ->when($filters['class'] ?? null, fn ($item, $id) => $item->where('class_id', $id))
                ->when($filters['semester'] ?? null, fn ($item, $semester) => $item->whereHas('learningSession', fn ($session) => $session->where('semester', $semester)))
                ->when($filters['date_from'] ?? null, fn ($item, $date) => $item->whereDate('attendance_date', '>=', $date))
                ->when($filters['date_to'] ?? null, fn ($item, $date) => $item->whereDate('attendance_date', '<=', $date)))
            ->get()
            ->groupBy('user_id')
            ->map(fn ($studentRecords) => $studentRecords->keyBy('attendance_session_id'));
        $activeStudentCount = ClassStudent::query()
            ->where('academic_year_id', $filters['year'])
            ->when($matrixProgramBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))
            ->when($filters['class'] ?? null, fn ($query, $class) => $query->where('class_id', $class))
            ->where('status', 'active')
            ->count();

        return view('reports.attendance-matrix', [
            'type' => $type,
            'years' => $years,
            'classes' => $classes,
            'filters' => $filters,
            'selectedYear' => $selectedYear,
            'selectedClass' => $selectedClass,
            'sessions' => $sessions,
            'students' => $students,
            'records' => $records,
            'activeStudentCount' => $activeStudentCount,
            'participantLabel' => $filters['participant_label'],
            'groupLabel' => $filters['group_label'],
            'periodLabel' => $filters['period_label'],
        ]);
    }

    private function render(ReportFilterRequest $request, ReportType $type, ReportService $service, bool $print): View
    {
        $context = $this->reportContext($request);
        $activeBatchId = $context['programBatchId'];
        $programContext = $context['programContext'];
        $years = $programContext->academicYearsForBatch($context['selectedProgramBatch']);
        $activeYear = $years->firstWhere('is_active', true) ?? $years->first();
        $filters = $this->filters($request, ['activeYear' => $activeYear, ...$context]);
        $classes = SchoolClass::query()->when($filters['year'], fn ($q, $year) => $q->where('academic_year_id', $year))->when($activeBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))->orderBy('name')->get();
        $report = $service->build($type, $filters, $request->user(), $print);
        $view = $print ? 'reports.print' : 'reports.show';

        return view($view, [...$report, 'years' => $years, 'classes' => $classes, 'selectedYear' => $years->firstWhere('id', (int) $filters['year']), 'programBatches' => $context['programBatches'], 'selectedProgramBatch' => $context['selectedProgramBatch']]);
    }

    /** @return array<string, mixed> */
    private function reportContext(ReportFilterRequest $request): array
    {
        $programContext = app(ProgramContextService::class);
        $user = $request->user();
        $availableBatches = $programContext->availableBatches($user);
        $activeBatch = $programContext->activeBatch($user);
        $requestedBatchId = $request->filled('program_batch_id') ? $request->integer('program_batch_id') : null;
        $selectedProgramBatch = $activeBatch;

        if ($requestedBatchId && $availableBatches->contains('id', $requestedBatchId)) {
            $selectedProgramBatch = $availableBatches->firstWhere('id', $requestedBatchId);
        }

        return [
            'programContext' => $programContext,
            'programBatchId' => $selectedProgramBatch?->id,
            'programBatches' => $availableBatches->where('id', $selectedProgramBatch?->id)->values(),
            'selectedProgramBatch' => $selectedProgramBatch,
        ];
    }

    /** @param array<string, mixed> $context */
    private function filters(ReportFilterRequest $request, array $context): array
    {
        $programContext = $context['programContext'];
        $activeYear = $context['activeYear'] ?? AcademicYear::query()->where('is_active', true)->first();
        $selectedProgramBatch = $context['selectedProgramBatch'] ?? null;
        $user = $request->user();

        return array_merge([
            'year' => $activeYear?->id,
            'class' => null,
            'semester' => null,
            'date_from' => null,
            'date_to' => null,
            'program_batch_id' => $context['programBatchId'],
            'participant_label' => $selectedProgramBatch?->participant_label ?? $programContext->participantLabel($user),
            'group_label' => $selectedProgramBatch ? 'Kelompok/Angkatan' : $programContext->groupLabel($user),
            'period_label' => $selectedProgramBatch?->period_label ? 'Periode Program' : $programContext->periodLabel($user),
            'viewer_is_leadership' => $user->hasAnyRole([RoleSlug::SuperAdmin, RoleSlug::Admin, RoleSlug::Principal]),
        ], $request->validated(), ['program_batch_id' => $context['programBatchId']]);
    }
}
