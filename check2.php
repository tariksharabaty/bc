<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$classes = [
    'Filament\Tables\Actions\DeleteAction',
    'Filament\Actions\DeleteAction',
    'Filament\Tables\Actions\EditAction',
    'Filament\Actions\RestoreAction',
];

foreach ($classes as $class) {
    if (class_exists($class)) {
        $ref = new ReflectionClass($class);
        echo "$class: " . $ref->getFileName() . "\n";
    } else {
        echo "$class: NOT FOUND\n";
    }
}
