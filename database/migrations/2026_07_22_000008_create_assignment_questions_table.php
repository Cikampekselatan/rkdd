<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignment_questions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('assignment_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(1);
            $table->string('prompt');
            $table->text('help_text')->nullable();
            $table->string('answer_type')->default('paragraph');
            $table->boolean('is_required')->default(true);
            $table->timestamps();

            $table->index(['assignment_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignment_questions');
    }
};
