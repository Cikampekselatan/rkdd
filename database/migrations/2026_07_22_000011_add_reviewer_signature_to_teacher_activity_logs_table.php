<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teacher_activity_logs', function (Blueprint $table): void {
            $table->string('reviewer_signature_path')->nullable()->after('signature_original_name');
            $table->string('reviewer_signature_original_name')->nullable()->after('reviewer_signature_path');
        });
    }

    public function down(): void
    {
        Schema::table('teacher_activity_logs', function (Blueprint $table): void {
            $table->dropColumn(['reviewer_signature_path', 'reviewer_signature_original_name']);
        });
    }
};
