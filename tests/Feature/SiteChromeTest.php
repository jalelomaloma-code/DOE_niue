<?php

use App\Models\SiteSetting;

beforeEach(function () {
    SiteSetting::current()->update([
        'department_name' => 'Niue Department of Environment',
        'government_name' => 'Government of Niue',
        'email' => 'environment@mail.gov.nu',
    ]);
});

it('shows the government identity and contact details from the database', function () {
    $response = $this->withoutVite()->get('/');

    $response->assertOk();
    $response->assertSee('Niue Department of Environment');
    $response->assertSee('Government of Niue');
    $response->assertSee('environment@mail.gov.nu');
});

it('marks the current navigation item with aria-current', function () {
    $this->withoutVite()->get('/')->assertSee('aria-current="page"', false);
});

it('renders a branded 404 page', function () {
    $this->withoutVite()->get('/no-such-page')
        ->assertNotFound()
        ->assertSee('Page not found');
});
