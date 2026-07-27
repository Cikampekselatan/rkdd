<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_group_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('role')->nullable();
            $table->text('contribution_note')->nullable();
            $table->date('joined_at')->nullable();
            $table->date('left_at')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->unique(['project_group_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_group_members');
    }
};
