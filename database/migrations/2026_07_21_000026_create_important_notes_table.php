<?php

use App\Enums\ImportantNotePriority;
use App\Enums\ImportantNoteStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('important_notes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->date('note_date');
            $table->longText('note');
            $table->longText('resolution')->nullable();
            $table->string('priority')->default(ImportantNotePriority::Medium->value)->index();
            $table->string('status')->default(ImportantNoteStatus::Open->value)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('teacher_initial_path')->nullable();
            $table->foreignId('teacher_initialed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('teacher_initialed_at')->nullable();
            $table->string('coach_initial_path')->nullable();
            $table->foreignId('coach_initialed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('coach_initialed_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index(['academic_year_id', 'note_date'], 'important_notes_year_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('important_notes');
    }
};
