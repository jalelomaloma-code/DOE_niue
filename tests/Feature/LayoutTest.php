<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

beforeEach(function () {
    // The probe view is a test fixture, not an application view.
    View::addLocation(base_path('tests/Fixtures/views'));
    Route::get('/__layout-probe', fn () => view('probe'));
});

it('renders the document shell with a skip link and dynamic title', function () {
    $response = $this->withoutVite()->get('/__layout-probe');

    $response->assertOk();
    $response->assertSee('Skip to main content');
    $response->assertSee('<html lang="en"', false);
    $response->assertSee('<main', false);
});
