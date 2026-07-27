<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programs', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type')->default('ekstrakurikuler');
            $table->text('description')->nullable();
            $table->string('primary_color', 20)->default('#0f766e');
            $table->string('secondary_color', 20)->default('#0f172a');
            $table->string('accent_color', 20)->default('#f59e0b');
            $table->string('logo_path')->nullable();
            $table->string('banner_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('institutions', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type')->default('sekolah');
            $table->string('address')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('program_batches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('program_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('institution_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('period_label');
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->string('audience_type')->default('school');
            $table->string('participant_label')->default('Siswa');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['program_id', 'institution_id', 'period_label'], 'program_batches_context_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_batches');
        Schema::dropIfExists('institutions');
        Schema::dropIfExists('programs');
    }
};
