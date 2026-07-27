<?php

use App\Enums\PortfolioApprovalStatus;
use App\Enums\PortfolioVisibility;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portfolio_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('class_id')->constrained('classes')->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('submission_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('initial_submission_version_id')->nullable()->constrained('submission_versions')->nullOnDelete();
            $table->foreignId('final_submission_version_id')->nullable()->constrained('submission_versions')->nullOnDelete();
            $table->string('source_type')->index();
            $table->string('title');
            $table->string('slug')->index();
            $table->string('work_type')->index();
            $table->longText('description');
            $table->longText('reflection')->nullable();
            $table->text('sources')->nullable();
            $table->boolean('ai_used')->default(false);
            $table->string('ai_tools')->nullable();
            $table->text('ai_usage_description')->nullable();
            $table->string('thumbnail_path')->nullable();
            $table->string('initial_file_path')->nullable();
            $table->string('initial_original_name')->nullable();
            $table->string('final_file_path')->nullable();
            $table->string('final_original_name')->nullable();
            $table->string('initial_url', 2048)->nullable();
            $table->string('final_url', 2048)->nullable();
            $table->string('visibility')->default(PortfolioVisibility::Private->value)->index();
            $table->string('approval_status')->default(PortfolioApprovalStatus::NotRequired->value)->index();
            $table->text('approval_note')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->boolean('is_featured')->default(false)->index();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['class_id', 'visibility', 'approval_status'], 'portfolio_class_visibility_approval_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolio_items');
    }
};
