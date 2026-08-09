<?php

namespace App\Providers;

use App\Models\NavigationItem;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('components.site.*', function ($view) {
            $view->with('settings', SiteSetting::current());

            // Resolved once here rather than in the header itself: the
            // header renders two lists (desktop + mobile disclosure) from
            // the same data, so sharing it through the composer keeps that
            // to one query instead of two.
            $view->with('navigation', NavigationItem::active()->get());
        });
    }
}
