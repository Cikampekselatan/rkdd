<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_resources', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('academic_year_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug')->index();
            $table->string('category')->index();
            $table->text('description')->nullable();
            $table->string('drive_url', 2048);
            $table->string('drive_file_id')->index();
            $table->string('preview_url', 2048);
            $table->string('audience')->index();
            $table->unsignedTinyInteger('semester')->nullable()->index();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_pinned')->default(false)->index();
            $table->boolean('is_active')->default(false)->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'audience', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_resources');
    }
};
