<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

beforeEach(function () {
    // The probe views are test fixtures, not application views.
    View::addLocation(base_path('tests/Fixtures/views'));
    Route::get('/__layout-probe', fn () => view('probe'));
    Route::get('/__layout-probe-no-description', fn () => view('probe-no-description'));
});

it('renders the document shell with a skip link and dynamic title', function () {
    $response = $this->withoutVite()->get('/__layout-probe');

    $response->assertOk();
    $response->assertSee('Skip to main content');
    $response->assertSee('<html lang="en"', false);
    $response->assertSee('<main id="main"', false);
    $response->assertSee('<title>Probe — Niue Department of Environment</title>', false);
    $response->assertSee('<meta name="description" content="Probe description">', false);
});

it('omits the meta description tag when no description is passed', function () {
    $response = $this->withoutVite()->get('/__layout-probe-no-description');

    $response->assertOk();
    $response->assertSee('<title>Probe — Niue Department of Environment</title>', false);
    $response->assertDontSee('<meta name="description"', false);
});
