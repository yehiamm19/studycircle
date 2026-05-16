<?php

declare(strict_types=1);

namespace App\Models;

class Message
{
    public static function forGroup(int $groupId, int $limit = 50, ?int $afterId = null): array
    {
        if ($afterId) {
            $stmt = db()->prepare('
                SELECT m.*, u.name, u.avatar FROM messages m JOIN users u ON m.user_id = u.id
                WHERE m.group_id = ? AND m.id > ? ORDER BY m.created_at ASC LIMIT ?
            ');
            $stmt->execute([$groupId, $afterId, $limit]);
        } else {
            $stmt = db()->prepare('
                SELECT m.*, u.name, u.avatar FROM messages m JOIN users u ON m.user_id = u.id
                WHERE m.group_id = ? ORDER BY m.created_at DESC LIMIT ?
            ');
            $stmt->execute([$groupId, $limit]);
            return array_reverse($stmt->fetchAll());
        }
        return $stmt->fetchAll();
    }

    public static function create(int $groupId, int $userId, string $body): array
    {
        db()->prepare('INSERT INTO messages (group_id, user_id, body) VALUES (?,?,?)')->execute([$groupId, $userId, $body]);
        $id = (int) db()->lastInsertId();
        $stmt = db()->prepare('SELECT m.*, u.name, u.avatar FROM messages m JOIN users u ON m.user_id = u.id WHERE m.id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
}

