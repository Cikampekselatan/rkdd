<?php

namespace App\Actions\Attendance;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AmendClosedAttendanceRecord
{
    /** @param array<string, mixed> $data */
    public function execute(AttendanceRecord $record, array $data, User $actor): AttendanceRecord
    {
        return DB::transaction(function () use ($actor, $data, $record): AttendanceRecord {
            $record = AttendanceRecord::query()->with('attendanceSession')->lockForUpdate()->findOrFail($record->id);

            if ($record->attendanceSession->isOpen()) {
                throw ValidationException::withMessages(['attendance_record' => 'Koreksi khusus hanya digunakan setelah sesi ditutup.']);
            }

            $oldStatus = $record->status;
            $oldNotes = $record->notes;
            $record->update([
                'status' => $data['status'],
                'notes' => $data['notes'] ?? null,
                'recorded_by' => $actor->id,
                'recorded_at' => now(),
            ]);
            $record->logs()->create([
                'user_id' => $actor->id,
                'event' => 'closed_amendment',
                'old_status' => $oldStatus,
                'new_status' => $record->status,
                'old_notes' => $oldNotes,
                'new_notes' => $record->notes,
                'reason' => $data['reason'],
            ]);

            return $record->refresh();
        });
    }
}
