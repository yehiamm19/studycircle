<?php

declare(strict_types=1);

$config = require __DIR__ . '/config.php';
date_default_timezone_set($config['timezone']);

if (empty($config['debug'])) {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    ini_set('log_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));

    $paths = [__DIR__ . '/' . $relative . '.php'];

    $nsDirMap = [
        'Models' => 'models',
        'Controllers' => 'controllers',
        'Services' => 'services',
        'Utils' => 'utils',
    ];
    $slashPos = strpos($relative, '/');
    if ($slashPos !== false) {
        $first = substr($relative, 0, $slashPos);
        $rest = substr($relative, $slashPos + 1);
        if (isset($nsDirMap[$first])) {
            $paths[] = __DIR__ . '/' . $nsDirMap[$first] . '/' . $rest . '.php';
        }
        foreach ($nsDirMap as $dir) {
            $paths[] = __DIR__ . '/' . $dir . '/' . $rest . '.php';
        }
    }

    foreach ($paths as $file) {
        if (file_exists($file)) {
            require $file;
            return;
        }
    }
});

require __DIR__ . '/helpers.php';

use App\Auth;
use App\Database;

Auth::start();

if (!file_exists(Database::path())) {
    Database::initialize();
    $seed = dirname(__DIR__) . '/database/seed.php';
    if (file_exists($seed)) {
        require $seed;
    }
} else {
    \App\Models\FocusAmbient::ensureTable();
    \App\Models\AmbientTrack::ensureTable();
    \App\Models\Story::ensureTable();
}

\App\Models\User::ensurePublicProfileSlugColumn();
\App\Models\User::backfillMissingPublicProfileSlugs();

\App\Models\AgileMigration::ensure();

$__sc_agile_demo = dirname(__DIR__) . '/database/seed_agile_demo.php';
if (file_exists($__sc_agile_demo)) {
    require_once $__sc_agile_demo;
    studycircle_seed_demo_agile_workspace();
}

Auth::syncSessionUser();