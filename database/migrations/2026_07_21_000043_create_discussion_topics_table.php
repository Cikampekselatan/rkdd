<?php

use App\Enums\DiscussionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discussion_topics', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('academic_year_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('class_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->foreignId('learning_session_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('title');
            $table->longText('body');
            $table->string('status')->default(DiscussionStatus::Open->value)->index();
            $table->boolean('is_pinned')->default(false)->index();
            $table->boolean('is_hidden')->default(false)->index();
            $table->foreignId('hidden_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('hidden_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['class_id', 'status', 'is_hidden']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discussion_topics');
    }
};
