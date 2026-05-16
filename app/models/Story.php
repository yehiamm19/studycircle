<?php

declare(strict_types=1);

namespace App\Models;

use App\Utils\Uploader;
use PDO;

class Story
{
    private static function dir(): string
    {
        return base_path('uploads/stories');
    }

    public static function ensureTable(): void
    {
        db()->exec('CREATE TABLE IF NOT EXISTS stories (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            group_id INTEGER,
            caption TEXT NOT NULL DEFAULT \'\',
            mood TEXT NOT NULL DEFAULT \'general\',
            context_json TEXT NOT NULL DEFAULT \'{}\',
            filename TEXT NOT NULL,
            mime_type TEXT NOT NULL,
            file_size INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL DEFAULT (datetime(\'now\')),
            expires_at TEXT NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE
        )');
        db()->exec('CREATE INDEX IF NOT EXISTS idx_stories_expires ON stories(expires_at)');
        db()->exec('CREATE INDEX IF NOT EXISTS idx_stories_group_expires ON stories(group_id, expires_at)');

        $cols = [];
        try {
            $rows = db()->query('PRAGMA table_info(stories)')->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $r) {
                if (!empty($r['name'])) {
                    $cols[] = $r['name'];
                }
            }
        } catch (\Throwable) {
            $cols = [];
        }
        if (!in_array('view_count', $cols, true)) {
            db()->exec('ALTER TABLE stories ADD COLUMN view_count INTEGER NOT NULL DEFAULT 0');
        }
    }

    public static function purgeExpired(): void
    {
        self::ensureTable();
        $now = date('Y-m-d H:i:s');
        $stmt = db()->prepare('SELECT id, filename FROM stories WHERE expires_at <= ?');
        $stmt->execute([$now]);
        foreach ($stmt->fetchAll() as $row) {
            Uploader::delete(self::dir(), $row['filename']);
        }
        db()->prepare('DELETE FROM stories WHERE expires_at <= ?')->execute([$now]);
    }

    public static function create(array $data): int
    {
        self::ensureTable();
        $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
        $groupBind = array_key_exists('group_id', $data) ? $data['group_id'] : null;
        if ($groupBind === '' || $groupBind === false || $groupBind === null) {
            $groupBind = null;
        } else {
            $g = (int) $groupBind;
            $groupBind = $g > 0 ? $g : null;
        }
        $stmt = db()->prepare('INSERT INTO stories (user_id, group_id, caption, mood, context_json, filename, mime_type, file_size, expires_at) VALUES (?,?,?,?,?,?,?,?,?)');
        $stmt->execute([
            $data['user_id'],
            $groupBind,
            $data['caption'] ?? '',
            $data['mood'] ?? 'general',
            $data['context_json'] ?? '{}',
            $data['filename'],
            $data['mime_type'],
            (int) ($data['file_size'] ?? 0),
            $expires,
        ]);
        return (int) db()->lastInsertId();
    }

    public static function find(int $id): ?array
    {
        self::ensureTable();
        $stmt = db()->prepare('
            SELECT s.*, u.name AS user_name, u.avatar AS user_avatar
            FROM stories s
            JOIN users u ON u.id = s.user_id
            WHERE s.id = ?
        ');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function isExpired(array $story): bool
    {
        return ($story['expires_at'] ?? '') <= date('Y-m-d H:i:s');
    }

    public static function canView(?array $story, int $userId): bool
    {
        if (!$story || self::isExpired($story)) {
            return false;
        }
        if ($story['group_id'] === null || $story['group_id'] === '') {
            return true;
        }
        $gid = (int) $story['group_id'];
        if ($gid < 1) {
            return true;
        }

        return Group::isMember($gid, $userId);
    }

    /** @return list<array{user_id:int,user_name:string,user_avatar:?string,stories:list}> */
    public static function railBucketsPublic(): array
    {
        self::purgeExpired();
        $now = date('Y-m-d H:i:s');
        $stmt = db()->prepare('
            SELECT s.*, u.name AS user_name, u.avatar AS user_avatar
            FROM stories s
            JOIN users u ON u.id = s.user_id
            WHERE s.group_id IS NULL AND s.expires_at > ?
            ORDER BY s.created_at DESC
        ');
        $stmt->execute([$now]);
        return self::bucketRows($stmt->fetchAll());
    }

    /** @return list<array{user_id:int,user_name:string,user_avatar:?string,stories:list}> */
    public static function railBucketsForGroup(int $groupId): array
    {
        self::purgeExpired();
        $now = date('Y-m-d H:i:s');
        $stmt = db()->prepare('
            SELECT s.*, u.name AS user_name, u.avatar AS user_avatar
            FROM stories s
            JOIN users u ON u.id = s.user_id
            WHERE s.group_id = ? AND s.expires_at > ?
            ORDER BY s.created_at DESC
        ');
        $stmt->execute([$groupId, $now]);
        return self::bucketRows($stmt->fetchAll());
    }

    /**
     * @param list<array<string,mixed>> $rows
     * @return list<array{user_id:int,user_name:string,user_avatar:?string,stories:list}>
     */
    private static function bucketRows(array $rows): array
    {
        $buckets = [];
        $order = [];
        foreach ($rows as $r) {
            $uid = (int) $r['user_id'];
            if (!isset($buckets[$uid])) {
                $order[] = $uid;
                $buckets[$uid] = [
                    'user_id' => $uid,
                    'user_name' => $r['user_name'],
                    'user_avatar' => $r['user_avatar'],
                    'stories' => [],
                ];
            }
            $buckets[$uid]['stories'][] = $r;
        }
        $out = [];
        foreach ($order as $uid) {
            $b = $buckets[$uid];
            $b['stories'] = array_reverse($b['stories']);

            $out[] = $b;
        }
        return $out;
    }

    public static function deleteById(int $id, int $userId): bool
    {
        $story = db()->prepare('SELECT * FROM stories WHERE id = ? AND user_id = ?');
        $story->execute([$id, $userId]);
        $row = $story->fetch();
        if (!$row) {
            return false;
        }
        Uploader::delete(self::dir(), $row['filename']);
        db()->prepare('DELETE FROM stories WHERE id = ?')->execute([$id]);

        return true;
    }

    /** Increment views (full-screen viewer). Returns new total. */
    public static function incrementViewCount(int $storyId): int
    {
        self::ensureTable();
        $pdo = db();
        $pdo->prepare('UPDATE stories SET view_count = COALESCE(view_count, 0) + 1 WHERE id = ?')->execute([$storyId]);
        $sel = $pdo->prepare('SELECT COALESCE(view_count, 0) FROM stories WHERE id = ?');
        $sel->execute([$storyId]);

        return (int) $sel->fetchColumn();
    }

    /** Prepare buckets for Alpine (flat media URLs, mood labels). */
    public static function serializeBuckets(array $buckets): array
    {
        foreach ($buckets as &$bucket) {
            $bucket['avatar_url'] = avatar_url($bucket['user_avatar'] ?? null);
            foreach ($bucket['stories'] as &$s) {
                $s['media_url'] = url('/stories/' . $s['id'] . '/media');
                $s['mood_key'] = $s['mood'] ?? 'general';
                $meta = json_decode((string) ($s['context_json'] ?? '{}'), true) ?: [];
                $s['context_note'] = (string) ($meta['note'] ?? '');
                $s['view_count'] = (int) ($s['view_count'] ?? 0);
                unset($s['context_json'], $s['filename']);
            }
        }
        unset($bucket, $s);

        return $buckets;
    }
}
