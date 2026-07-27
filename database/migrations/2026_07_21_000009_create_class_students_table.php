<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_students', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('academic_year_id')->index();
            $table->unsignedBigInteger('class_id')->index();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('joined_at');
            $table->string('status')->default('active')->index();
            $table->timestamps();

            $table->unique(['academic_year_id', 'class_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_students');
    }
};
