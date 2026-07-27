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
            $this->upMysql();

            return;
        }

        Schema::table('learning_modules', function (Blueprint $table): void {
            $table->dropUnique('learning_modules_academic_year_id_module_number_unique');
            $table->dropUnique('learning_modules_academic_year_id_slug_unique');
            $table->unique(['program_batch_id', 'academic_year_id', 'module_number'], 'learning_modules_program_year_number_unique');
            $table->unique(['program_batch_id', 'academic_year_id', 'slug'], 'learning_modules_program_year_slug_unique');
        });

        Schema::table('learning_sessions', function (Blueprint $table): void {
            $table->dropUnique('learning_sessions_academic_year_id_session_number_unique');
            $table->unique(['program_batch_id', 'academic_year_id', 'session_number'], 'learning_sessions_program_year_number_unique');
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            $this->downMysql();

            return;
        }

        Schema::table('learning_sessions', function (Blueprint $table): void {
            $table->dropUnique('learning_sessions_program_year_number_unique');
            $table->unique(['academic_year_id', 'session_number']);
        });

        Schema::table('learning_modules', function (Blueprint $table): void {
            $table->dropUnique('learning_modules_program_year_number_unique');
            $table->dropUnique('learning_modules_program_year_slug_unique');
            $table->unique(['academic_year_id', 'module_number']);
            $table->unique(['academic_year_id', 'slug']);
        });
    }

    private function upMysql(): void
    {
        $this->tryStatement('ALTER TABLE `learning_modules` ADD INDEX `learning_modules_academic_year_id_idx` (`academic_year_id`)', [1061]);
        $this->tryStatement('ALTER TABLE `learning_modules` DROP INDEX `learning_modules_academic_year_id_module_number_unique`', [1091]);
        $this->tryStatement('ALTER TABLE `learning_modules` DROP INDEX `learning_modules_academic_year_id_slug_unique`', [1091]);
        $this->tryStatement('ALTER TABLE `learning_modules` ADD UNIQUE INDEX `learning_modules_program_year_number_unique` (`program_batch_id`, `academic_year_id`, `module_number`)', [1061]);
        $this->tryStatement('ALTER TABLE `learning_modules` ADD UNIQUE INDEX `learning_modules_program_year_slug_unique` (`program_batch_id`, `academic_year_id`, `slug`)', [1061]);

        $this->tryStatement('ALTER TABLE `learning_sessions` ADD INDEX `learning_sessions_academic_year_id_idx` (`academic_year_id`)', [1061]);
        $this->tryStatement('ALTER TABLE `learning_sessions` DROP INDEX `learning_sessions_academic_year_id_session_number_unique`', [1091]);
        $this->tryStatement('ALTER TABLE `learning_sessions` ADD UNIQUE INDEX `learning_sessions_program_year_number_unique` (`program_batch_id`, `academic_year_id`, `session_number`)', [1061]);
    }

    private function downMysql(): void
    {
        $this->tryStatement('ALTER TABLE `learning_sessions` DROP INDEX `learning_sessions_program_year_number_unique`', [1091]);
        $this->tryStatement('ALTER TABLE `learning_sessions` ADD UNIQUE INDEX `learning_sessions_academic_year_id_session_number_unique` (`academic_year_id`, `session_number`)', [1061]);
        $this->tryStatement('ALTER TABLE `learning_sessions` DROP INDEX `learning_sessions_academic_year_id_idx`', [1091, 1553]);

        $this->tryStatement('ALTER TABLE `learning_modules` DROP INDEX `learning_modules_program_year_number_unique`', [1091]);
        $this->tryStatement('ALTER TABLE `learning_modules` DROP INDEX `learning_modules_program_year_slug_unique`', [1091]);
        $this->tryStatement('ALTER TABLE `learning_modules` ADD UNIQUE INDEX `learning_modules_academic_year_id_module_number_unique` (`academic_year_id`, `module_number`)', [1061]);
        $this->tryStatement('ALTER TABLE `learning_modules` ADD UNIQUE INDEX `learning_modules_academic_year_id_slug_unique` (`academic_year_id`, `slug`)', [1061]);
        $this->tryStatement('ALTER TABLE `learning_modules` DROP INDEX `learning_modules_academic_year_id_idx`', [1091, 1553]);
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
