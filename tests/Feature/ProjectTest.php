<?php

use App\Enums\ContentStatus;
use App\Enums\ProjectStatus;
use App\Models\Programme;
use App\Models\Project;

it('keeps the editorial status and the project lifecycle independent', function () {
    $project = Project::factory()->create([
        'status' => ContentStatus::Draft,
        'project_status' => ProjectStatus::Active,
    ]);

    expect($project->status)->toBe(ContentStatus::Draft)
        ->and($project->project_status)->toBe(ProjectStatus::Active)
        ->and(Project::published()->count())->toBe(0);
});

it('belongs to a programme', function () {
    $programme = Programme::factory()->create(['title' => 'Waste Management']);
    $project = Project::factory()->create(['programme_id' => $programme->id]);

    expect($project->programme->title)->toBe('Waste Management')
        ->and($programme->projects)->toHaveCount(1);
});

it('casts status and project_status independently on a freshly loaded record', function () {
    // Re-fetch from the database rather than reusing the in-memory instance:
    // an enum assigned in memory reads back identical even with no cast at
    // all (PHP backed-enum cases are singletons), so the assertions above
    // don't actually prove the casts exist. Only a fresh load forces
    // Eloquent to rehydrate the raw DB string through the enum cast.
    $project = Project::factory()->create([
        'status' => ContentStatus::Published,
        'project_status' => ProjectStatus::Completed,
        'published_at' => now()->subDay(),
    ]);

    $fresh = Project::findOrFail($project->id);

    expect($fresh->status)->toBe(ContentStatus::Published)
        ->and($fresh->project_status)->toBe(ProjectStatus::Completed)
        ->and($fresh->isPublished())->toBeTrue();
});
