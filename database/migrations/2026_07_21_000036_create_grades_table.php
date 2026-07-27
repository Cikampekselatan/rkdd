<?php

use App\Enums\RemedialStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grades', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('submission_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('rubric_id')->constrained()->restrictOnDelete();
            $table->decimal('total_score', 5, 2)->default(0);
            $table->unsignedTinyInteger('achievement_level')->default(1);
            $table->longText('feedback')->nullable();
            $table->longText('private_note')->nullable();
            $table->boolean('is_published')->default(false)->index();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('graded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('remedial_status')->default(RemedialStatus::None->value)->index();
            $table->text('remedial_note')->nullable();
            $table->dateTime('remedial_due_at')->nullable();
            $table->longText('remedial_response')->nullable();
            $table->timestamp('remedial_submitted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grades');
    }
};
