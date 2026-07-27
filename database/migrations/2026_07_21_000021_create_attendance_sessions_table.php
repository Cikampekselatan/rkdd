<?php

use App\Enums\AttendanceSessionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Recover safely when MySQL kept the table from an earlier failed index creation.
        if (Schema::hasTable('attendance_sessions')) {
            Schema::table('attendance_sessions', function (Blueprint $table): void {
                $table->index(['academic_year_id', 'class_id', 'attendance_date'], 'attendance_sessions_year_class_date_idx');
            });

            return;
        }

        Schema::create('attendance_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('learning_session_id')->constrained()->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('class_id')->constrained('classes')->restrictOnDelete();
            $table->date('attendance_date');
            $table->string('status')->default(AttendanceSessionStatus::Open->value)->index();
            $table->text('notes')->nullable();
            $table->foreignId('opened_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('opened_at');
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->unique(['learning_session_id', 'class_id']);
            $table->index(['academic_year_id', 'class_id', 'attendance_date'], 'attendance_sessions_year_class_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_sessions');
    }
};
