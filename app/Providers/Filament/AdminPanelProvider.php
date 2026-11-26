<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\AdminAlertsWidget;
use App\Filament\Widgets\AdminStatsOverviewWidget;
use App\Filament\Widgets\LatestOrdersTableWidget;
use App\Filament\Widgets\LatestReceiptsTableWidget;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('SiLeuit.id')
            ->font('Inter')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->darkMode(false)
            ->colors([
                'primary' => Color::hex('#2F5A27'),
                'gray' => Color::hex('#5B6655'),
            ])
            ->navigationItems([
                NavigationItem::make('Aplikasi Lapangan (Mobile/POS)')
                    ->url('/app/beranda')
                    ->icon('heroicon-o-device-phone-mobile')
                    ->group('Aplikasi Operasional')
                    ->sort(-2),
                NavigationItem::make('Audit Trail & Log Sistem')
                    ->url('/app/audit')
                    ->icon('heroicon-o-shield-check')
                    ->group('Aplikasi Operasional')
                    ->sort(-1)
                    ->visible(fn (): bool => auth()->user()?->can('lihat-audit') ?? false),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AdminAlertsWidget::class,
                AdminStatsOverviewWidget::class,
                LatestReceiptsTableWidget::class,
                LatestOrdersTableWidget::class,
                AccountWidget::class,
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
            ])
            ->renderHook(
                PanelsRenderHook::SCRIPTS_AFTER,
                fn (): string => Blade::render('@vite(["resources/js/app.js"])')
            );
    }
}
