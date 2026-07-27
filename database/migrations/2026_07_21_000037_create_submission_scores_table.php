<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submission_scores', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('submission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rubric_criterion_id')->constrained()->restrictOnDelete();
            $table->foreignId('rubric_level_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('level');
            $table->decimal('weight', 5, 2);
            $table->decimal('weighted_score', 5, 2);
            $table->text('teacher_note')->nullable();
            $table->timestamps();
            $table->unique(['submission_id', 'rubric_criterion_id'], 'submission_scores_submission_criterion_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submission_scores');
    }
};
