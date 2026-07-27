<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rubric_levels', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('rubric_criterion_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('level');
            $table->string('label');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->unique(['rubric_criterion_id', 'level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rubric_levels');
    }
};
