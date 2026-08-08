<?php

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Gate;

// Why this test exists: Filament allows a resource action by default when
// its model has no matching Policy. Filament\Resources\Resource\Concerns\
// HasAuthorization (via the Filament\get_authorization_response() helper)
// returns Response::allow() when Gate::getPolicyFor() finds nothing --
// which is the OPPOSITE of Laravel's raw Gate, which denies an ability with
// no policy registered. Panel "strict mode", which would flip Filament's
// default to deny, is off and nothing in this codebase turns it on.
//
// This has already happened twice: QuickLinkResource shipped with no
// policy (Task 13), then NewsCategoryResource and DocumentCategoryResource
// were found the same way in the follow-up review -- both silently wide
// open to every panel role, including Viewer, for create/update/delete.
// A code-comment warning (see App\Policies\QuickLinkPolicy) does not hold
// the line on its own; this test is the permanent guard. It fails whenever
// a resource is registered on the admin panel without a policy for its
// model, and names the offending resource so the fix is obvious.
it('has a policy for every model backing a resource on the admin panel', function () {
    $panel = Filament::getPanel('admin');

    $resourcesMissingPolicies = collect($panel->getResources())
        ->filter(fn (string $resource) => Gate::getPolicyFor($resource::getModel()) === null)
        ->values()
        ->all();

    expect($resourcesMissingPolicies)->toBeEmpty(
        'These resources have no Policy for their model, which means Filament '
        .'allows every panel role -- including Viewer -- to create, update and '
        .'delete their records (Filament allows by default with no policy; '
        .'Laravel\'s raw Gate denies by default -- see the comment above): '
        .implode(', ', $resourcesMissingPolicies)
        .'. Add a {Model}Policy for each one -- App\Policies\QuickLinkPolicy shows the shape.'
    );
});
