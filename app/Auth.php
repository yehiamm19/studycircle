<?php

declare(strict_types=1);

namespace App;

class Auth
{
    public static function start(): void
    {
        if (session_status() !== PHP_SESSION_NONE) {
            return;
        }

        $lifetime = (int) config('session_lifetime', 86400 * 7);
        if ($lifetime > 0) {
            ini_set('session.gc_maxlifetime', (string) $lifetime);
        }

        ini_set('session.cookie_httponly', '1');
        ini_set('session.use_strict_mode', '1');
        ini_set('session.cookie_samesite', 'Lax');

        $secure = self::connectionUsesHttps();
        ini_set('session.cookie_secure', $secure ? '1' : '0');

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
    }

    private static function connectionUsesHttps(): bool
    {
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }
        if (isset($_SERVER['SERVER_PORT']) && (string) $_SERVER['SERVER_PORT'] === '443') {
            return true;
        }
        if (!empty(config('trust_https_proxy', false))) {
            return strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
        }

        return false;
    }

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function id(): ?int
    {
        $id = self::user()['id'] ?? null;
        return $id !== null ? (int) $id : null;
    }

    /** Keep session xp/streak/role/name in sync with DB (Gamification updates DB but not $_SESSION alone). */
    public static function syncSessionUser(): void
    {
        $id = self::id();
        if ($id === null) {
            return;
        }
        $fresh = \App\Models\User::refreshSession($id);
        if ($fresh !== null) {
            $_SESSION['user'] = $fresh;

            return;
        }
        // User removed from DB; clear stale session
        unset($_SESSION['user']);
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function login(array $user): void
    {
        session_regenerate_id(true);
        unset($user['password']);
        $_SESSION['user'] = $user;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    public static function isAdmin(): bool
    {
        return (self::user()['role'] ?? '') === 'admin';
    }

    public static function requireAuth(): void
    {
        if (!self::check()) {
            flash('error', 'Please sign in to continue.');
            redirect('/login');
        }
    }

    public static function guestOnly(): void
    {
        if (self::check()) {
            redirect(self::isAdmin() ? '/admin' : '/dashboard');
        }
    }

    public static function requireAdmin(): void
    {
        self::requireAuth();
        if (!self::isAdmin()) {
            flash('error', 'Admin access only.');
            redirect('/dashboard');
        }
    }
}

