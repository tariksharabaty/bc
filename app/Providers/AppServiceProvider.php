<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\HtmlString;

use BezhanSalleh\LanguageSwitch\LanguageSwitch;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if (!class_exists('Filament\Tables\Actions\EditAction')) {
            class_alias('Filament\Actions\EditAction', 'Filament\Tables\Actions\EditAction');
        }
        if (!class_exists('Filament\Tables\Actions\DeleteAction')) {
            class_alias('Filament\Actions\DeleteAction', 'Filament\Tables\Actions\DeleteAction');
        }
        if (!class_exists('Filament\Tables\Actions\Action')) {
            class_alias('Filament\Actions\Action', 'Filament\Tables\Actions\Action');
        }
        if (!class_exists('Filament\Tables\Actions\BulkActionGroup')) {
            class_alias('Filament\Actions\BulkActionGroup', 'Filament\Tables\Actions\BulkActionGroup');
        }
        if (!class_exists('Filament\Tables\Actions\DeleteBulkAction')) {
            class_alias('Filament\Actions\DeleteBulkAction', 'Filament\Tables\Actions\DeleteBulkAction');
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        LanguageSwitch::configureUsing(function (LanguageSwitch $switch) {
            $switch
                ->locales(['tr', 'en', 'ar']);
        });

        FilamentView::registerRenderHook(
            PanelsRenderHook::HEAD_END,
            fn (): HtmlString => new HtmlString('
                <link rel="manifest" href="/manifest.json">
                <meta name="theme-color" content="#f97316">
                <script>
                    if (\'serviceWorker\' in navigator) {
                        window.addEventListener(\'load\', function() {
                            navigator.serviceWorker.register(\'/sw.js\').then(function(registration) {
                                console.log(\'ServiceWorker registration successful with scope: \', registration.scope);
                            }, function(err) {
                                console.log(\'ServiceWorker registration failed: \', err);
                            });
                        });
                    }
                </script>
            ')
        );
    }
}
