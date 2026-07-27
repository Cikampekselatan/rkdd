<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monthly_student_assessments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('class_id')->constrained('classes')->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('semester');
            $table->unsignedTinyInteger('assessment_month');
            $table->string('period_label');
            $table->text('product_summary')->nullable();
            $table->string('evidence_url')->nullable();
            $table->decimal('product_portfolio_score', 5, 2)->default(0);
            $table->decimal('process_creativity_score', 5, 2)->default(0);
            $table->decimal('collaboration_responsibility_score', 5, 2)->default(0);
            $table->decimal('presentation_communication_score', 5, 2)->default(0);
            $table->decimal('ethics_security_reflection_score', 5, 2)->default(0);
            $table->decimal('final_score', 5, 2)->default(0);
            $table->unsignedTinyInteger('achievement_level')->default(1);
            $table->text('strengths')->nullable();
            $table->text('improvement_targets')->nullable();
            $table->text('remedial_plan')->nullable();
            $table->text('enrichment_plan')->nullable();
            $table->text('teacher_note')->nullable();
            $table->boolean('is_published')->default(false)->index();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('assessed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assessed_at')->nullable();
            $table->timestamps();

            $table->unique(['academic_year_id', 'user_id', 'semester', 'assessment_month'], 'monthly_assessments_unique_period');
            $table->index(['academic_year_id', 'class_id', 'semester', 'assessment_month'], 'monthly_assessments_period_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_student_assessments');
    }
};
