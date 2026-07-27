<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submission_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('submission_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('version_number');
            $table->longText('text_content')->nullable();
            $table->string('video_url', 2048)->nullable();
            $table->string('external_url', 2048)->nullable();
            $table->text('student_note')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->unique(['submission_id', 'version_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submission_versions');
    }
};
