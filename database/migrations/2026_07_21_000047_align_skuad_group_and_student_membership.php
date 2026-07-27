<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classes', function (Blueprint $table): void {
            $table->unsignedTinyInteger('grade_level')->nullable()->change();
        });

        Schema::table('student_profiles', function (Blueprint $table): void {
            $table->unsignedTinyInteger('grade_level')->nullable()->after('birth_date')->index();
            $table->string('school_class_name', 50)->nullable()->after('grade_level');
        });

        Schema::table('class_students', function (Blueprint $table): void {
            $table->timestamp('left_at')->nullable()->after('joined_at');
            $table->string('exit_reason', 50)->nullable()->after('status');
            $table->text('exit_notes')->nullable()->after('exit_reason');
        });
    }

    public function down(): void
    {
        Schema::table('class_students', function (Blueprint $table): void {
            $table->dropColumn(['left_at', 'exit_reason', 'exit_notes']);
        });

        Schema::table('student_profiles', function (Blueprint $table): void {
            $table->dropIndex(['grade_level']);
            $table->dropColumn(['grade_level', 'school_class_name']);
        });

        Schema::table('classes', function (Blueprint $table): void {
            $table->unsignedTinyInteger('grade_level')->nullable(false)->change();
        });
    }
};
