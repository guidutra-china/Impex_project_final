<?php

namespace App\Providers\Filament;

use App\Filament\Pages\EditProfile;
use App\Filament\Pages\WidgetSelectorPage;
use App\Filament\Widgets\RfqStatsWidget;
use App\Filament\Widgets\PurchaseOrderStatsWidget;
use App\Filament\Widgets\FinancialOverviewWidget;
use App\Filament\Widgets\CalendarWidget;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use App\Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Http\Middleware\SetLocale;

class AdminPanelProvider extends PanelProvider
{
    public function boot(): void
    {
        // Set locale from authenticated user before Filament loads
        if (auth()->check() && auth()->user()->locale) {
            app()->setLocale(auth()->user()->locale);
        }
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('panel')
            ->login()
            ->registration()
            ->passwordReset()
            ->emailverification()
            ->defaultAvatarProvider(\Filament\AvatarProviders\UiAvatarsProvider::class)
            ->userMenuItems([
                'profile' => \Filament\Navigation\MenuItem::make()
                    ->label('Profile')
                    ->url(fn (): string => EditProfile::getUrl())
                    ->icon('heroicon-o-user-circle'),
            ])
            ->sidebarCollapsibleOnDesktop()
            ->sidebarFullyCollapsibleOnDesktop()
            ->brandName('IMPEX')
            ->brandLogo(asset('images/logo.svg'))
            ->darkModeBrandLogo(asset('images/logo-dark.svg'))
            ->brandLogoHeight('2.5rem')
            ->favicon(asset('images/logo.svg'))
            ->colors([
                'primary' => Color::Indigo,
                'danger' => Color::Red,
                'gray' => Color::Zinc,
                'info' => Color::Blue,
                'success' => Color::Green,
                'warning' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
                WidgetSelectorPage::class,
            ])
            ->navigationGroups([
                'Sales & Quotations',
                'Purchasing',
                'Logistics & Shipping',
                'Finance',
                'Documents',
                'Contacts',
                'Inventory',
                'Settings',
                'Security',
            ])
            ->plugin(
                \BezhanSalleh\FilamentShield\FilamentShieldPlugin::make()
                    ->navigationGroup('Security')
                    ->navigationSort(20)
                    ->navigationLabel('Roles & Permissions')
                    ->navigationIcon('heroicon-o-shield-check')
            )
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                CalendarWidget::class,
                RfqStatsWidget::class,
                PurchaseOrderStatsWidget::class,
                FinancialOverviewWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                SetLocale::class, // Set locale from user preference
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
