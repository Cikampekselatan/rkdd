<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discussion_reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('post_id')->constrained('discussion_posts')->cascadeOnDelete();
            $table->foreignId('reported_by')->constrained('users')->cascadeOnDelete();
            $table->string('reason', 1000);
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['post_id', 'reported_by']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discussion_reports');
    }
};
