<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_materials', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('learning_session_id')->constrained()->cascadeOnDelete();
            $table->string('type')->index();
            $table->string('title');
            $table->longText('content')->nullable();
            $table->string('url', 2048)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_required')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['learning_session_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_materials');
    }
};
