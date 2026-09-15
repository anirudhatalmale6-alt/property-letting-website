<?php
/**
 * Admin authentication.
 *
 * One role only: the site owner. Passwords are stored with password_hash()
 * (bcrypt by default) and verified in constant time. Failed logins are rate
 * limited per IP address so the login form cannot be brute-forced.
 */

declare(strict_types=1);

function auth_user(): ?array
{
    session_start_safe();
    $id = $_SESSION['admin_id'] ?? null;
    if (!$id) {
        return null;
    }
    static $user = null;
    if ($user === null) {
        $stmt = db()->prepare('SELECT * FROM admins WHERE id = ?');
        $stmt->execute([$id]);
        $user = $stmt->fetch() ?: false;
    }
    return $user ?: null;
}

function auth_check(): bool
{
    return auth_user() !== null;
}

/** Guard placed at the top of every admin route. */
function require_admin(): array
{
    $user = auth_user();
    if (!$user) {
        session_start_safe();
        $_SESSION['intended'] = current_path();
        flash('error', 'Please sign in to continue.');
        redirect('/admin/login');
    }
    return $user;
}

function auth_attempt(string $username, string $password): bool
{
    $stmt = db()->prepare('SELECT * FROM admins WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    // Hash even when the user does not exist, so a wrong username and a wrong
    // password take the same amount of time to answer.
    $hash = $user['password_hash'] ?? '$2y$10$usesomesillystringforsalt0000000000000000000000000000000000';

    if (!password_verify($password, $hash) || !$user) {
        return false;
    }

    if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
        db()->prepare('UPDATE admins SET password_hash = ? WHERE id = ?')
            ->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);
    }

    session_start_safe();
    session_regenerate_id(true);           // defeats session fixation
    $_SESSION['admin_id'] = (int)$user['id'];

    db()->prepare('UPDATE admins SET last_login = ? WHERE id = ?')->execute([now(), $user['id']]);
    login_attempts_clear(client_ip());

    return true;
}

function auth_logout(): void
{
    session_start_safe();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

// --- Login rate limiting ---------------------------------------------------

function login_attempts_record(string $ip): void
{
    db()->prepare('INSERT INTO login_attempts (ip, created_at) VALUES (?, ?)')
        ->execute([$ip, time()]);
}

function login_attempts_clear(string $ip): void
{
    db()->prepare('DELETE FROM login_attempts WHERE ip = ?')->execute([$ip]);
}

/** Seconds the caller must wait, or 0 if they may try again now. */
function login_lockout_remaining(string $ip): int
{
    $window = (int)config('login_lockout_secs');
    $since  = time() - $window;

    db()->prepare('DELETE FROM login_attempts WHERE created_at < ?')->execute([$since]);

    $stmt = db()->prepare('SELECT COUNT(*) c, MAX(created_at) latest FROM login_attempts WHERE ip = ? AND created_at >= ?');
    $stmt->execute([$ip, $since]);
    $row = $stmt->fetch();

    if ((int)$row['c'] < (int)config('login_max_attempts')) {
        return 0;
    }
    return max(1, ((int)$row['latest'] + $window) - time());
}

/**
 * True while the seeded default password is still in place. The admin area
 * shows a warning until it is changed.
 */
function using_default_password(): bool
{
    $user = auth_user();
    return $user !== null && password_verify('changeme', $user['password_hash']);
}
