<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RoleSlug;
use App\Enums\StudentExitReason;
use App\Enums\StudentMembershipStatus;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DeactivateStudentRequest;
use App\Http\Requests\Admin\StudentIndexRequest;
use App\Models\AttendanceRecord;
use App\Models\ClassStudent;
use App\Models\DiscussionPost;
use App\Models\PortfolioItem;
use App\Models\RegistrationCode;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\StudentLearningProgress;
use App\Models\StudentOnboardingResponse;
use App\Models\StudentProfile;
use App\Models\Submission;
use App\Models\User;
use App\Services\ProgramContextService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(StudentIndexRequest $request): View
    {
        $filters = $request->validated();
        $activeBatchId = app(ProgramContextService::class)->activeBatchId($request->user());
        $students = User::query()
            ->with([
                'studentProfile' => fn ($query) => $query->withTrashed()->with('schoolClass.academicYear'),
                'onboardingResponse',
                'roles:id,slug',
            ])
            ->withTrashed()
            ->where(fn (Builder $query) => $query
                ->where('status', UserStatus::Onboarding->value)
                ->orWhere('status', UserStatus::Archived->value)
                ->orWhereHas('roles', fn (Builder $query) => $query->where('slug', RoleSlug::Student->value)))
            ->when($activeBatchId, fn (Builder $query, int $batchId) => $query->where(fn (Builder $query) => $query
                ->whereHas('studentProfile', fn (Builder $profile) => $profile->where('program_batch_id', $batchId))
                ->orWhereHas('classMemberships', fn (Builder $membership) => $membership->where('program_batch_id', $batchId))))
            ->when($filters['q'] ?? null, function (Builder $query, string $search): void {
                $query->where(fn (Builder $query) => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('studentProfile', fn (Builder $query) => $query
                        ->where('student_number', 'like', "%{$search}%")
                        ->orWhere('school_class_name', 'like', "%{$search}%")));
            })
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['class_id'] ?? null, fn (Builder $query, int|string $classId) => $query->whereHas('studentProfile', fn (Builder $query) => $query->where('class_id', $classId)))
            ->when($filters['grade_level'] ?? null, fn (Builder $query, int|string $grade) => $query->whereHas('studentProfile', fn (Builder $query) => $query->where('grade_level', $grade)))
            ->when($filters['interest'] ?? null, fn (Builder $query, string $interest) => $query->whereHas('onboardingResponse', fn (Builder $query) => $query->whereJsonContains('interests', $interest)))
            ->when(($filters['onboarding'] ?? null) === 'complete', fn (Builder $query) => $query->whereHas('onboardingResponse', fn (Builder $query) => $query->whereNotNull('completed_at')))
            ->when(($filters['onboarding'] ?? null) === 'incomplete', fn (Builder $query) => $query->where(fn (Builder $query) => $query->whereDoesntHave('onboardingResponse')->orWhereHas('onboardingResponse', fn (Builder $query) => $query->whereNull('completed_at'))))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.students.index', [
            'students' => $students,
            'classes' => SchoolClass::query()->with('academicYear:id,name')->when($activeBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))->orderBy('name')->get(),
            'filters' => $filters,
        ]);
    }

    public function show(User $student): View
    {
        $this->authorize('viewStudents', User::class);
        abort_unless($student->status === UserStatus::Onboarding || $student->status === UserStatus::Archived || $student->hasRole(RoleSlug::Student), 404);
        $this->ensureStudentInActiveProgram($student);

        $student->load([
            'studentProfile' => fn ($query) => $query->withTrashed()->with('schoolClass.academicYear'),
            'onboardingResponse.registrationCode',
            'classMemberships.schoolClass',
            'authenticationLogs' => fn ($query) => $query->latest('created_at')->limit(10),
        ]);

        return view('admin.students.show', compact('student'));
    }

    public function suspend(User $student): RedirectResponse
    {
        $this->authorize('changeStudentStatus', User::class);
        abort_unless($student->hasRole(RoleSlug::Student), 422);
        $this->ensureStudentInActiveProgram($student);

        DB::transaction(function () use ($student): void {
            $student->update(['status' => UserStatus::Suspended]);
            $student->studentProfile?->update(['membership_status' => StudentMembershipStatus::Suspended]);
            $student->classMemberships()->update(['status' => StudentMembershipStatus::Suspended->value]);
        });

        return back()->with('success', 'Peserta berhasil ditangguhkan.');
    }

    public function deactivate(DeactivateStudentRequest $request, User $student): RedirectResponse
    {
        abort_unless($student->hasRole(RoleSlug::Student) && $student->status === UserStatus::Active, 422);
        $this->ensureStudentInActiveProgram($student);
        $data = $request->validated();

        DB::transaction(function () use ($data, $student): void {
            $student->update(['status' => UserStatus::Inactive]);
            $student->studentProfile?->update(['membership_status' => StudentMembershipStatus::Inactive]);
            $student->classMemberships()
                ->where('status', StudentMembershipStatus::Active->value)
                ->latest('academic_year_id')
                ->first()?->update([
                    'status' => StudentMembershipStatus::Inactive->value,
                    'left_at' => now(),
                    'exit_reason' => $data['exit_reason'],
                    'exit_notes' => $data['exit_notes'] ?? null,
                ]);
        });

        $reason = StudentExitReason::from($data['exit_reason'])->label();

        return back()->with('success', "Keanggotaan peserta dinonaktifkan: {$reason}.");
    }

    public function activate(User $student): RedirectResponse
    {
        $this->authorize('changeStudentStatus', User::class);
        abort_unless($student->hasRole(RoleSlug::Student) && $student->onboardingResponse?->completed_at !== null, 422);
        $this->ensureStudentInActiveProgram($student);

        DB::transaction(function () use ($student): void {
            $student->update(['status' => UserStatus::Active]);
            $student->studentProfile?->update(['membership_status' => StudentMembershipStatus::Active]);
            $student->classMemberships()
                ->whereIn('status', [StudentMembershipStatus::Inactive->value, StudentMembershipStatus::Suspended->value])
                ->latest('academic_year_id')
                ->first()?->update([
                    'status' => StudentMembershipStatus::Active->value,
                    'left_at' => null,
                    'exit_reason' => null,
                    'exit_notes' => null,
                ]);
        });

        return back()->with('success', 'Peserta berhasil diaktifkan.');
    }

    public function resetOnboarding(User $student): RedirectResponse
    {
        $this->authorize('changeStudentStatus', User::class);
        abort_unless($student->status === UserStatus::Archived || $student->trashed() || $student->status === UserStatus::Onboarding, 422);
        $this->ensureStudentInActiveProgram($student);
        abort_if($this->hasLearningHistory($student), 422, 'Peserta sudah memiliki riwayat belajar, tugas, presensi, forum, atau portofolio. Gunakan nonaktif/arsip agar histori tetap aman.');

        DB::transaction(function () use ($student): void {
            $student->restore();
            $student->forceFill([
                'status' => UserStatus::Onboarding,
                'password' => null,
            ])->save();

            $studentRoleId = Role::query()->where('slug', RoleSlug::Student->value)->value('id');

            if ($studentRoleId !== null) {
                $student->roles()->detach($studentRoleId);
            }

            StudentProfile::withTrashed()->where('user_id', $student->id)->forceDelete();
            StudentOnboardingResponse::query()->where('user_id', $student->id)->delete();
            ClassStudent::query()->where('user_id', $student->id)->delete();
        });

        return redirect()->route('admin.students.show', $student)->with('success', 'Akun peserta dikembalikan ke onboarding. Peserta dapat login Google lalu masuk halaman kode pendaftaran lagi.');
    }

    public function purgeTest(User $student): RedirectResponse
    {
        $this->authorize('changeStudentStatus', User::class);
        abort_unless($student->status === UserStatus::Onboarding || $student->status === UserStatus::Archived || $student->trashed(), 422);
        $this->ensureStudentInActiveProgram($student);
        abort_if($this->hasLearningHistory($student), 422, 'Data ini bukan lagi data test kosong karena sudah memiliki riwayat belajar/tugas/presensi/forum/portofolio.');

        DB::transaction(function () use ($student): void {
            $response = StudentOnboardingResponse::query()->where('user_id', $student->id)->first();

            if ($response?->completed_at !== null && $response->registration_code_id !== null) {
                RegistrationCode::query()
                    ->whereKey($response->registration_code_id)
                    ->where('used_count', '>', 0)
                    ->decrement('used_count');
            }

            StudentProfile::withTrashed()->where('user_id', $student->id)->forceDelete();
            StudentOnboardingResponse::query()->where('user_id', $student->id)->delete();
            ClassStudent::query()->where('user_id', $student->id)->delete();
            $student->roles()->detach();
            $student->forceDelete();
        });

        return redirect()->route('admin.students.index')->with('success', 'Data peserta test berhasil dihapus permanen.');
    }

    public function destroy(User $student): RedirectResponse
    {
        $this->authorize('changeStudentStatus', User::class);
        abort_unless($student->status === UserStatus::Onboarding || $student->hasRole(RoleSlug::Student), 404);
        $this->ensureStudentInActiveProgram($student);

        DB::transaction(function () use ($student): void {
            $student->update(['status' => UserStatus::Archived]);
            $student->studentProfile?->update(['membership_status' => StudentMembershipStatus::Archived]);
            $student->classMemberships()->update(['status' => StudentMembershipStatus::Archived->value]);
            $student->studentProfile?->delete();
            $student->delete();
        });

        return redirect()->route('admin.students.index')->with('success', 'Data peserta berhasil diarsipkan.');
    }

    private function hasLearningHistory(User $student): bool
    {
        return StudentLearningProgress::query()->where('user_id', $student->id)->exists()
            || AttendanceRecord::query()->where('user_id', $student->id)->exists()
            || Submission::query()->where('user_id', $student->id)->exists()
            || PortfolioItem::query()->where('user_id', $student->id)->exists()
            || DiscussionPost::query()->where('user_id', $student->id)->exists();
    }

    private function ensureStudentInActiveProgram(User $student): void
    {
        $viewer = request()->user();

        if ($viewer?->hasRole(RoleSlug::SuperAdmin)) {
            return;
        }

        $activeBatchId = app(ProgramContextService::class)->activeBatchId($viewer);

        if ($activeBatchId === null) {
            return;
        }

        $student->loadMissing(['studentProfile', 'classMemberships', 'onboardingResponse.registrationCode']);
        $programIds = collect([
            $student->studentProfile?->program_batch_id,
            $student->onboardingResponse?->registrationCode?->program_batch_id,
        ])
            ->merge($student->classMemberships->pluck('program_batch_id'))
            ->filter()
            ->unique()
            ->values();

        abort_if($programIds->isNotEmpty() && ! $programIds->contains($activeBatchId), 403);
    }
}
