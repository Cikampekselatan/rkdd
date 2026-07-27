<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registration_codes', function (Blueprint $table): void {
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->restrictOnDelete();
            $table->foreign('class_id')->references('id')->on('classes')->restrictOnDelete();
        });

        Schema::table('student_profiles', function (Blueprint $table): void {
            $table->foreign('class_id')->references('id')->on('classes')->restrictOnDelete();
        });

        Schema::table('class_students', function (Blueprint $table): void {
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->restrictOnDelete();
            $table->foreign('class_id')->references('id')->on('classes')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('class_students', function (Blueprint $table): void {
            $table->dropForeign(['academic_year_id']);
            $table->dropForeign(['class_id']);
        });

        Schema::table('student_profiles', function (Blueprint $table): void {
            $table->dropForeign(['class_id']);
        });

        Schema::table('registration_codes', function (Blueprint $table): void {
            $table->dropForeign(['academic_year_id']);
            $table->dropForeign(['class_id']);
        });
    }
};
