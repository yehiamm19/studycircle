<?php

declare(strict_types=1);

namespace App\Models;

class AmbientTrack
{
    public const ALLOWED_ICONS = [
        'youtube', 'music', 'music-2', 'headphones', 'radio', 'mic',
        'cloud-rain', 'waves', 'wind', 'trees', 'bird', 'sparkles',
        'moon', 'sun', 'coffee', 'book-open', 'flame', 'heart',
    ];

    public static function ensureTable(): void
    {
        db()->exec('CREATE TABLE IF NOT EXISTS user_ambient_tracks (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            name TEXT NOT NULL,
            icon TEXT NOT NULL DEFAULT "youtube",
            youtube_url TEXT NOT NULL,
            sort_order INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL DEFAULT (datetime(\'now\')),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )');
        db()->exec('CREATE INDEX IF NOT EXISTS idx_ambient_tracks_user ON user_ambient_tracks(user_id)');
    }

    public static function forUser(int $userId): array
    {
        self::ensureTable();
        $stmt = db()->prepare('SELECT * FROM user_ambient_tracks WHERE user_id = ? ORDER BY sort_order ASC, id ASC');
        $stmt->execute([$userId]);
        return $stmt->fetchAll() ?: [];
    }

    public static function create(int $userId, string $name, string $icon, string $youtubeUrl): int
    {
        self::ensureTable();
        $icon = self::sanitizeIcon($icon);
        $name = trim($name);
        if ($name === '') {
            throw new \InvalidArgumentException('Name is required');
        }
        $max = db()->prepare('SELECT COALESCE(MAX(sort_order), 0) FROM user_ambient_tracks WHERE user_id = ?');
        $max->execute([$userId]);
        $order = (int) $max->fetchColumn() + 1;

        db()->prepare('INSERT INTO user_ambient_tracks (user_id, name, icon, youtube_url, sort_order) VALUES (?,?,?,?,?)')
            ->execute([$userId, $name, $icon, trim($youtubeUrl), $order]);

        return (int) db()->lastInsertId();
    }

    public static function delete(int $userId, int $id): bool
    {
        self::ensureTable();
        $stmt = db()->prepare('DELETE FROM user_ambient_tracks WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $userId]);
        return $stmt->rowCount() > 0;
    }

    public static function find(int $userId, int $id): ?array
    {
        self::ensureTable();
        $stmt = db()->prepare('SELECT * FROM user_ambient_tracks WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function sanitizeIcon(string $icon): string
    {
        $icon = preg_replace('/[^a-z0-9-]/', '', strtolower(trim($icon))) ?: 'youtube';
        return in_array($icon, self::ALLOWED_ICONS, true) ? $icon : 'youtube';
    }
}
