<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\StudentMembershipStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\MonthlyStudentAssessmentRequest;
use App\Models\AcademicYear;
use App\Models\ClassStudent;
use App\Models\MonthlyStudentAssessment;
use App\Models\SchoolClass;
use App\Models\User;
use App\Notifications\SkuadActivityNotification;
use App\Services\AnnouncementService;
use App\Services\ProgramContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MonthlyStudentAssessmentController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', MonthlyStudentAssessment::class);
        $context = $this->context($request);
        $academicYears = $context['academicYears'];
        $academicYear = $context['academicYear'];
        $classes = $context['classes'];
        $class = $context['class'];
        $semester = $context['semester'];

        $assessments = $this->assessmentQuery($academicYear, $class, $semester)->paginate(12)->withQueryString();
        $activeBatchId = app(ProgramContextService::class)->activeBatchId($request->user());
        $activeStudentCount = $academicYear && $class
            ? ClassStudent::query()->where('academic_year_id', $academicYear->id)->where('class_id', $class->id)->when($activeBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))->where('status', StudentMembershipStatus::Active->value)->count()
            : 0;
        $completedByMonth = MonthlyStudentAssessment::query()
            ->selectRaw('assessment_month, COUNT(*) total')
            ->when($activeBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))
            ->when($academicYear, fn ($query) => $query->where('academic_year_id', $academicYear->id))
            ->when($class, fn ($query) => $query->where('class_id', $class->id))
            ->where('semester', $semester)
            ->groupBy('assessment_month')
            ->pluck('total', 'assessment_month');

        return view('teacher.monthly-assessments.index', [
            'academicYears' => $academicYears,
            'academicYear' => $academicYear,
            'classes' => $classes,
            'class' => $class,
            'semester' => $semester,
            'assessments' => $assessments,
            'activeStudentCount' => $activeStudentCount,
            'completedByMonth' => $completedByMonth,
            'components' => MonthlyStudentAssessment::COMPONENTS,
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', MonthlyStudentAssessment::class);
        $context = $this->context($request);
        $assessments = $this->assessmentQuery($context['academicYear'], $context['class'], $context['semester'])->get();
        $yearSlug = str(str_replace('/', '-', $context['academicYear']?->name ?? 'semua'))->slug();
        $filename = 'asesmen-bulanan-'.$yearSlug.'-semester-'.$context['semester'].'.csv';

        return response()->streamDownload(function () use ($assessments): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Periode', 'Tahun Ajaran', 'Kelompok', 'Semester', 'Bulan', 'Nama Peserta', 'Email',
                'Produk/Portofolio', 'Proses/Kreativitas', 'Kolaborasi/Tanggung Jawab',
                'Presentasi/Komunikasi', 'Etika/Keamanan/Refleksi', 'Nilai Akhir', 'Level',
                'Label Capaian', 'Ringkasan Produk', 'URL Bukti', 'Kekuatan', 'Target Perbaikan',
                'Remedial', 'Pengayaan', 'Dipublikasikan',
            ]);

            foreach ($assessments as $assessment) {
                fputcsv($handle, [
                    $assessment->period_label,
                    $assessment->academicYear?->name,
                    $assessment->schoolClass?->name,
                    $assessment->semester,
                    $assessment->assessment_month,
                    $assessment->student?->name,
                    $assessment->student?->email,
                    $assessment->product_portfolio_score,
                    $assessment->process_creativity_score,
                    $assessment->collaboration_responsibility_score,
                    $assessment->presentation_communication_score,
                    $assessment->ethics_security_reflection_score,
                    $assessment->final_score,
                    $assessment->achievement_level,
                    MonthlyStudentAssessment::achievementLabel($assessment->achievement_level),
                    $assessment->product_summary,
                    $assessment->evidence_url,
                    $assessment->strengths,
                    $assessment->improvement_targets,
                    $assessment->remedial_plan,
                    $assessment->enrichment_plan,
                    $assessment->is_published ? 'Ya' : 'Tidak',
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function print(Request $request): View
    {
        $this->authorize('viewAny', MonthlyStudentAssessment::class);
        $context = $this->context($request);
        $assessments = $this->assessmentQuery($context['academicYear'], $context['class'], $context['semester'])->limit(500)->get();

        return view('teacher.monthly-assessments.print', [
            ...$context,
            'assessments' => $assessments,
            'components' => MonthlyStudentAssessment::COMPONENTS,
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', MonthlyStudentAssessment::class);

        return $this->form(new MonthlyStudentAssessment, $request);
    }

    public function store(MonthlyStudentAssessmentRequest $request): RedirectResponse
    {
        $data = $this->data($request);
        $assessment = MonthlyStudentAssessment::query()->create($data);
        $this->notifyPublishedAssessment($assessment);

        return redirect()->route('teacher.monthly-assessments.index', [
            'academic_year_id' => $assessment->academic_year_id,
            'class_id' => $assessment->class_id,
            'semester' => $assessment->semester,
        ])->with('success', 'Asesmen bulanan peserta berhasil disimpan.');
    }

    public function edit(MonthlyStudentAssessment $monthlyAssessment, Request $request): View
    {
        $this->authorize('update', $monthlyAssessment);

        return $this->form($monthlyAssessment, $request);
    }

    public function update(MonthlyStudentAssessmentRequest $request, MonthlyStudentAssessment $monthlyAssessment): RedirectResponse
    {
        $wasPublished = $monthlyAssessment->is_published;
        $monthlyAssessment->update($this->data($request));
        if (! $wasPublished) {
            $this->notifyPublishedAssessment($monthlyAssessment->refresh());
        }

        return redirect()->route('teacher.monthly-assessments.index', [
            'academic_year_id' => $monthlyAssessment->academic_year_id,
            'class_id' => $monthlyAssessment->class_id,
            'semester' => $monthlyAssessment->semester,
        ])->with('success', 'Asesmen bulanan peserta berhasil diperbarui.');
    }

    private function form(MonthlyStudentAssessment $assessment, Request $request): View
    {
        $academicYears = app(ProgramContextService::class)->academicYears($request->user(), ['id', 'name', 'starts_on', 'is_active']);
        $academicYearId = (int) old('academic_year_id', $assessment->academic_year_id ?: ($request->integer('academic_year_id') ?: $academicYears->first()?->id));
        $activeBatchId = app(ProgramContextService::class)->activeBatchId($request->user());
        $classes = SchoolClass::query()->where('academic_year_id', $academicYearId)->when($activeBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))->where('is_active', true)->orderBy('grade_level')->orderBy('name')->get(['id', 'name', 'program_batch_id']);
        $classId = (int) old('class_id', $assessment->class_id ?: ($request->integer('class_id') ?: $classes->first()?->id));
        $students = User::query()
            ->whereHas('classMemberships', fn ($query) => $query
                ->where('academic_year_id', $academicYearId)
                ->where('class_id', $classId)
                ->when($activeBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))
                ->where('status', StudentMembershipStatus::Active->value))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('teacher.monthly-assessments.form', [
            'assessment' => $assessment,
            'academicYears' => $academicYears,
            'classes' => $classes,
            'students' => $students,
            'components' => MonthlyStudentAssessment::COMPONENTS,
        ]);
    }

    private function data(MonthlyStudentAssessmentRequest $request): array
    {
        $data = $request->validated();
        $year = AcademicYear::query()->findOrFail($data['academic_year_id']);
        $programBatchId = SchoolClass::query()->whereKey($data['class_id'])->value('program_batch_id')
            ?? app(ProgramContextService::class)->activeBatchId($request->user());
        $score = MonthlyStudentAssessment::finalScoreFrom($data);

        return [
            ...$data,
            'program_batch_id' => $programBatchId,
            'period_label' => MonthlyStudentAssessment::periodLabel($year, (int) $data['semester'], (int) $data['assessment_month']),
            'final_score' => round($score, 2),
            'achievement_level' => MonthlyStudentAssessment::achievementLevel($score),
            'is_published' => $request->boolean('is_published'),
            'published_at' => $request->boolean('is_published') ? now() : null,
            'assessed_by' => $request->user()->id,
            'assessed_at' => now(),
        ];
    }

    /**
     * @return array{academicYears: Collection<int, AcademicYear>, academicYear: ?AcademicYear, classes: Collection<int, SchoolClass>, class: ?SchoolClass, semester: int}
     */
    private function context(Request $request): array
    {
        $filters = $request->validate([
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'class_id' => ['nullable', 'integer', 'exists:classes,id'],
            'semester' => ['nullable', 'integer', 'in:1,2'],
        ]);
        $programContext = app(ProgramContextService::class);
        $academicYears = $programContext->academicYears($request->user(), ['id', 'name', 'starts_on', 'is_active']);
        $activeBatchId = $programContext->activeBatchId($request->user());
        $academicYear = isset($filters['academic_year_id'])
            ? $academicYears->firstWhere('id', (int) $filters['academic_year_id'])
            : $academicYears->first();
        $classes = SchoolClass::query()
            ->when($academicYear, fn ($query) => $query->where('academic_year_id', $academicYear->id))
            ->when($activeBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))
            ->where('is_active', true)
            ->orderBy('grade_level')->orderBy('name')->get(['id', 'academic_year_id', 'name']);
        $class = isset($filters['class_id'])
            ? $classes->firstWhere('id', (int) $filters['class_id'])
            : $classes->first();

        return [
            'academicYears' => $academicYears,
            'academicYear' => $academicYear,
            'classes' => $classes,
            'class' => $class,
            'semester' => (int) ($filters['semester'] ?? 1),
        ];
    }

    private function assessmentQuery(?AcademicYear $academicYear, ?SchoolClass $class, int $semester)
    {
        return MonthlyStudentAssessment::query()
            ->with(['student:id,name,email', 'schoolClass:id,name', 'academicYear:id,name'])
            ->when(app(ProgramContextService::class)->activeBatchId(request()->user()), fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))
            ->when($academicYear, fn ($query) => $query->where('academic_year_id', $academicYear->id))
            ->when($class, fn ($query) => $query->where('class_id', $class->id))
            ->where('semester', $semester)
            ->orderBy('assessment_month')
            ->orderByDesc('final_score');
    }

    private function notifyPublishedAssessment(MonthlyStudentAssessment $assessment): void
    {
        if (! $assessment->is_published) {
            return;
        }

        $assessment->loadMissing('student');
        $assessment->student?->notify(new SkuadActivityNotification(
            'monthly_assessment',
            'Asesmen bulanan sudah tersedia',
            route('student.grades.monthly.show', $assessment),
            $assessment->period_label.' · Nilai akhir '.$assessment->final_score,
            ['monthly_assessment_id' => $assessment->id, ...AnnouncementService::programMeta($assessment->program_batch_id)],
        ));
    }
}
