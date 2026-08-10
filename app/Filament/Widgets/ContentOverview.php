<?php

namespace App\Filament\Widgets;

use App\Enums\ContentStatus;
use App\Models\Document;
use App\Models\NewsArticle;
use App\Models\Programme;
use App\Models\Project;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;

/**
 * First widget an editor sees on login: how much content exists per type,
 * broken down by workflow status. Deliberately NOT scoped with the model's
 * published() scope -- that scope protects the public site by hiding
 * drafts and future-dated items, which is the opposite of what an editor
 * needs to see here.
 *
 * Ordering leaves room for Spec 4/5: an "Environmental Reports" and/or
 * "Contact Submissions" overview widget can slot in at sort 15-19, between
 * this widget and ProjectLifecycle (sort 20), without renumbering anything.
 */
class ContentOverview extends StatsOverviewWidget
{
    protected ?string $heading = 'Website Content Overview';

    protected static ?int $sort = 10;

    protected function getStats(): array
    {
        return [
            $this->statFor('Programmes', Programme::class),
            $this->statFor('Projects', Project::class),
            $this->statFor('News Articles', NewsArticle::class),
            $this->statFor('Documents', Document::class),
        ];
    }

    /** @param  class-string<Model>  $model */
    protected function statFor(string $label, string $model): Stat
    {
        // One grouped aggregate per model beats four separate COUNT()
        // calls (one per status).
        $counts = $model::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $total = (int) $counts->sum();

        // A fresh install (or a content type nobody has touched yet) gets
        // a plain empty-state message rather than "0 Draft · 0 Under
        // Review · 0 Published · 0 Archived", which reads like real data.
        $description = $total === 0
            ? 'None yet'
            : collect(ContentStatus::cases())
                ->map(fn (ContentStatus $status) => sprintf(
                    '%d %s',
                    (int) ($counts[$status->value] ?? 0),
                    $status->label(),
                ))
                ->implode(' · ');

        return Stat::make($label, (string) $total)
            ->description($description);
    }
}
