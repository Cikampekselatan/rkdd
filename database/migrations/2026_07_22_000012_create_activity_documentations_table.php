<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_documentations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->date('activity_date');
            $table->string('title');
            $table->longText('description')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('photo_original_name')->nullable();
            $table->string('resource_url')->nullable();
            $table->string('video_url')->nullable();
            $table->timestamps();

            $table->index(['academic_year_id', 'activity_date'], 'activity_docs_year_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_documentations');
    }
};
