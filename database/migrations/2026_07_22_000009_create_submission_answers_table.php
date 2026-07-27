<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submission_answers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('submission_version_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assignment_question_id')->constrained()->cascadeOnDelete();
            $table->longText('answer_text')->nullable();
            $table->string('answer_url', 2048)->nullable();
            $table->timestamps();

            $table->unique(['submission_version_id', 'assignment_question_id'], 'submission_answers_unique_question');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submission_answers');
    }
};
