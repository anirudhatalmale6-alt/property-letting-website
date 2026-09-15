<?php
/**
 * Front controller. Every request to the site arrives here and is dispatched
 * from app/routes.php. Nothing else in public/ is executable, so there is a
 * single way in and a single place where routing is defined.
 */

declare(strict_types=1);

$app = dirname(__DIR__) . '/app';

require $app . '/helpers.php';

// Error display follows the env setting so a live site never leaks a stack
// trace containing file paths.
if (config('env') === 'dev') {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    error_reporting(E_ALL);
}

require $app . '/db.php';
require $app . '/auth.php';
require $app . '/properties.php';
require $app . '/controllers/site.php';
require $app . '/controllers/admin.php';

// Baseline security headers. A Content-Security-Policy is set too; the site
// loads no third-party scripts, so it can be strict.
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; "
     . "style-src 'self' 'unsafe-inline'; script-src 'self'; frame-src https:; "
     . "form-action 'self'; base-uri 'self'; frame-ancestors 'self'");

require $app . '/routes.php';
