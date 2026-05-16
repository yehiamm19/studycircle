<?php

declare(strict_types=1);

/**
 * Default configuration (production-safe).
 * Override locally via config.local.php (see config.local.example.php) — never commit secrets there if repo is public.
 */
$config = [
    'app_name' => 'StudyCircle',
    /** Set when DocumentRoot is not auto-detected (e.g. some proxies); normally leave empty */
    'app_url' => '',
    /** When true: show PHP errors in the browser (keep false on hosting). */
    'debug' => false,
    'timezone' => 'UTC',
    'session_lifetime' => 86400 * 7,
    'upload_max_size' => 5 * 1024 * 1024,
    'upload_allowed' => ['pdf', 'jpg', 'jpeg', 'png', 'webp'],
    'avatar_allowed' => ['jpg', 'jpeg', 'png', 'webp'],
    'pomodoro_default' => 25,
    'xp_per_task' => 15,
    'xp_per_focus' => 10,
    'xp_per_message' => 2,
    /** Ephemeral campus stories — images only */
    'story_upload_allowed' => ['jpg', 'jpeg', 'png', 'webp'],
    'story_upload_max_size' => 5 * 1024 * 1024,
    'story_caption_max' => 220,
    /**
     * One-click demo login on login/register (seeded alex@ / jordan@ + password123).
     * ON by default for previews & resale demos; set false in config.local.php on locked-down production.
     */
    'demo_quick_login' => true,
    /**
     * Set true when HTTPS is terminated by a reverse proxy (nginx, Cloudflare, etc.)
     * so session cookies get the Secure flag when the client really uses HTTPS.
     */
    'trust_https_proxy' => false,
];

$localPath = __DIR__ . '/config.local.php';
if (is_file($localPath)) {
    /** @var mixed $local */
    $local = require $localPath;
    if (is_array($local)) {
        /** @var array<string,mixed> $local */
        $config = array_merge($config, $local);
    }
}

return $config;
