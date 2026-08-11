<?php

namespace App\Providers;

use App\Http\Responses\PanelAwareLoginResponse;
use App\Models\NavigationItem;
use App\Models\SiteSetting;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(LoginResponse::class, PanelAwareLoginResponse::class);
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
            $navigation = NavigationItem::active()->get();

            $view->with('navigation', $navigation);
            $view->with('primaryNavigation', collect([
                (object) ['label' => 'Home', 'url' => '/'],
                (object) ['label' => 'About Us', 'url' => '/about'],
                (object) [
                    'label' => 'Branches',
                    'url' => '/our-work',
                    'children' => [
                        (object) ['label' => 'Environmental Governance', 'url' => '/our-work/environmental-governance'],
                        (object) ['label' => 'Biodiversity and Conservation', 'url' => '/our-work/biodiversity-and-conservation'],
                        (object) ['label' => 'Climate Change and Ozone', 'url' => '/our-work/climate-change-and-ozone'],
                        (object) ['label' => 'Waste Management and Pollution Control', 'url' => '/our-work/waste-management-and-pollution-control'],
                    ],
                ],
                (object) ['label' => 'Resources', 'url' => '/resources'],
                (object) ['label' => 'Contact Us', 'url' => '/contact'],
            ]));
        });
    }
}
