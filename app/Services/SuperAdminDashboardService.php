<?php

namespace App\Services;

use App\Enums\UserStatus;
use App\Models\AcademicYear;
use App\Models\AuthenticationLog;
use App\Models\ClassStudent;
use App\Models\DocumentResource;
use App\Models\Institution;
use App\Models\LearningSession;
use App\Models\Program;
use App\Models\ProgramBatch;
use App\Models\RegistrationCode;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Foundation\Application;

class SuperAdminDashboardService
{
    public function build(): array
    {
        $roleCounts = Role::query()->withCount('users')->orderBy('id')->get();
        $activeYear = AcademicYear::query()->where('is_active', true)->first();
        $recentAuthentication = AuthenticationLog::query()->with('user:id,name')->latest('created_at')->limit(8)->get();
        $programBatchSummaries = ProgramBatch::query()
            ->with(['program', 'institution'])
            ->where('is_active', true)
            ->withCount([
                'classes as active_groups_count' => fn ($query) => $query->where('is_active', true),
                'registrationCodes as available_codes_count' => fn ($query) => $query
                    ->available()
                    ->where(fn ($query) => $query->whereNull('max_uses')->orWhereColumn('used_count', '<', 'max_uses')),
                'learningSessions as learning_sessions_count',
            ])
            ->orderBy('name')
            ->get()
            ->map(function (ProgramBatch $batch): array {
                $activeParticipants = ClassStudent::query()
                    ->where('program_batch_id', $batch->id)
                    ->where('status', 'active')
                    ->distinct('user_id')
                    ->count('user_id');

                return [
                    'batch' => $batch,
                    'participants' => $activeParticipants,
                    'groups' => $batch->active_groups_count,
                    'sessions' => $batch->learning_sessions_count,
                    'codes' => $batch->available_codes_count,
                ];
            });

        return [
            'activeYear' => $activeYear,
            'kpis' => [
                'users' => User::query()->count(),
                'programs' => Program::query()->where('is_active', true)->count(),
                'institutions' => Institution::query()->where('is_active', true)->count(),
                'program_batches' => ProgramBatch::query()->where('is_active', true)->count(),
                'active_users' => User::query()->where('status', UserStatus::Active)->count(),
                'onboarding' => User::query()->where('status', UserStatus::Onboarding)->count(),
                'suspended' => User::query()->where('status', UserStatus::Suspended)->count(),
                'classes' => SchoolClass::query()->where('is_active', true)->count(),
                'sessions' => LearningSession::query()->when($activeYear, fn ($query) => $query->where('academic_year_id', $activeYear->id))->count(),
                'documents' => DocumentResource::query()->published()->count(),
                'codes' => RegistrationCode::query()->available()->where(fn ($query) => $query->whereNull('max_uses')->orWhereColumn('used_count', '<', 'max_uses'))->count(),
                'auth_rejections' => AuthenticationLog::query()->where('created_at', '>=', now()->subDay())->where(fn ($query) => $query->where('event', 'like', 'rejected_%')->orWhere('event', 'provider_error'))->count(),
            ],
            'roleCounts' => $roleCounts,
            'programBatchSummaries' => $programBatchSummaries,
            'recentAuthentication' => $recentAuthentication,
            'system' => [
                'environment' => app()->environment(),
                'debug' => config('app.debug'),
                'laravel' => Application::VERSION,
                'php' => PHP_VERSION,
                'database' => config('database.default'),
                'session' => config('session.driver'),
                'session_encrypted' => config('session.encrypt'),
                'cache' => config('cache.default'),
                'queue' => config('queue.default'),
            ],
        ];
    }
}
