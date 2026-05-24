<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\App;

App::setLocale('ar');
echo "Locale set to: " . App::getLocale() . "\n";
echo "Translation of filament-panels::layout.direction: " . __('filament-panels::layout.direction') . "\n";

App::setLocale('tr');
echo "Locale set to: " . App::getLocale() . "\n";
echo "Translation of filament-panels::layout.direction: " . __('filament-panels::layout.direction') . "\n";

App::setLocale('en');
echo "Locale set to: " . App::getLocale() . "\n";
echo "Translation of filament-panels::layout.direction: " . __('filament-panels::layout.direction') . "\n";
