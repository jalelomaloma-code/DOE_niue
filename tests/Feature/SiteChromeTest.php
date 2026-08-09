<?php

use App\Models\NavigationItem;
use App\Models\SiteSetting;

beforeEach(function () {
    SiteSetting::current()->update([
        'department_name' => 'Niue Department of Environment',
        'government_name' => 'Government of Niue',
        'email' => 'environment@mail.gov.nu',
    ]);

    // Navigation is database-driven (Task 10): without a seeded item for
    // '/', the header renders no links at all and nothing can carry
    // aria-current for the homepage.
    NavigationItem::create(['label' => 'Home', 'url' => '/', 'sort_order' => 0]);
});

it('shows the government identity and contact details from the database', function () {
    $response = $this->withoutVite()->get('/');

    $response->assertOk();
    $response->assertSee('Niue Department of Environment');
    $response->assertSee('Government of Niue');
    $response->assertSee('environment@mail.gov.nu');
});

it('marks the current navigation item with aria-current', function () {
    // Asserting occurrence COUNT, not just presence: the header renders two
    // independent lists server-side (desktop <ul> + mobile <details>, both
    // unconditionally present in the HTML — a closed <details> hides its
    // body at the browser via shadow-DOM slot assignment, not by omitting
    // it from the response). A regression in only one loop's $isCurrent
    // logic is invisible to assertSee(), since the other loop's correct
    // output still makes the string present somewhere on the page. Exactly
    // 2 occurrences (one per list) is the only assertion that can catch a
    // one-list-only break.
    $content = $this->withoutVite()->get('/')->getContent();

    expect(substr_count($content, 'aria-current="page"'))->toBe(2);
});

it('renders a branded 404 page', function () {
    $this->withoutVite()->get('/no-such-page')
        ->assertNotFound()
        ->assertSee('Page not found');
});
