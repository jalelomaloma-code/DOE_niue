<?php

use App\Http\Responses\PanelAwareLoginResponse;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Illuminate\Http\Request;
use Tests\TestCase;

uses(TestCase::class);

it('returns a department login to the dashboard instead of a stale admin destination', function () {
    $request = Request::create('/livewire/update', 'POST', server: [
        'HTTP_HOST' => 'localhost',
        'HTTP_REFERER' => 'http://localhost/dashboard/login',
    ]);
    $request->setLaravelSession($this->app['session']->driver());
    $request->session()->put('url.intended', 'http://localhost/admin');

    $response = app(LoginResponse::class)->toResponse($request);

    expect(app(LoginResponse::class))->toBeInstanceOf(PanelAwareLoginResponse::class)
        ->and($response->getTargetUrl())->toBe(url('/dashboard'));
});

it('returns an admin login to the CMS instead of a stale department destination', function () {
    $request = Request::create('/livewire/update', 'POST', server: [
        'HTTP_HOST' => 'localhost',
        'HTTP_REFERER' => 'http://localhost/admin/login',
    ]);
    $request->setLaravelSession($this->app['session']->driver());
    $request->session()->put('url.intended', 'http://localhost/dashboard');

    $response = app(LoginResponse::class)->toResponse($request);

    expect($response->getTargetUrl())->toBe(url('/admin'));
});
