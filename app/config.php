<?php
/**
 * Application configuration.
 *
 * Every value can be overridden without touching this file by creating
 * config.local.php alongside it and returning an array of overrides:
 *
 *     <?php return ['mail_from' => 'noreply@example.com'];
 *
 * config.local.php is git-ignored so your live credentials never end up in
 * the repository.
 */

declare(strict_types=1);

$config = [
    // --- Paths -----------------------------------------------------------
    'root_path'    => dirname(__DIR__),
    'app_path'     => __DIR__,
    'public_path'  => dirname(__DIR__) . '/public',
    'upload_path'  => dirname(__DIR__) . '/public/uploads',
    'data_path'    => dirname(__DIR__) . '/data',
    'db_file'      => dirname(__DIR__) . '/data/site.sqlite',

    // --- Environment -----------------------------------------------------
    // 'dev' shows errors on screen, 'prod' hides them and logs instead.
    'env'          => 'prod',

    // --- Mail ------------------------------------------------------------
    // Notifications are sent with PHP's mail(). On most shared hosting that
    // works out of the box. If your host blocks it, see docs/INSTALL.md for
    // the SMTP alternative.
    'mail_enabled' => true,
    'mail_from'    => 'website@example.com',
    'mail_from_name' => 'Website',

    // --- Uploads ---------------------------------------------------------
    'max_upload_bytes'  => 6 * 1024 * 1024, // 6 MB per image
    'allowed_image_ext' => ['jpg', 'jpeg', 'png', 'webp'],
    'image_max_width'   => 1600,            // large images are resized down

    // --- Security --------------------------------------------------------
    'session_name'      => 'pmsite',
    'login_max_attempts'=> 5,               // per 15 minutes, per IP
    'login_lockout_secs'=> 900,

    // --- GDPR ------------------------------------------------------------
    // Enquiries older than this are flagged in the admin as due for deletion.
    // Nothing is ever auto-deleted; you decide.
    'enquiry_retention_days' => 730,
];

$localFile = __DIR__ . '/config.local.php';
if (is_file($localFile)) {
    $overrides = require $localFile;
    if (is_array($overrides)) {
        $config = array_merge($config, $overrides);
    }
}

return $config;
