<?php

declare(strict_types=1);

use App\Auth;
use App\Database;

function config(string $key, mixed $default = null): mixed
{
    static $cfg = null;
    if ($cfg === null) {
        $cfg = require __DIR__ . '/config.php';
    }
    return $cfg[$key] ?? $default;
}

function base_path(string $path = ''): string
{
    return dirname(__DIR__) . ($path ? '/' . ltrim($path, '/') : '');
}

function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

function logo_url(): string
{
    return asset('img/white-logo.png');
}

function app_base_path(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }

    $configured = config('app_url');
    if ($configured !== '') {
        $base = '/' . trim($configured, '/');
        return $base === '/' ? '' : $base;
    }

    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $dir = dirname($script);
    if ($dir === '/' || $dir === '.') {
        $base = '';
    } else {
        $base = rtrim($dir, '/');
    }

    return $base;
}

function request_uri(): string
{
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $uri = str_replace('\\', '/', $uri);

    $base = app_base_path();
    if ($base !== '' && str_starts_with($uri, $base)) {
        $uri = substr($uri, strlen($base)) ?: '/';
    }

    $uri = preg_replace('#^/index\.php#', '', $uri) ?: '/';
    $uri = rtrim($uri, '/') ?: '/';

    return $uri;
}

function url(string $path = ''): string
{
    $base = app_base_path();
    $path = '/' . ltrim($path, '/');
    if ($path === '/') {
        return $base ?: '/';
    }
    return ($base ?: '') . $path;
}

/** Absolute URL using current scheme + Host + app path prefix (for share cards / deep links). */
function absolute_site_href(string $appRelativeHref): string
{
    $href = '/' . ltrim($appRelativeHref, '/');
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    return $scheme . '://' . $host . $href;
}

/** Stable shareable profile path: `/p/{slug}` when set, otherwise falls back to `/profile/{id}`. */
function profile_public_href(array $userRow): string
{
    $slug = strtolower(trim((string) ($userRow['public_profile_slug'] ?? '')));
    if ($slug !== '' && preg_match('/^[a-f0-9]{10,128}$/', $slug)) {
        return url('/p/' . $slug);
    }

    return url('/profile/' . max(0, (int) ($userRow['id'] ?? 0)));
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function view(string $name, array $data = []): void
{
    extract($data);
    $viewFile = base_path("app/views/{$name}.php");
    if (!file_exists($viewFile)) {
        http_response_code(500);
        echo "View not found: {$name}";
        return;
    }
    ob_start();
    require $viewFile;
    $content = ob_get_clean();
    $layout = $data['layout'] ?? 'layouts/app';
    if ($layout === false) {
        echo $content;
        return;
    }
    require base_path("app/views/{$layout}.php");
}

function partial(string $name, array $data = []): void
{
    extract($data);
    require base_path("app/views/components/{$name}.php");
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function json_input(): array
{
    static $data = null;
    if ($data === null) {
        $raw = file_get_contents('php://input') ?: '{}';
        $data = json_decode($raw, true) ?? [];
    }
    return $data;
}

function verify_csrf(): void
{
    $token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if ($token === '') {
        $token = json_input()['_csrf'] ?? '';
    }
    if (!hash_equals(csrf_token(), $token)) {
        if (is_ajax() || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
            json_response(['error' => 'Invalid CSRF token'], 403);
        }
        flash('error', 'Session expired. Please try again.');
        redirect($_SERVER['HTTP_REFERER'] ?? '/dashboard');
    }
}

function json_response(array $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }
    $val = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $val;
}

function old(string $key, string $default = ''): string
{
    return e($_SESSION['old'][$key] ?? $default);
}

function set_old(array $data): void
{
    $_SESSION['old'] = $data;
}

function db(): PDO
{
    return Database::connect();
}

function request_method(): string
{
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if ($method === 'POST' && isset($_POST['_method'])) {
        return strtoupper($_POST['_method']);
    }
    return $method;
}

function input(string $key, mixed $default = null): mixed
{
    return $_POST[$key] ?? $_GET[$key] ?? $default;
}

function is_ajax(): bool
{
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function time_ago(string $datetime): string
{
    $time = strtotime($datetime);
    $diff = time() - $time;
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('M j', $time);
}

/** Human-readable focus time: "2h 15m", "45m", "0m". */
function format_focus_duration(int $minutes): string
{
    $m = max(0, $minutes);
    $h = intdiv($m, 60);
    $r = $m % 60;
    if ($h > 0 && $r > 0) {
        return "{$h}h {$r}m";
    }
    if ($h > 0) {
        return "{$h}h";
    }
    return "{$r}m";
}

function avatar_url(?string $path): string
{
    if ($path && file_exists(base_path('uploads/avatars/' . $path))) {
        return url('uploads/avatars/' . $path);
    }
    return asset('img/default-avatar.svg');
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    $init = '';
    foreach (array_slice($parts, 0, 2) as $p) {
        $init .= strtoupper($p[0] ?? '');
    }
    return $init ?: '?';
}

function priority_color(string $priority): string
{
    return match ($priority) {
        'high' => 'rose',
        'medium' => 'amber',
        default => 'slate',
    };
}

function label_color(string $label): string
{
    return match ($label) {
        'exam' => 'violet',
        'homework' => 'sky',
        'reading' => 'emerald',
        'project' => 'indigo',
        default => 'slate',
    };
}

