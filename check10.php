<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$restore = new \Filament\Actions\RestoreAction('restore');
echo "Parent of Filament\\Actions\\RestoreAction: " . get_parent_class($restore) . "\n";
echo "Interfaces: " . implode(', ', class_implements($restore)) . "\n";

// Let's check if Filament\Tables\Actions\RestoreAction exists, or if there is a class_alias that isn't loaded yet?
// Wait, is RestoreAction loaded dynamically? Let's check!
try {
    $class = new \Filament\Tables\Actions\RestoreAction('restore');
    echo "Filament\\Tables\\Actions\\RestoreAction instantiated successfully! Class is: " . get_class($class) . "\n";
} catch (\Throwable $e) {
    echo "Error instantiating Filament\\Tables\\Actions\\RestoreAction: " . $e->getMessage() . "\n";
}
