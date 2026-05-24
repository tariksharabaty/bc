<?php

require __DIR__ . '/../vendor/autoload.php';

try {
    $ag = \Filament\Actions\ActionGroup::make([]);
    echo "ActionGroup instantiated successfully!\n";
} catch (\Throwable $e) {
    echo "ActionGroup Error: " . $e->getMessage() . "\n";
}

try {
    $bag = \Filament\Actions\BulkActionGroup::make([]);
    echo "BulkActionGroup instantiated successfully!\n";
} catch (\Throwable $e) {
    echo "BulkActionGroup Error: " . $e->getMessage() . "\n";
}
