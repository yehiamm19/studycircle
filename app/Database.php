<?php

declare(strict_types=1);

namespace App;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;

    public static function path(): string
    {
        return dirname(__DIR__) . '/database/studycircle.sqlite';
    }

    public static function connect(): PDO
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $path = self::path();
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        self::$instance = new PDO('sqlite:' . $path, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        self::$instance->exec('PRAGMA foreign_keys = ON');
        self::$instance->exec('PRAGMA journal_mode = WAL');

        return self::$instance;
    }

    public static function initialize(): void
    {
        $pdo = self::connect();
        $schema = dirname(__DIR__) . '/database/schema.sql';
        if (file_exists($schema)) {
            $pdo->exec(file_get_contents($schema));
        }
    }

    public static function reset(): void
    {
        self::$instance = null;
    }
}

