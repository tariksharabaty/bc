<?php

$dir = new RecursiveDirectoryIterator(__DIR__ . '/../app/Filament');
$iterator = new RecursiveIteratorIterator($dir);

$patterns = [
    '/Tables\\\\Actions\\\\ActionGroup/',
    '/Tables\\\\Actions\\\\BulkActionGroup/',
];

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                echo "FOUND obsolete pattern in: " . $file->getPathname() . "\n";
            }
        }
    }
}
echo "Search completed!\n";
