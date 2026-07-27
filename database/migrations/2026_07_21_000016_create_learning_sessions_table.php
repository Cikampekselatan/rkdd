<?php

use App\Enums\LearningSessionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('learning_module_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('session_number');
            $table->unsignedTinyInteger('semester');
            $table->string('title');
            $table->string('slug');
            $table->unsignedSmallInteger('duration_minutes')->default(90);
            $table->json('objectives');
            $table->longText('introduction')->nullable();
            $table->longText('summary')->nullable();
            $table->longText('practice_instructions')->nullable();
            $table->longText('reflection_prompt')->nullable();
            $table->string('status')->default(LearningSessionStatus::Draft->value)->index();
            $table->timestamp('scheduled_at')->nullable()->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['academic_year_id', 'session_number']);
            $table->unique(['learning_module_id', 'slug']);
            $table->index(['academic_year_id', 'semester', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_sessions');
    }
};
