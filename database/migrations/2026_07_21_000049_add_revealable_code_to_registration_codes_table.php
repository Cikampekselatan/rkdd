<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registration_codes', function (Blueprint $table): void {
            $table->text('plain_code_encrypted')->nullable()->after('code_hint');
        });
    }

    public function down(): void
    {
        Schema::table('registration_codes', function (Blueprint $table): void {
            $table->dropColumn('plain_code_encrypted');
        });
    }
};
