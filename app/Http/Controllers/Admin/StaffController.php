<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RoleSlug;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreStaffRequest;
use App\Http\Requests\Admin\UpdateStaffRequest;
use App\Models\Role;
use App\Models\Program;
use App\Models\User;
use App\Services\ProgramContextService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function __construct(private readonly ProgramContextService $programContext) {}

    public function index(): View
    {
        $this->authorize('manageStaff', User::class);

        return view('admin.staff.index', [
            'staffMembers' => User::query()
                ->with(['roles:id,name,slug', 'teacherProfile', 'assignedProgramBatches.program', 'assignedProgramBatches.institution'])
                ->whereHas('roles', fn ($query) => $query->whereIn('slug', array_map(
                    fn (RoleSlug $role): string => $role->value,
                    RoleSlug::staffRoles(),
                )))
                ->whereDoesntHave('roles', fn ($query) => $query->where('slug', RoleSlug::SuperAdmin->value))
                ->latest()
                ->paginate(15),
        ]);
    }

    public function create(): View
    {
        $this->authorize('manageStaff', User::class);

        return view('admin.staff.create', [
            'programBatches' => $this->programContext->availableBatches($this->activeUser()),
            'programsWithoutActiveBatches' => $this->programsWithoutActiveBatches(),
            'assignedProgramBatchIds' => [],
        ]);
    }

    public function store(StoreStaffRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $data = $request->validated();
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'email_verified_at' => now(),
                'status' => $data['is_active'] ? UserStatus::Active : UserStatus::Suspended,
            ]);
            $role = Role::query()->where('slug', $data['role'])->firstOrFail();
            $user->roles()->attach($role);
            $user->teacherProfile()->create([
                'employee_number' => $data['employee_number'] ?? null,
                'phone' => $data['phone'] ?? null,
                'specialization' => $data['specialization'] ?? null,
                'bio' => $data['bio'] ?? null,
                'is_active' => $data['is_active'],
            ]);
            $user->assignedProgramBatches()->syncWithPivotValues(
                $data['program_batch_ids'],
                ['assigned_by' => $this->activeUser()->id],
            );
        });

        return redirect()->route('admin.staff.index')->with('success', 'Akun staff berhasil dibuat.');
    }

    public function edit(User $staff): View
    {
        $this->authorize('manageStaff', User::class);
        abort_if($staff->hasRole(RoleSlug::SuperAdmin), 403);

        $staff->load(['roles', 'teacherProfile', 'assignedProgramBatches']);

        return view('admin.staff.edit', [
            'staff' => $staff,
            'programBatches' => $this->programContext->availableBatches($this->activeUser()),
            'programsWithoutActiveBatches' => $this->programsWithoutActiveBatches(),
            'assignedProgramBatchIds' => $staff->assignedProgramBatches->pluck('id')->all(),
        ]);
    }

    public function update(UpdateStaffRequest $request, User $staff): RedirectResponse
    {
        abort_if($staff->hasRole(RoleSlug::SuperAdmin), 403);

        DB::transaction(function () use ($request, $staff): void {
            $data = $request->validated();
            $userData = [
                'name' => $data['name'],
                'email' => $data['email'],
                'status' => $data['is_active'] ? UserStatus::Active : UserStatus::Suspended,
            ];

            if (! empty($data['password'])) {
                $userData['password'] = $data['password'];
            }

            $staff->update($userData);
            $role = Role::query()->where('slug', $data['role'])->firstOrFail();
            $staff->roles()->sync([$role->id]);
            $staff->teacherProfile()->updateOrCreate(['user_id' => $staff->id], [
                'employee_number' => $data['employee_number'] ?? null,
                'phone' => $data['phone'] ?? null,
                'specialization' => $data['specialization'] ?? null,
                'bio' => $data['bio'] ?? null,
                'is_active' => $data['is_active'],
            ]);
            $staff->assignedProgramBatches()->syncWithPivotValues(
                $data['program_batch_ids'],
                ['assigned_by' => $this->activeUser()->id],
            );
        });

        return redirect()->route('admin.staff.index')->with('success', 'Akun staff berhasil diperbarui.');
    }

    public function destroy(User $staff): RedirectResponse
    {
        $this->authorize('manageStaff', User::class);
        abort_if($staff->hasRole(RoleSlug::SuperAdmin) || $staff->is(auth()->user()), 403);

        DB::transaction(function () use ($staff): void {
            $staff->teacherProfile?->delete();
            $staff->delete();
        });

        return redirect()->route('admin.staff.index')->with('success', 'Akun staff berhasil dihapus.');
    }

    private function activeUser(): User
    {
        /** @var User $user */
        $user = auth()->user();

        return $user;
    }

    private function programsWithoutActiveBatches(): Collection
    {
        if (! $this->activeUser()->hasRole(RoleSlug::SuperAdmin)) {
            return new Collection;
        }

        return Program::query()
            ->where('is_active', true)
            ->whereDoesntHave('batches', fn ($query) => $query
                ->where('is_active', true)
                ->whereHas('institution', fn ($institution) => $institution->where('is_active', true)))
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);
    }
}
