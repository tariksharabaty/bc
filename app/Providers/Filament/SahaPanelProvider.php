<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\View\PanelsRenderHook;

class SahaPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('saha')
            ->path('saha')
            ->login(\App\Filament\Pages\Auth\CustomLogin::class)
            ->brandName(fn () => __('system.kumbara_takip_sistemi'))
            ->darkMode(true)
            ->defaultThemeMode(\Filament\Enums\ThemeMode::System)
            ->renderHook(PanelsRenderHook::GLOBAL_SEARCH_BEFORE, fn () => view('filament.theme-toggle'))
            ->renderHook(PanelsRenderHook::FOOTER, fn (): string => \Illuminate\Support\Facades\Blade::render('filament.footer'))
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->sidebarCollapsibleOnDesktop()
            ->colors([
                'primary' => Color::Indigo,
            ])
            ->resources([
                \App\Filament\Resources\PiggyBanks\PiggyBankResource::class,
                \App\Filament\Resources\Transactions\TransactionResource::class,
            ])
            ->discoverPages(in: app_path('Filament/Saha/Pages'), for: 'App\Filament\Saha\Pages')
            ->pages([
                \App\Filament\Saha\Pages\SahaDashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Saha/Widgets'), for: 'App\Filament\Saha\Widgets')
            ->widgets([
                AccountWidget::class,
                \App\Filament\Saha\Widgets\SahaDashboardActions::class,
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
