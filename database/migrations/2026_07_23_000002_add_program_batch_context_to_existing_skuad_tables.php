<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<int, string> */
    private array $contextTables = [
        'classes',
        'registration_codes',
        'student_profiles',
        'class_students',
        'learning_modules',
        'learning_sessions',
        'document_resources',
        'attendance_sessions',
        'teacher_activity_logs',
        'important_notes',
        'assignments',
        'rubrics',
        'portfolio_items',
        'announcements',
        'discussion_topics',
        'monthly_student_assessments',
        'project_groups',
        'activity_documentations',
        'showcase_highlights',
    ];

    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            $this->upMysql();

            return;
        }

        foreach ($this->contextTables as $tableName) {
            if (! Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'program_batch_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table): void {
                $table->foreignId('program_batch_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('program_batches')
                    ->nullOnDelete();
            });
        }

        $defaultBatchId = DB::table('program_batches')->where('slug', 'skuad-2026-2027')->value('id')
            ?? DB::table('program_batches')->orderBy('id')->value('id');

        if (! $defaultBatchId) {
            return;
        }

        foreach ($this->contextTables as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'program_batch_id')) {
                continue;
            }

            DB::table($tableName)->whereNull('program_batch_id')->update(['program_batch_id' => $defaultBatchId]);
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            $this->downMysql();

            return;
        }

        foreach (array_reverse($this->contextTables) as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'program_batch_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropConstrainedForeignId('program_batch_id');
            });
        }
    }

    private function upMysql(): void
    {
        foreach ($this->contextTables as $tableName) {
            $this->tryStatement("ALTER TABLE `{$tableName}` ADD COLUMN `program_batch_id` BIGINT UNSIGNED NULL AFTER `id`", [1060, 1146]);
            $this->tryStatement("ALTER TABLE `{$tableName}` ADD INDEX `{$this->indexName($tableName)}` (`program_batch_id`)", [1061, 1072, 1146]);
            $this->tryStatement("ALTER TABLE `{$tableName}` ADD CONSTRAINT `{$this->foreignName($tableName)}` FOREIGN KEY (`program_batch_id`) REFERENCES `program_batches` (`id`) ON DELETE SET NULL ON UPDATE CASCADE", [1005, 1025, 1061, 1072, 1146, 1215, 1826]);
        }

        $defaultBatchId = DB::table('program_batches')->where('slug', 'skuad-2026-2027')->value('id')
            ?? DB::table('program_batches')->orderBy('id')->value('id');

        if (! $defaultBatchId) {
            return;
        }

        foreach ($this->contextTables as $tableName) {
            $this->tryStatement("UPDATE `{$tableName}` SET `program_batch_id` = {$defaultBatchId} WHERE `program_batch_id` IS NULL", [1054, 1146]);
        }
    }

    private function downMysql(): void
    {
        foreach (array_reverse($this->contextTables) as $tableName) {
            $this->tryStatement("ALTER TABLE `{$tableName}` DROP FOREIGN KEY `{$this->foreignName($tableName)}`", [1025, 1091, 1146]);
            $this->tryStatement("ALTER TABLE `{$tableName}` DROP INDEX `{$this->indexName($tableName)}`", [1091, 1146]);
            $this->tryStatement("ALTER TABLE `{$tableName}` DROP COLUMN `program_batch_id`", [1091, 1146]);
        }
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

    private function indexName(string $tableName): string
    {
        return substr('pb_'.$tableName.'_idx', 0, 64);
    }

    private function foreignName(string $tableName): string
    {
        return substr('pb_'.$tableName.'_fk', 0, 64);
    }
};
