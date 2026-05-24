<?php

require __DIR__ . '/../vendor/autoload.php';

$classes = [
    'Filament\Tables\Actions\ActionGroup',
    'Filament\Actions\ActionGroup',
    'Filament\Tables\Actions\BulkActionGroup',
];

foreach ($classes as $class) {
    echo $class . ': ' . (class_exists($class) ? 'EXISTS' : 'NOT FOUND') . "\n";
}
