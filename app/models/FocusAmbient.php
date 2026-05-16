<?php

declare(strict_types=1);

namespace App\Models;

class FocusAmbient
{
    public static function get(int $userId): array
    {
        self::ensureTable();
        $stmt = db()->prepare('SELECT * FROM user_focus_ambient WHERE user_id = ?');
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        return $row ?: [
            'user_id' => $userId,
            'youtube_url' => '',
            'volume' => 0.5,
            'active_sound' => '',
        ];
    }

    public static function save(int $userId, array $data): void
    {
        self::ensureTable();
        $existing = self::get($userId);
        $youtube = array_key_exists('youtube_url', $data) ? trim($data['youtube_url']) : ($existing['youtube_url'] ?? '');
        $volume = isset($data['volume']) ? max(0, min(1, (float) $data['volume'])) : (float) ($existing['volume'] ?? 0.5);
        $sound = $data['active_sound'] ?? ($existing['active_sound'] ?? '');

        if (isset($existing['user_id'])) {
            db()->prepare('UPDATE user_focus_ambient SET youtube_url = ?, volume = ?, active_sound = ?, updated_at = datetime("now") WHERE user_id = ?')
                ->execute([$youtube, $volume, $sound, $userId]);
        } else {
            db()->prepare('INSERT INTO user_focus_ambient (user_id, youtube_url, volume, active_sound, updated_at) VALUES (?,?,?,?, datetime("now"))')
                ->execute([$userId, $youtube, $volume, $sound]);
        }
    }

    public static function ensureTable(): void
    {
        db()->exec("CREATE TABLE IF NOT EXISTS user_focus_ambient (
            user_id INTEGER PRIMARY KEY,
            youtube_url TEXT DEFAULT '',
            volume REAL NOT NULL DEFAULT 0.5,
            active_sound TEXT DEFAULT '',
            updated_at TEXT NOT NULL DEFAULT (datetime('now')),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )");
    }
}
