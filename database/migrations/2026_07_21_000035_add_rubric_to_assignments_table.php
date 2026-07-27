<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assignments', fn (Blueprint $table) => $table->foreignId('rubric_id')->nullable()->after('learning_session_id')->constrained()->nullOnDelete());
    }

    public function down(): void
    {
        Schema::table('assignments', fn (Blueprint $table) => $table->dropConstrainedForeignId('rubric_id'));
    }
};
