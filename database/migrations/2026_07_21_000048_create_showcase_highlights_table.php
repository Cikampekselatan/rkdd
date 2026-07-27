<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('showcase_highlights', function (Blueprint $table): void {
            $table->id();
            $table->string('period')->index();
            $table->string('title');
            $table->string('student_name')->nullable();
            $table->text('caption')->nullable();
            $table->string('url', 2048);
            $table->string('media_type')->index();
            $table->unsignedSmallInteger('display_order')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['period', 'is_active', 'display_order'], 'showcase_period_active_order_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('showcase_highlights');
    }
};
