<?php

namespace App\Actions\Attendance;

use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveBulkAttendance
{
    /** @param list<array<string, mixed>> $records */
    public function execute(AttendanceSession $session, array $records, User $actor): AttendanceSession
    {
        return DB::transaction(function () use ($actor, $records, $session): AttendanceSession {
            $session = AttendanceSession::query()->lockForUpdate()->findOrFail($session->id);

            if (! $session->isOpen()) {
                throw ValidationException::withMessages(['attendance_session' => 'Sesi telah ditutup. Gunakan koreksi ber-audit.']);
            }

            foreach ($records as $data) {
                $record = AttendanceRecord::query()
                    ->where('attendance_session_id', $session->id)
                    ->where('user_id', $data['user_id'])
                    ->lockForUpdate()
                    ->firstOrFail();
                $oldStatus = $record->status;
                $oldNotes = $record->notes;
                $record->fill([
                    'status' => $data['status'],
                    'notes' => $data['notes'] ?? null,
                    'recorded_by' => $actor->id,
                    'recorded_at' => now(),
                ]);

                if ($record->isDirty(['status', 'notes'])) {
                    $record->save();
                    $record->logs()->create([
                        'user_id' => $actor->id,
                        'event' => 'bulk_updated',
                        'old_status' => $oldStatus,
                        'new_status' => $record->status,
                        'old_notes' => $oldNotes,
                        'new_notes' => $record->notes,
                    ]);
                }
            }

            return $session->refresh();
        });
    }
}
