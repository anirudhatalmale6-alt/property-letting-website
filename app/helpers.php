<?php
/**
 * Shared helpers: configuration, output escaping, URLs, sessions, CSRF,
 * flash messages, validation, uploads and mail.
 */

declare(strict_types=1);

// ---------------------------------------------------------------------------
// Configuration
// ---------------------------------------------------------------------------

function config(?string $key = null, $default = null)
{
    static $config = null;
    if ($config === null) {
        $config = require __DIR__ . '/config.php';
    }
    if ($key === null) {
        return $config;
    }
    return $config[$key] ?? $default;
}

/** Current timestamp in the format stored in the database. */
function now(): string
{
    return gmdate('Y-m-d H:i:s');
}

// ---------------------------------------------------------------------------
// Output
// ---------------------------------------------------------------------------

/** Escape for HTML output. Used on every single dynamic value in the views. */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Renders plain text entered in the admin as HTML paragraphs, escaping it
 * first. Admin input is never treated as HTML, so a pasted <script> is shown
 * as text rather than executed.
 */
function paragraphs(?string $text): string
{
    $text = trim((string)$text);
    if ($text === '') {
        return '';
    }
    $blocks = preg_split('/\n\s*\n/', $text) ?: [];
    $html   = '';
    foreach ($blocks as $block) {
        $html .= '<p>' . nl2br(e(trim($block))) . '</p>';
    }
    return $html;
}

/** Splits a newline-separated admin field into a clean array of lines. */
function lines(?string $text): array
{
    $out = [];
    foreach (preg_split('/\r?\n/', (string)$text) ?: [] as $line) {
        $line = trim($line);
        if ($line !== '') {
            $out[] = $line;
        }
    }
    return $out;
}

/**
 * Formats an amount with the currency symbol set in the admin, so the same
 * code serves a site quoting $, £, € or anything else.
 */
function money(int $amount): string
{
    return setting('currency_symbol', '$') . number_format($amount);
}

/** "/mo", "per month", "pcm" — whatever the market expects. Set in the admin. */
function rent_period(): string
{
    return setting('rent_period_label', '/mo');
}

/**
 * "2026-10-01" -> "October 1, 2026" with the US default; passes free text such
 * as "Now" straight through. The pattern comes from the date_format setting so
 * a site outside the US can use its own convention.
 */
function pretty_date(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }
    $ts = strtotime($value);
    if ($ts === false || !preg_match('/^\d{4}-\d{2}-\d{2}/', $value)) {
        return $value;
    }
    return date(setting('date_format', 'F j, Y'), $ts);
}

/** Timestamps are stored in UTC; shown here in the format the owner chose. */
function pretty_datetime(string $value): string
{
    $ts = strtotime($value . ' UTC');
    if (!$ts) {
        return $value;
    }
    $format = setting('date_format', 'F j, Y') === 'j F Y' ? 'j M Y, H:i' : 'M j, Y, g:ia';
    return date($format, $ts);
}

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = str_replace(['’', "'"], '', $text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    return trim($text, '-') ?: 'item';
}

function excerpt(string $text, int $chars = 160): string
{
    $text = trim(preg_replace('/\s+/', ' ', $text) ?? '');
    if (mb_strlen($text) <= $chars) {
        return $text;
    }
    $cut = mb_substr($text, 0, $chars);
    $pos = mb_strrpos($cut, ' ');
    return rtrim($pos ? mb_substr($cut, 0, $pos) : $cut, ' ,.;:') . '…';
}

// ---------------------------------------------------------------------------
// URLs and routing
// ---------------------------------------------------------------------------

/**
 * The sub-directory the site is installed in, e.g. "" at a domain root or
 * "/lettings" in a sub-folder. Worked out from the front controller's path so
 * the site runs from either without configuration.
 */
function base_path(): string
{
    static $base = null;
    if ($base === null) {
        $script = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
        $base   = ($script === '/' || $script === '.') ? '' : rtrim($script, '/');
    }
    return $base;
}

/** Builds a site URL: url('/properties') => "/lettings/properties". */
function url(string $path = '/', array $query = []): string
{
    $path = '/' . ltrim($path, '/');
    $out  = base_path() . ($path === '/' ? '/' : rtrim($path, '/'));
    if ($query) {
        $out .= '?' . http_build_query($query);
    }
    return $out;
}

function asset(string $path): string
{
    $path = ltrim($path, '/');
    $file = config('public_path') . '/' . $path;
    // Cache-buster so a re-uploaded stylesheet or photo is picked up at once.
    $version = is_file($file) ? '?v=' . filemtime($file) : '';
    return base_path() . '/' . $path . $version;
}

function upload_url(string $filename): string
{
    return asset('uploads/' . $filename);
}

/**
 * URL of the logo shown in the header, or '' when there is none and the
 * business name should be set in type instead.
 *
 * A logo uploaded from Settings wins. Otherwise the bundled artwork is used if
 * it is present, which is what a fresh install ships with.
 */
function logo_url(): string
{
    $uploaded = setting('logo_file');
    if ($uploaded !== '' && is_file(config('upload_path') . '/' . basename($uploaded))) {
        return upload_url(basename($uploaded));
    }
    if (is_file(config('public_path') . '/assets/img/logo-mark.png')) {
        return asset('assets/img/logo-mark.png');
    }
    return '';
}

function redirect(string $path, array $query = []): void
{
    header('Location: ' . url($path, $query));
    exit;
}

function current_path(): string
{
    $uri  = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $base = base_path();
    if ($base !== '' && str_starts_with($uri, $base)) {
        $uri = substr($uri, strlen($base));
    }
    return '/' . trim($uri, '/');
}

/** Marks the active item in the navigation. */
function nav_active(string $path): string
{
    $current = current_path();
    if ($path === '/') {
        return $current === '/' ? ' is-active' : '';
    }
    return str_starts_with($current, $path) ? ' is-active' : '';
}

// ---------------------------------------------------------------------------
// Sessions, CSRF and flash messages
// ---------------------------------------------------------------------------

function session_start_safe(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    session_name(config('session_name'));
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => base_path() . '/',
        'httponly' => true,
        'secure'   => $https,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function csrf_token(): string
{
    session_start_safe();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    session_start_safe();
    $sent = $_POST['_token'] ?? '';
    if (!is_string($sent) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $sent)) {
        http_response_code(419);
        exit('Your session expired. Please go back, reload the page and try again.');
    }
}

function flash(string $type, string $message): void
{
    session_start_safe();
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array
{
    session_start_safe();
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

/** Keeps submitted values in the form when validation sends the user back. */
function old(string $key, $default = '')
{
    session_start_safe();
    return $_SESSION['old'][$key] ?? $default;
}

function keep_old(array $input): void
{
    session_start_safe();
    unset($input['_token']);
    $_SESSION['old'] = $input;
}

function clear_old(): void
{
    session_start_safe();
    unset($_SESSION['old']);
}

// ---------------------------------------------------------------------------
// Request input
// ---------------------------------------------------------------------------

function input(string $key, $default = ''): string
{
    $value = $_POST[$key] ?? $_GET[$key] ?? $default;
    return is_string($value) ? trim($value) : (string)$default;
}

function input_int(string $key, int $default = 0): int
{
    $value = $_POST[$key] ?? $_GET[$key] ?? null;
    if ($value === null || $value === '') {
        return $default;
    }
    return (int)preg_replace('/[^0-9\-]/', '', (string)$value);
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function client_ip(): string
{
    return (string)($_SERVER['REMOTE_ADDR'] ?? '');
}

// ---------------------------------------------------------------------------
// Settings and pages
// ---------------------------------------------------------------------------

function settings(): array
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        foreach (db()->query('SELECT key, value FROM settings') as $row) {
            $cache[$row['key']] = $row['value'];
        }
    }
    return $cache;
}

function setting(string $key, string $default = ''): string
{
    $all = settings();
    return $all[$key] ?? $default;
}

function setting_save(string $key, string $value): void
{
    db()->prepare('INSERT INTO settings (key, value) VALUES (?, ?)
                   ON CONFLICT(key) DO UPDATE SET value = excluded.value')
        ->execute([$key, $value]);
}

function page(string $slug): ?array
{
    $stmt = db()->prepare('SELECT * FROM pages WHERE slug = ?');
    $stmt->execute([$slug]);
    return $stmt->fetch() ?: null;
}

// ---------------------------------------------------------------------------
// Views
// ---------------------------------------------------------------------------

/**
 * Renders a view inside a layout. $data keys become local variables in both
 * the view and the layout.
 */
function view(string $template, array $data = [], string $layout = 'layout'): void
{
    extract($data, EXTR_SKIP);
    ob_start();
    require config('app_path') . '/views/' . $template . '.php';
    $content = ob_get_clean();
    require config('app_path') . '/views/' . $layout . '.php';
}

function not_found(string $message = 'That page could not be found.'): void
{
    http_response_code(404);
    view('404', ['title' => 'Page not found', 'message' => $message]);
    exit;
}

// ---------------------------------------------------------------------------
// Validation
// ---------------------------------------------------------------------------

function valid_email(string $email): bool
{
    return (bool)filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Rejects header-injection attempts in anything that will be placed in a mail
 * header (a name, a reply-to address).
 */
function safe_header_value(string $value): string
{
    return trim(str_replace(["\r", "\n", "%0a", "%0d"], ' ', $value));
}

// ---------------------------------------------------------------------------
// Image uploads
// ---------------------------------------------------------------------------

/**
 * Validates and stores one uploaded image.
 *
 * The file is checked by actually decoding it as an image rather than
 * trusting the extension or the browser-supplied MIME type, then re-encoded.
 * A file that is not a real JPEG/PNG/WebP cannot survive that round trip, so
 * a PHP script renamed to .jpg is rejected.
 *
 * @return array{ok: bool, filename?: string, error?: string}
 */
function store_uploaded_image(array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'error' => 'No file was selected.'];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'The upload did not complete (error code ' . (int)$file['error'] . ').'];
    }
    if (!is_uploaded_file($file['tmp_name'])) {
        return ['ok' => false, 'error' => 'The file was not received correctly.'];
    }
    if ($file['size'] > config('max_upload_bytes')) {
        $mb = round(config('max_upload_bytes') / 1048576, 1);
        return ['ok' => false, 'error' => 'That image is larger than ' . $mb . ' MB. Please save it smaller and try again.'];
    }

    $info = @getimagesize($file['tmp_name']);
    if ($info === false) {
        return ['ok' => false, 'error' => 'That file is not a readable image.'];
    }

    [$width, $height, $type] = $info;
    $readers = [
        IMAGETYPE_JPEG => 'imagecreatefromjpeg',
        IMAGETYPE_PNG  => 'imagecreatefrompng',
        IMAGETYPE_WEBP => 'imagecreatefromwebp',
    ];
    if (!isset($readers[$type]) || !function_exists($readers[$type])) {
        return ['ok' => false, 'error' => 'Please upload a JPEG, PNG or WebP image.'];
    }

    $image = @$readers[$type]($file['tmp_name']);
    if (!$image) {
        return ['ok' => false, 'error' => 'That image could not be read.'];
    }

    // Resize anything oversized so the site stays fast on a phone.
    $maxWidth = (int)config('image_max_width');
    if ($width > $maxWidth) {
        $newHeight = (int)round($height * ($maxWidth / $width));
        $resized   = imagecreatetruecolor($maxWidth, $newHeight);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $maxWidth, $newHeight, $width, $height);
        imagedestroy($image);
        $image = $resized;
    }

    $dir = config('upload_path');
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    $filename = date('Ymd') . '-' . bin2hex(random_bytes(8)) . '.jpg';
    $ok = imagejpeg($image, $dir . '/' . $filename, 82);
    imagedestroy($image);

    if (!$ok) {
        return ['ok' => false, 'error' => 'The image could not be saved. Check that the uploads folder is writable.'];
    }

    @chmod($dir . '/' . $filename, 0644);
    return ['ok' => true, 'filename' => $filename];
}

/**
 * Stores an uploaded logo.
 *
 * Kept separate from store_uploaded_image() because that one re-encodes to
 * JPEG, which would replace a logo's transparent background with white. Here a
 * PNG stays a PNG, alpha intact, so the logo sits cleanly on any color.
 *
 * @return array{ok: bool, filename?: string, error?: string}
 */
function store_uploaded_logo(array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'error' => 'No file was selected.'];
    }
    if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        return ['ok' => false, 'error' => 'The upload did not complete.'];
    }
    if ($file['size'] > config('max_upload_bytes')) {
        return ['ok' => false, 'error' => 'That file is too large.'];
    }

    $info = @getimagesize($file['tmp_name']);
    if ($info === false) {
        return ['ok' => false, 'error' => 'That file is not a readable image.'];
    }

    [$width, $height, $type] = $info;
    $readers = [
        IMAGETYPE_PNG  => ['imagecreatefrompng',  'png'],
        IMAGETYPE_JPEG => ['imagecreatefromjpeg', 'jpg'],
        IMAGETYPE_WEBP => ['imagecreatefromwebp', 'png'],
    ];
    if (!isset($readers[$type])) {
        return ['ok' => false, 'error' => 'Please upload a PNG, JPEG or WebP logo. A PNG with a transparent background looks best.'];
    }

    [$reader, $ext] = $readers[$type];
    if (!function_exists($reader)) {
        return ['ok' => false, 'error' => 'This server cannot read that image format.'];
    }

    $image = @$reader($file['tmp_name']);
    if (!$image) {
        return ['ok' => false, 'error' => 'That image could not be read.'];
    }

    // A logo is only ever shown small; 600px wide is more than enough even on
    // a high-density screen.
    $maxWidth = 600;
    if ($width > $maxWidth) {
        $newHeight = (int)round($height * ($maxWidth / $width));
        $resized   = imagecreatetruecolor($maxWidth, $newHeight);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        imagefill($resized, 0, 0, imagecolorallocatealpha($resized, 0, 0, 0, 127));
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $maxWidth, $newHeight, $width, $height);
        imagedestroy($image);
        $image = $resized;
    }

    $dir = config('upload_path');
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    $filename = 'logo-' . bin2hex(random_bytes(6)) . '.' . $ext;
    if ($ext === 'png') {
        imagealphablending($image, false);
        imagesavealpha($image, true);
        $ok = imagepng($image, $dir . '/' . $filename, 8);
    } else {
        $ok = imagejpeg($image, $dir . '/' . $filename, 90);
    }
    imagedestroy($image);

    if (!$ok) {
        return ['ok' => false, 'error' => 'The logo could not be saved. Check that the uploads folder is writable.'];
    }

    @chmod($dir . '/' . $filename, 0644);
    return ['ok' => true, 'filename' => $filename];
}

/** Turns PHP's awkward multi-file $_FILES array into one entry per file. */
function normalise_files(array $files): array
{
    if (!isset($files['name']) || !is_array($files['name'])) {
        return [];
    }
    $out = [];
    foreach (array_keys($files['name']) as $i) {
        $out[] = [
            'name'     => $files['name'][$i],
            'type'     => $files['type'][$i],
            'tmp_name' => $files['tmp_name'][$i],
            'error'    => $files['error'][$i],
            'size'     => $files['size'][$i],
        ];
    }
    return $out;
}

function delete_upload(string $filename): void
{
    // basename() stops a stored value like "../../config.php" escaping the folder.
    $path = config('upload_path') . '/' . basename($filename);
    if (is_file($path)) {
        @unlink($path);
    }
}

// ---------------------------------------------------------------------------
// Mail
// ---------------------------------------------------------------------------

/**
 * Sends a plain-text notification. Returns false if mail is disabled or the
 * host refused it — the caller decides whether that matters. An inquiry is
 * always saved to the database first, so a mail failure never loses a lead.
 */
function send_mail(string $to, string $subject, string $body, string $replyTo = ''): bool
{
    if (!config('mail_enabled')) {
        return false;
    }
    if (!valid_email($to)) {
        return false;
    }

    $fromName = safe_header_value((string)config('mail_from_name'));
    $from     = safe_header_value((string)config('mail_from'));

    $headers = [
        'From: ' . sprintf('=?UTF-8?B?%s?= <%s>', base64_encode($fromName), $from),
        'Content-Type: text/plain; charset=UTF-8',
        'MIME-Version: 1.0',
        'X-Mailer: PHP/' . PHP_VERSION,
    ];
    if ($replyTo !== '' && valid_email($replyTo)) {
        $headers[] = 'Reply-To: ' . safe_header_value($replyTo);
    }

    $subject = '=?UTF-8?B?' . base64_encode(safe_header_value($subject)) . '?=';
    $body    = wordwrap(str_replace("\r\n", "\n", $body), 78, "\n", false);

    return @mail($to, $subject, $body, implode("\r\n", $headers));
}
