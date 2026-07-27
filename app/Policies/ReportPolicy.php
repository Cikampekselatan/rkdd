<?php

namespace App\Policies;

use App\Enums\ReportType;
use App\Enums\RoleSlug;
use App\Models\User;

class ReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function viewType(User $user, string|ReportType $type): bool
    {
        $type = $type instanceof ReportType ? $type : ReportType::tryFrom($type);
        if (! $type) {
            return false;
        }
        if ($user->hasAnyRole([RoleSlug::SuperAdmin, RoleSlug::Teacher, RoleSlug::Coach])) {
            return true;
        }
        if ($user->hasRole(RoleSlug::Principal)) {
            return in_array($type, [ReportType::Attendance, ReportType::TeacherLogs, ReportType::Grades, ReportType::MonthlyAssessments, ReportType::Remedial, ReportType::Portfolio, ReportType::ActivityDocumentations, ReportType::Notes, ReportType::Sessions, ReportType::Documents], true);
        }
        if ($user->hasRole(RoleSlug::Admin)) {
            return in_array($type, [ReportType::Students, ReportType::Onboarding, ReportType::Attendance, ReportType::TeacherLogs, ReportType::MonthlyAssessments, ReportType::ActivityDocumentations, ReportType::Notes, ReportType::Documents], true);
        }

        return false;
    }
}
