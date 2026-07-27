<?php

use App\Enums\SubmissionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('status')->default(SubmissionStatus::Draft->value)->index();
            $table->unsignedTinyInteger('current_version_number')->default(1);
            $table->unsignedTinyInteger('revision_count')->default(0);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('last_reviewed_at')->nullable();
            $table->text('revision_note')->nullable();
            $table->foreignId('revision_requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revision_requested_at')->nullable();
            $table->timestamps();
            $table->unique(['assignment_id', 'user_id']);
            $table->index(['assignment_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};
