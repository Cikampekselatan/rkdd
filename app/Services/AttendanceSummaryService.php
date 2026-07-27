<?php

namespace App\Services;

use App\Enums\AttendanceSessionStatus;
use App\Enums\AttendanceStatus;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\ClassStudent;
use App\Models\User;
use Illuminate\Support\Collection;

class AttendanceSummaryService
{
    /** @return array{total: int, attended: int, percentage: int, counts: array<string, int>} */
    public function forStudent(User $student, ?int $academicYearId = null, ?int $programBatchId = null): array
    {
        $records = AttendanceRecord::query()
            ->where('user_id', $student->id)
            ->whereHas('attendanceSession', fn ($query) => $query
                ->where('status', AttendanceSessionStatus::Closed->value)
                ->when($academicYearId, fn ($query, int $year) => $query->where('academic_year_id', $year))
                ->when($programBatchId, fn ($query, int $batchId) => $query->where('program_batch_id', $batchId)))
            ->get(['status']);

        return $this->summarize($records);
    }

    /** @return Collection<int, array<string, mixed>> */
    public function perStudent(int $academicYearId, int $classId): Collection
    {
        $members = ClassStudent::query()
            ->with('user:id,name')
            ->where('academic_year_id', $academicYearId)
            ->where('class_id', $classId)
            ->where('status', 'active')
            ->orderBy('user_id')
            ->get();
        $records = AttendanceRecord::query()
            ->whereIn('user_id', $members->pluck('user_id'))
            ->whereHas('attendanceSession', fn ($query) => $query
                ->where('academic_year_id', $academicYearId)
                ->where('class_id', $classId)
                ->where('status', AttendanceSessionStatus::Closed->value))
            ->get()
            ->groupBy('user_id');

        return $members->map(function (ClassStudent $member) use ($records): array {
            return [
                'student' => $member->user,
                ...$this->summarize($records->get($member->user_id, collect())),
            ];
        })->sortBy(fn (array $row) => $row['student']->name)->values();
    }

    /** @return array{total: int, attended: int, percentage: int, counts: array<string, int>} */
    public function forSession(AttendanceSession $session): array
    {
        return $this->summarize($session->records);
    }

    /** @param Collection<int, AttendanceRecord> $records
     * @return array{total: int, attended: int, percentage: int, counts: array<string, int>}
     */
    private function summarize(Collection $records): array
    {
        $counts = collect(AttendanceStatus::cases())
            ->mapWithKeys(fn (AttendanceStatus $status): array => [$status->value => $records->where('status', $status)->count()])
            ->all();
        $attended = $counts[AttendanceStatus::Present->value] + $counts[AttendanceStatus::Late->value];
        $total = $records->count();

        return [
            'total' => $total,
            'attended' => $attended,
            'percentage' => $total > 0 ? (int) round(($attended / $total) * 100) : 0,
            'counts' => $counts,
        ];
    }
}
