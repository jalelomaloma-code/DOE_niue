<?php

namespace App\Filament\Widgets;

use App\Enums\ProjectStatus;
use App\Models\Project;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Counts projects by project_status (Planned, Active, Completed, On Hold)
 * -- distinct from the workflow status ContentOverview already covers.
 * Also not published()-scoped, for the same reason as ContentOverview.
 */
class ProjectLifecycle extends StatsOverviewWidget
{
    protected ?string $heading = 'Website Project Lifecycle';

    protected static ?int $sort = 20;

    protected function getStats(): array
    {
        // One grouped aggregate rather than four separate COUNT() calls.
        $counts = Project::query()
            ->selectRaw('project_status, count(*) as aggregate')
            ->groupBy('project_status')
            ->pluck('aggregate', 'project_status');

        return collect(ProjectStatus::cases())
            ->map(fn (ProjectStatus $status) => Stat::make(
                $status->label(),
                (string) (int) ($counts[$status->value] ?? 0),
            )->color($status->color()))
            ->all();
    }
}
