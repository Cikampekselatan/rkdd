<?php

use App\Enums\AssignmentType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('class_id')->constrained('classes')->restrictOnDelete();
            $table->foreignId('learning_session_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->longText('instructions');
            $table->string('type')->default(AssignmentType::Text->value)->index();
            $table->dateTime('available_from')->nullable()->index();
            $table->dateTime('due_at')->index();
            $table->boolean('allow_late')->default(false);
            $table->unsignedTinyInteger('max_files')->default(3);
            $table->unsignedInteger('max_file_size_kb')->default(5120);
            $table->json('allowed_mime_types')->nullable();
            $table->unsignedTinyInteger('max_revisions')->default(1);
            $table->boolean('is_published')->default(false)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['academic_year_id', 'class_id', 'is_published'], 'assignments_year_class_publish_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};
