<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_project_assessments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('group_project_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('final_score', 5, 2)->default(0);
            $table->unsignedTinyInteger('achievement_level')->default(1);
            $table->text('feedback')->nullable();
            $table->text('private_note')->nullable();
            $table->boolean('is_published')->default(false)->index();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('assessed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_project_assessments');
    }
};
