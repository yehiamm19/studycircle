<?php

declare(strict_types=1);

namespace App\Services;

class NotificationService
{
    public static function create(int $userId, string $type, string $title, string $body = '', string $link = ''): void
    {
        db()->prepare('INSERT INTO notifications (user_id, type, title, body, link) VALUES (?,?,?,?,?)')
            ->execute([$userId, $type, $title, $body, $link]);
    }

    public static function notifyGroup(int $groupId, int $excludeUserId, string $type, string $title, string $body, string $link): void
    {
        $stmt = db()->prepare('SELECT user_id FROM group_members WHERE group_id = ? AND user_id != ?');
        $stmt->execute([$groupId, $excludeUserId]);
        while ($row = $stmt->fetch()) {
            self::create((int) $row['user_id'], $type, $title, $body, $link);
        }
    }
}

