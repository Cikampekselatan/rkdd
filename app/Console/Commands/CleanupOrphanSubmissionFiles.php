<?php

namespace App\Console\Commands;

use App\Models\SubmissionFile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanupOrphanSubmissionFiles extends Command
{
    protected $signature = 'submissions:cleanup-orphans {--dry-run}';

    protected $description = 'Hapus file submission privat yang tidak lagi memiliki record database.';

    public function handle(): int
    {
        $disk = Storage::disk('local');
        $known = SubmissionFile::pluck('stored_path')->all();
        $orphans = collect($disk->allFiles('submissions'))->diff($known);
        if (! $this->option('dry-run')) {
            $orphans->each(fn ($path) => $disk->delete($path));
        }$this->info($orphans->count().' file orphan '.($this->option('dry-run') ? 'ditemukan.' : 'dihapus.'));

        return self::SUCCESS;
    }
}
