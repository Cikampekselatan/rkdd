<?php

namespace App\Actions\Attendance;

use App\Models\AttendanceSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EnableAttendanceCheckIn
{
    public const DEFAULT_MINUTES = 30;

    /** @return array{session: AttendanceSession, token: string} */
    public function execute(AttendanceSession $session, User $actor, int $minutes = self::DEFAULT_MINUTES): array
    {
        return DB::transaction(function () use ($minutes, $session): array {
            $session = AttendanceSession::query()->lockForUpdate()->findOrFail($session->id);

            if (! $session->isOpen()) {
                throw ValidationException::withMessages(['attendance_session' => 'Sesi presensi sudah ditutup. QR tidak dapat diaktifkan.']);
            }

            $token = Str::random(64);
            $now = now();

            $session->update([
                'check_in_token_encrypted' => $token,
                'check_in_token_hash' => self::hashToken($token),
                'check_in_opens_at' => $now,
                'check_in_expires_at' => $now->copy()->addMinutes(max(5, min($minutes, 180))),
                'check_in_enabled' => true,
            ]);

            return ['session' => $session->refresh(), 'token' => $token];
        });
    }

    public static function hashToken(string $token): string
    {
        return hash_hmac('sha256', $token, (string) config('app.key'));
    }
}
