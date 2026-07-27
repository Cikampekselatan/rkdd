<?php

use App\Enums\StudentMembershipStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('student_number')->unique();
            $table->string('nisn')->nullable()->unique();
            $table->string('nickname')->nullable();
            $table->string('gender', 20);
            $table->date('birth_date');
            $table->unsignedBigInteger('class_id')->index();
            $table->string('parent_name')->nullable();
            $table->string('parent_phone', 30)->nullable();
            $table->string('guardian_relationship', 50)->nullable();
            $table->text('address')->nullable();
            $table->timestamp('joined_at')->nullable();
            $table->string('membership_status')->default(StudentMembershipStatus::Onboarding->value)->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_profiles');
    }
};
