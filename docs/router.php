<?php
/**
 * Router for PHP's built-in web server — for local previewing only.
 *
 *     php -S localhost:8000 -t public docs/router.php
 *
 * It serves real files (stylesheets, scripts, uploaded photographs) straight
 * from disk and hands everything else to the front controller, which is what
 * the .htaccess rewrite does on a real Apache host. This file has no part in
 * running the live site.
 */

declare(strict_types=1);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$file = __DIR__ . '/../public' . $path;

// Returning false tells the built-in server to serve the file itself.
if ($path !== '/' && is_file($file) && !str_ends_with($file, '.php')) {
    return false;
}

require __DIR__ . '/../public/index.php';
