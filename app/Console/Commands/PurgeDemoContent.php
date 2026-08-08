<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Models\NewsArticle;
use App\Models\Programme;
use App\Models\Project;
use Illuminate\Console\Command;

class PurgeDemoContent extends Command
{
    protected $signature = 'demo:purge {--force : Skip confirmation}';

    protected $description = 'Permanently delete all content flagged is_demo';

    /**
     * Projects first: they reference programmes via a foreign key. Deleting
     * a Programme before its demo Projects would fail the constraint (or,
     * since it's nullOnDelete, silently orphan a still-live Project) —
     * either way, children must go before parents.
     */
    private const MODELS = [Project::class, NewsArticle::class, Document::class, Programme::class];

    public function handle(): int
    {
        $total = collect(self::MODELS)->sum(fn (string $model) => $model::where('is_demo', true)->count());

        if ($total === 0) {
            $this->info('No demo content found.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("Permanently delete {$total} demo records?")) {
            $this->warn('Aborted.');

            return self::FAILURE;
        }

        foreach (self::MODELS as $model) {
            // Delete individually via cursor()->each->delete() rather than a
            // mass ::where(...)->delete(): a mass delete runs straight to SQL
            // and skips Eloquent model events entirely, so medialibrary never
            // gets the "deleting" event it needs to remove the attached
            // files. Individual deletion is the only way that also cleans up
            // disk storage, not just database rows.
            $model::where('is_demo', true)->cursor()->each->delete();
        }

        $this->info("Deleted {$total} demo records and their media.");

        return self::SUCCESS;
    }
}
