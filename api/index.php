<?php
// api/index.php - Vercel Serverless Entrypoint & Router for Ayerko Beads

$rawUri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($rawUri, PHP_URL_PATH);
$path = trim($path, '/');

// Root request -> index.php
if (empty($path)) {
    chdir(__DIR__ . '/..');
    require __DIR__ . '/../index.php';
    exit;
}

// Handle .php or clean URLs
$candidates = [
    __DIR__ . '/../' . $path,
    __DIR__ . '/../' . $path . '.php',
    __DIR__ . '/../' . $path . '/index.php'
];

foreach ($candidates as $file) {
    if (file_exists($file) && !is_dir($file) && str_ends_with($file, '.php')) {
        chdir(dirname($file));
        require $file;
        exit;
    }
}

// Fallback to index.php
chdir(__DIR__ . '/..');
require __DIR__ . '/../index.php';
