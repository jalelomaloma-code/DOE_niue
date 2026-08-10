<?php

namespace App\Providers\Filament;

use App\Filament\Department\Pages\DepartmentDashboard;
use App\Filament\Department\Pages\Documents;
use App\Filament\Department\Pages\FullDashboardPreview;
use App\Filament\Department\Pages\HrStaff;
use App\Filament\Department\Pages\Leave;
use App\Filament\Department\Pages\Overview;
use App\Filament\Department\Pages\Settings;
use App\Filament\Department\Pages\WebsiteAnalytics;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class DepartmentDashboardPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('department')
            ->path('dashboard')
            ->login()
            ->colors([
                'primary' => Color::hex('#0B8790'),
                'success' => Color::hex('#287A4B'),
                'warning' => Color::hex('#E59A2F'),
            ])
            ->brandName('Environment Department Portal')
            ->brandLogo(asset('images/niue-doe-logo.png'))
            ->brandLogoHeight('2.4rem')
            ->viteTheme('resources/css/filament/department/theme.css')
            ->discoverPages(in: app_path('Filament/Department/Pages'), for: 'App\Filament\Department\Pages')
            ->pages([
                DepartmentDashboard::class,
                Overview::class,
                HrStaff::class,
                Leave::class,
                WebsiteAnalytics::class,
                Documents::class,
                Settings::class,
                FullDashboardPreview::class,
            ])
            ->navigationItems([
                NavigationItem::make('Director')
                    ->icon(Heroicon::OutlinedUserCircle)
                    ->url(fn (): string => DepartmentDashboard::getUrl(panel: 'department'))
                    ->isActiveWhen(fn (): bool => request()->routeIs('filament.department.pages.department-dashboard'))
                    ->sort(1),
                NavigationItem::make('Overview')
                    ->icon(Heroicon::OutlinedSquares2x2)
                    ->url(fn (): string => Overview::getUrl(panel: 'department'))
                    ->isActiveWhen(fn (): bool => request()->routeIs('filament.department.pages.overview'))
                    ->sort(2),
                NavigationItem::make('HR & Staff')
                    ->icon(Heroicon::OutlinedUsers)
                    ->url(fn (): string => HrStaff::getUrl(panel: 'department'))
                    ->isActiveWhen(fn (): bool => request()->routeIs('filament.department.pages.hr-staff'))
                    ->sort(3),
                NavigationItem::make('Leave')
                    ->icon(Heroicon::OutlinedCalendarDays)
                    ->url(fn (): string => Leave::getUrl(panel: 'department'))
                    ->isActiveWhen(fn (): bool => request()->routeIs('filament.department.pages.leave'))
                    ->sort(4),
                NavigationItem::make('Website Analytics')
                    ->icon(Heroicon::OutlinedChartBar)
                    ->url(fn (): string => WebsiteAnalytics::getUrl(panel: 'department'))
                    ->isActiveWhen(fn (): bool => request()->routeIs('filament.department.pages.website-analytics'))
                    ->sort(5),
                NavigationItem::make('Documents')
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->url(fn (): string => Documents::getUrl(panel: 'department'))
                    ->isActiveWhen(fn (): bool => request()->routeIs('filament.department.pages.documents'))
                    ->sort(6),
                NavigationItem::make('Settings')
                    ->icon(Heroicon::OutlinedCog6Tooth)
                    ->url(fn (): string => Settings::getUrl(panel: 'department'))
                    ->isActiveWhen(fn (): bool => request()->routeIs('filament.department.pages.settings'))
                    ->sort(7),
                NavigationItem::make('Website Management')
                    ->icon(Heroicon::OutlinedTableCells)
                    ->url(fn (): string => url('/admin'))
                    ->sort(8),
                NavigationItem::make('Full Dashboard Preview')
                    ->icon(Heroicon::OutlinedPresentationChartLine)
                    ->url(fn (): string => FullDashboardPreview::getUrl(panel: 'department'))
                    ->visible(fn (): bool => auth()->user()?->mayPublish() ?? false)
                    ->isActiveWhen(fn (): bool => request()->routeIs('filament.department.pages.full-dashboard-preview'))
                    ->sort(9),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
