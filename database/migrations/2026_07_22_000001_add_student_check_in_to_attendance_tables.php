<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE attendance_sessions ADD COLUMN IF NOT EXISTS check_in_token_encrypted TEXT NULL AFTER notes');
            DB::statement('ALTER TABLE attendance_sessions ADD COLUMN IF NOT EXISTS check_in_token_hash VARCHAR(255) NULL AFTER check_in_token_encrypted');
            DB::statement('ALTER TABLE attendance_sessions ADD UNIQUE INDEX IF NOT EXISTS attendance_sessions_check_in_token_hash_unique (check_in_token_hash)');
            DB::statement('ALTER TABLE attendance_sessions ADD COLUMN IF NOT EXISTS check_in_opens_at TIMESTAMP NULL AFTER check_in_token_hash');
            DB::statement('ALTER TABLE attendance_sessions ADD COLUMN IF NOT EXISTS check_in_expires_at TIMESTAMP NULL AFTER check_in_opens_at');
            DB::statement('ALTER TABLE attendance_sessions ADD COLUMN IF NOT EXISTS check_in_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER check_in_expires_at');

            DB::statement('ALTER TABLE attendance_records ADD COLUMN IF NOT EXISTS checked_in_at TIMESTAMP NULL AFTER recorded_at');
            DB::statement('ALTER TABLE attendance_records ADD COLUMN IF NOT EXISTS check_in_method VARCHAR(255) NULL AFTER checked_in_at');
            DB::statement('ALTER TABLE attendance_records ADD INDEX IF NOT EXISTS attendance_records_session_checkin_idx (attendance_session_id, checked_in_at)');

            return;
        }

        if (! Schema::hasColumn('attendance_sessions', 'check_in_token_encrypted')) {
            Schema::table('attendance_sessions', function (Blueprint $table): void {
                $table->text('check_in_token_encrypted')->nullable()->after('notes');
            });
        }

        if (! Schema::hasColumn('attendance_sessions', 'check_in_token_hash')) {
            Schema::table('attendance_sessions', function (Blueprint $table): void {
                $table->string('check_in_token_hash')->nullable()->unique()->after('check_in_token_encrypted');
            });
        }

        if (! Schema::hasColumn('attendance_sessions', 'check_in_opens_at')) {
            Schema::table('attendance_sessions', function (Blueprint $table): void {
                $table->timestamp('check_in_opens_at')->nullable()->after('check_in_token_hash');
            });
        }

        if (! Schema::hasColumn('attendance_sessions', 'check_in_expires_at')) {
            Schema::table('attendance_sessions', function (Blueprint $table): void {
                $table->timestamp('check_in_expires_at')->nullable()->after('check_in_opens_at');
            });
        }

        if (! Schema::hasColumn('attendance_sessions', 'check_in_enabled')) {
            Schema::table('attendance_sessions', function (Blueprint $table): void {
                $table->boolean('check_in_enabled')->default(false)->after('check_in_expires_at');
            });
        }

        if (! Schema::hasColumn('attendance_records', 'checked_in_at')) {
            Schema::table('attendance_records', function (Blueprint $table): void {
                $table->timestamp('checked_in_at')->nullable()->after('recorded_at');
            });
        }

        if (! Schema::hasColumn('attendance_records', 'check_in_method')) {
            Schema::table('attendance_records', function (Blueprint $table): void {
                $table->string('check_in_method')->nullable()->after('checked_in_at');
            });
        }

        Schema::table('attendance_records', function (Blueprint $table): void {
            $table->index(['attendance_session_id', 'checked_in_at'], 'attendance_records_session_checkin_idx');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_records', function (Blueprint $table): void {
            $table->dropIndex('attendance_records_session_checkin_idx');
            $table->dropColumn(['checked_in_at', 'check_in_method']);
        });

        Schema::table('attendance_sessions', function (Blueprint $table): void {
            $table->dropColumn([
                'check_in_token_hash',
                'check_in_token_encrypted',
                'check_in_opens_at',
                'check_in_expires_at',
                'check_in_enabled',
            ]);
        });
    }
};
