<?php

use App\Enums\TeacherActivityStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_activity_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('log_number');
            $table->date('activity_date');
            $table->text('material');
            $table->longText('activities');
            $table->text('assignment')->nullable();
            $table->string('signature_path')->nullable();
            $table->string('signature_original_name')->nullable();
            $table->string('status')->default(TeacherActivityStatus::Draft->value)->index();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('rejection_note')->nullable();
            $table->timestamps();

            $table->unique(['academic_year_id', 'teacher_id', 'log_number'], 'teacher_logs_year_teacher_number_unique');
            $table->unique(['teacher_id', 'activity_date']);
            $table->index(['academic_year_id', 'activity_date'], 'teacher_logs_year_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_activity_logs');
    }
};
