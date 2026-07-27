<?php

namespace App\Services;

use App\Enums\RoleSlug;
use App\Enums\UserStatus;
use App\Models\AcademicYear;
use App\Models\DocumentResource;
use App\Models\RegistrationCode;
use App\Models\SchoolClass;
use App\Models\User;

class AdminDashboardService
{
    public function build(): array
    {
        $activeYear = AcademicYear::query()->where('is_active', true)->first();
        $activeBatchId = request()->user() ? app(ProgramContextService::class)->activeBatchId(request()->user()) : null;
        $studentQuery = User::query()
            ->whereHas('roles', fn ($query) => $query->where('slug', RoleSlug::Student))
            ->when($activeBatchId, fn ($query, int $batchId) => $query->whereHas('classMemberships', fn ($membership) => $membership->where('program_batch_id', $batchId)));
        $staffRoles = collect(RoleSlug::staffRoles())->map->value;

        $classes = SchoolClass::query()
            ->when($activeYear, fn ($query) => $query->where('academic_year_id', $activeYear->id))
            ->when($activeBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))
            ->with(['academicYear:id,name', 'homeroomTeacher:id,name'])
            ->withCount('studentProfiles')
            ->orderBy('grade_level')
            ->orderBy('name')
            ->get();

        $availableCodes = RegistrationCode::query()
            ->available()
            ->when($activeBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))
            ->where(fn ($query) => $query->whereNull('max_uses')->orWhereColumn('used_count', '<', 'max_uses'));

        return [
            'activeYear' => $activeYear,
            'kpis' => [
                'active_students' => (clone $studentQuery)->where('status', UserStatus::Active)->count(),
                'onboarding_students' => (clone $studentQuery)->where('status', UserStatus::Onboarding)->count(),
                'active_staff' => User::query()->where('status', UserStatus::Active)->whereHas('roles', fn ($query) => $query->whereIn('slug', $staffRoles))->count(),
                'active_classes' => $classes->where('is_active', true)->count(),
                'available_codes' => (clone $availableCodes)->count(),
                'active_documents' => DocumentResource::query()->published()->when($activeYear, fn ($query) => $query->where('academic_year_id', $activeYear->id))->when($activeBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId))->count(),
            ],
            'recentStudents' => (clone $studentQuery)
                ->with('studentProfile.schoolClass:id,name')
                ->latest()
                ->limit(6)
                ->get(),
            'classes' => $classes,
            'registrationCodes' => (clone $availableCodes)
                ->with(['academicYear:id,name', 'schoolClass:id,name'])
                ->orderByRaw('expires_at IS NULL')
                ->orderBy('expires_at')
                ->limit(6)
                ->get(),
            'alerts' => collect([
                $activeYear === null ? 'Tahun ajaran aktif belum ditetapkan.' : null,
                $classes->whereNull('homeroom_teacher_id')->isNotEmpty() ? 'Koordinator kelompok/angkatan belum ditetapkan.' : null,
                $classes->where('is_active', true)->filter(fn ($class) => $class->student_profiles_count > $class->capacity)->isNotEmpty() ? 'Kelompok/angkatan melebihi kapasitas.' : null,
            ])->filter()->values(),
        ];
    }
}
