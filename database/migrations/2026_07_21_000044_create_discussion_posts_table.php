<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discussion_posts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('topic_id')->constrained('discussion_topics')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('discussion_posts')->cascadeOnDelete();
            $table->longText('body');
            $table->boolean('is_hidden')->default(false)->index();
            $table->foreignId('hidden_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('hidden_at')->nullable();
            $table->timestamps();
            $table->index(['topic_id', 'parent_id', 'is_hidden']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discussion_posts');
    }
};
