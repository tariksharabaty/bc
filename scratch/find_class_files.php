<?php

$dir = new RecursiveDirectoryIterator(__DIR__ . '/../vendor/filament');
$iterator = new RecursiveIteratorIterator($dir);
$files = new RegexIterator($iterator, '/(ActionGroup|BulkActionGroup)\.php$/i', RecursiveRegexIterator::GET_MATCH);

foreach ($files as $file) {
    echo $iterator->getPathname() . "\n";
}
