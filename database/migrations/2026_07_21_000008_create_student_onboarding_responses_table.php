<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_onboarding_responses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('registration_code_id')->nullable()->constrained()->restrictOnDelete();
            $table->json('device_access')->nullable();
            $table->string('internet_access')->nullable();
            $table->boolean('willing_to_share_device')->nullable();
            $table->json('digital_apps')->nullable();
            $table->json('interests')->nullable();
            $table->json('initial_skills')->nullable();
            $table->text('experience')->nullable();
            $table->text('expectation')->nullable();
            $table->text('learning_targets')->nullable();
            $table->timestamp('agreed_rules_at')->nullable();
            $table->timestamp('agreed_privacy_at')->nullable();
            $table->timestamp('agreed_ai_policy_at')->nullable();
            $table->timestamp('agreed_publication_policy_at')->nullable();
            $table->unsignedTinyInteger('current_step')->default(1);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_onboarding_responses');
    }
};
