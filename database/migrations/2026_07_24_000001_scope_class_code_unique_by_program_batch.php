<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            $this->tryStatement('ALTER TABLE `classes` ADD INDEX `classes_academic_year_id_index` (`academic_year_id`)', [1061]);
            $this->tryStatement('ALTER TABLE `classes` DROP INDEX `classes_academic_year_id_code_unique`', [1091]);
            $this->tryStatement('ALTER TABLE `classes` ADD UNIQUE INDEX `classes_program_year_code_unique` (`program_batch_id`, `academic_year_id`, `code`)', [1061]);

            return;
        }

        Schema::table('classes', function (Blueprint $table): void {
            $table->dropUnique(['academic_year_id', 'code']);
        });

        Schema::table('classes', function (Blueprint $table): void {
            $table->unique(['program_batch_id', 'academic_year_id', 'code'], 'classes_program_year_code_unique');
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            $this->tryStatement('ALTER TABLE `classes` DROP INDEX `classes_program_year_code_unique`', [1091]);
            $this->tryStatement('ALTER TABLE `classes` ADD UNIQUE INDEX `classes_academic_year_id_code_unique` (`academic_year_id`, `code`)', [1061]);
            $this->tryStatement('ALTER TABLE `classes` DROP INDEX `classes_academic_year_id_index`', [1091]);

            return;
        }

        Schema::table('classes', function (Blueprint $table): void {
            $table->dropUnique('classes_program_year_code_unique');
        });

        Schema::table('classes', function (Blueprint $table): void {
            $table->unique(['academic_year_id', 'code']);
        });
    }

    /**
     * @param  array<int, int>  $ignoredErrorCodes
     */
    private function tryStatement(string $sql, array $ignoredErrorCodes): void
    {
        try {
            DB::statement($sql);
        } catch (QueryException $exception) {
            $errorCode = (int) ($exception->errorInfo[1] ?? $exception->getCode());

            if (! in_array($errorCode, $ignoredErrorCodes, true)) {
                throw $exception;
            }
        }
    }
};
