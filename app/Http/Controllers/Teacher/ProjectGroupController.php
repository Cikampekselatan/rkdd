<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\RoleSlug;
use App\Enums\StudentMembershipStatus;
use App\Http\Controllers\Controller;
use App\Models\ClassStudent;
use App\Models\GroupProject;
use App\Models\GroupProjectAssessment;
use App\Models\ProjectGroup;
use App\Models\SchoolClass;
use App\Models\User;
use App\Services\ProgramContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProjectGroupController extends Controller
{
    public function index(): View
    {
        $activeBatchId = app(ProgramContextService::class)->activeBatchId(request()->user());
        $groups = ProjectGroup::query()
            ->with(['academicYear:id,name', 'schoolClass:id,name'])
            ->withCount(['activeMembers', 'projects'])
            ->when($activeBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))
            ->latest()
            ->paginate(12);

        return view('teacher.project-groups.index', compact('groups'));
    }

    public function create(): View
    {
        $programContext = app(ProgramContextService::class);

        return view('teacher.project-groups.form', [
            'group' => new ProjectGroup,
            'activeBatch' => $programContext->activeBatch(request()->user()),
            'groupLabel' => $programContext->groupLabel(request()->user()),
            'participantLabel' => $programContext->participantLabel(request()->user()),
            ...$this->formData(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedGroup($request);
        $data['program_batch_id'] = SchoolClass::query()->whereKey($data['class_id'])->value('program_batch_id') ?? app(ProgramContextService::class)->activeBatchId($request->user());

        $group = DB::transaction(function () use ($data, $request): ProjectGroup {
            $group = ProjectGroup::query()->create([
                ...collect($data)->except('member_ids')->all(),
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]);

            $this->syncMembers($group, $data['member_ids']);

            return $group;
        });

        return redirect()->route('teacher.project-groups.show', $group)->with('success', 'Kelompok proyek berhasil dibuat.');
    }

    public function show(ProjectGroup $projectGroup): View
    {
        abort_unless($this->isInActiveProgram($projectGroup), 403);
        $projectGroup->load([
            'academicYear:id,name',
            'schoolClass:id,name',
            'members.student:id,name,email',
            'projects.assessment',
        ]);

        return view('teacher.project-groups.show', [
            'group' => $projectGroup,
            'project' => new GroupProject,
        ]);
    }

    public function edit(ProjectGroup $projectGroup): View
    {
        abort_unless($this->isInActiveProgram($projectGroup), 403);
        $projectGroup->load('members');
        $programContext = app(ProgramContextService::class);

        return view('teacher.project-groups.form', [
            'group' => $projectGroup,
            'activeBatch' => $programContext->activeBatch(request()->user()),
            'groupLabel' => $programContext->groupLabel(request()->user()),
            'participantLabel' => $programContext->participantLabel(request()->user()),
            ...$this->formData(),
        ]);
    }

    public function update(Request $request, ProjectGroup $projectGroup): RedirectResponse
    {
        abort_unless($this->isInActiveProgram($projectGroup), 403);
        $data = $this->validatedGroup($request, $projectGroup);
        $data['program_batch_id'] = SchoolClass::query()->whereKey($data['class_id'])->value('program_batch_id') ?? $projectGroup->program_batch_id ?? app(ProgramContextService::class)->activeBatchId($request->user());

        DB::transaction(function () use ($data, $request, $projectGroup): void {
            $projectGroup->update([
                ...collect($data)->except('member_ids')->all(),
                'updated_by' => $request->user()->id,
            ]);

            $this->syncMembers($projectGroup, $data['member_ids']);
        });

        return redirect()->route('teacher.project-groups.show', $projectGroup)->with('success', 'Kelompok proyek berhasil diperbarui.');
    }

    public function storeProject(Request $request, ProjectGroup $projectGroup): RedirectResponse
    {
        abort_unless($this->isInActiveProgram($projectGroup), 403);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:5000'],
            'evidence_url' => ['nullable', 'url', 'max:255'],
            'due_at' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['active', 'completed', 'archived'])],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $projectGroup->projects()->create([
            ...$data,
            'is_published' => (bool) ($data['is_published'] ?? false),
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Proyek kelompok berhasil ditambahkan.');
    }

    public function editAssessment(GroupProject $groupProject): View
    {
        abort_unless($this->isInActiveProgram($groupProject->projectGroup), 403);
        $groupProject->load(['projectGroup.members.student:id,name,email', 'assessment']);

        return view('teacher.project-groups.assessment', [
            'project' => $groupProject,
            'assessment' => $groupProject->assessment ?? new GroupProjectAssessment(['final_score' => 0, 'achievement_level' => 1]),
        ]);
    }

    public function updateAssessment(Request $request, GroupProject $groupProject): RedirectResponse
    {
        abort_unless($this->isInActiveProgram($groupProject->projectGroup), 403);
        $data = $request->validate([
            'final_score' => ['required', 'numeric', 'min:0', 'max:100'],
            'feedback' => ['nullable', 'string', 'max:5000'],
            'private_note' => ['nullable', 'string', 'max:5000'],
            'is_published' => ['nullable', 'boolean'],
        ]);
        $isPublished = (bool) ($data['is_published'] ?? false);
        $score = (float) $data['final_score'];

        $groupProject->assessment()->updateOrCreate(
            ['group_project_id' => $groupProject->id],
            [
                ...$data,
                'achievement_level' => GroupProjectAssessment::achievementLevel($score),
                'is_published' => $isPublished,
                'published_at' => $isPublished ? now() : null,
                'assessed_by' => $request->user()->id,
            ],
        );

        return redirect()->route('teacher.project-groups.show', $groupProject->project_group_id)->with('success', $isPublished ? 'Nilai kelompok dipublikasikan ke semua anggota.' : 'Draf nilai kelompok disimpan.');
    }

    private function validatedGroup(Request $request, ?ProjectGroup $group = null): array
    {
        $data = $request->validate([
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'class_id' => ['required', 'exists:classes,id'],
            'name' => ['required', 'string', 'max:120', Rule::unique('project_groups', 'name')->where('academic_year_id', $request->input('academic_year_id'))->ignore($group?->id)],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::in(['active', 'completed', 'archived'])],
            'member_ids' => ['required', 'array', 'min:1'],
            'member_ids.*' => ['integer', 'distinct', 'exists:users,id'],
        ]);
        $class = SchoolClass::query()->find($data['class_id']);
        $programContext = app(ProgramContextService::class);
        $activeBatchId = $programContext->activeBatchId($request->user());
        $allowedYearIds = $programContext->academicYears($request->user(), ['id'])->pluck('id');

        if (! $allowedYearIds->contains((int) $data['academic_year_id'])) {
            throw ValidationException::withMessages(['academic_year_id' => 'Tahun ajaran harus berasal dari program aktif.']);
        }

        if ($class && $class->academic_year_id !== (int) $data['academic_year_id']) {
            throw ValidationException::withMessages(['class_id' => 'Kelompok harus berasal dari tahun ajaran yang dipilih.']);
        }

        if ($activeBatchId && $class?->program_batch_id && $class->program_batch_id !== $activeBatchId) {
            throw ValidationException::withMessages(['class_id' => 'Kelompok harus berasal dari program aktif.']);
        }

        $activeMemberCount = ClassStudent::query()
            ->where('academic_year_id', $data['academic_year_id'])
            ->where('class_id', $data['class_id'])
            ->when($class?->program_batch_id, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))
            ->where('status', StudentMembershipStatus::Active->value)
            ->whereIn('user_id', $data['member_ids'])
            ->count();

        if ($activeMemberCount !== count($data['member_ids'])) {
            $participantLabel = app(ProgramContextService::class)->participantLabel($request->user());
            $groupLabel = app(ProgramContextService::class)->groupLabel($request->user());

            throw ValidationException::withMessages(['member_ids' => "Semua anggota harus {$participantLabel} aktif pada {$groupLabel} dan periode yang dipilih."]);
        }

        return $data;
    }

    private function syncMembers(ProjectGroup $group, array $memberIds): void
    {
        $payload = collect($memberIds)->mapWithKeys(fn (int|string $id): array => [
            (int) $id => ['joined_at' => now()->toDateString(), 'is_active' => true],
        ])->all();

        $group->students()->sync($payload);
    }

    private function formData(): array
    {
        $programContext = app(ProgramContextService::class);
        $activeBatchId = $programContext->activeBatchId(request()->user());

        return [
            'academicYears' => $programContext->academicYears(request()->user()),
            'classes' => SchoolClass::with('academicYear:id,name')->when($activeBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))->orderBy('name')->get(),
            'students' => User::query()
                ->whereHas('roles', fn ($query) => $query->where('slug', RoleSlug::Student->value))
                ->whereHas('classMemberships', fn ($query) => $query->when($activeBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))->where('status', StudentMembershipStatus::Active->value))
                ->with('studentProfile.schoolClass.academicYear')
                ->orderBy('name')
                ->get(),
        ];
    }

    private function isInActiveProgram(ProjectGroup $group): bool
    {
        $activeBatchId = app(ProgramContextService::class)->activeBatchId(request()->user());

        return $group->program_batch_id === null || $activeBatchId === null || $group->program_batch_id === $activeBatchId;
    }
}
