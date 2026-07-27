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
            $this->tryStatement('ALTER TABLE `monthly_student_assessments` ADD INDEX `monthly_assessments_academic_year_id_idx` (`academic_year_id`)', [1061]);
            $this->tryStatement('ALTER TABLE `monthly_student_assessments` ADD INDEX `monthly_assessments_user_id_idx` (`user_id`)', [1061]);
            $this->tryStatement('ALTER TABLE `monthly_student_assessments` DROP INDEX `monthly_assessments_unique_period`', [1091]);
            $this->tryStatement('ALTER TABLE `monthly_student_assessments` ADD UNIQUE INDEX `monthly_assessments_program_unique_period` (`program_batch_id`, `academic_year_id`, `user_id`, `semester`, `assessment_month`)', [1061]);

            return;
        }

        Schema::table('monthly_student_assessments', function (Blueprint $table): void {
            $table->dropUnique('monthly_assessments_unique_period');
            $table->unique(['program_batch_id', 'academic_year_id', 'user_id', 'semester', 'assessment_month'], 'monthly_assessments_program_unique_period');
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            $this->tryStatement('ALTER TABLE `monthly_student_assessments` DROP INDEX `monthly_assessments_program_unique_period`', [1091]);
            $this->tryStatement('ALTER TABLE `monthly_student_assessments` ADD UNIQUE INDEX `monthly_assessments_unique_period` (`academic_year_id`, `user_id`, `semester`, `assessment_month`)', [1061]);
            $this->tryStatement('ALTER TABLE `monthly_student_assessments` DROP INDEX `monthly_assessments_academic_year_id_idx`', [1091, 1553]);
            $this->tryStatement('ALTER TABLE `monthly_student_assessments` DROP INDEX `monthly_assessments_user_id_idx`', [1091, 1553]);

            return;
        }

        Schema::table('monthly_student_assessments', function (Blueprint $table): void {
            $table->dropUnique('monthly_assessments_program_unique_period');
            $table->unique(['academic_year_id', 'user_id', 'semester', 'assessment_month'], 'monthly_assessments_unique_period');
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
