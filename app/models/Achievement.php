<?php

declare(strict_types=1);

namespace App\Models;

class Achievement
{
    public static function allWithProgress(int $userId): array
    {
        $stmt = db()->prepare('
            SELECT a.*, ua.unlocked_at,
                CASE WHEN ua.id IS NOT NULL THEN 1 ELSE 0 END as unlocked
            FROM achievements a
            LEFT JOIN user_achievements ua ON a.id = ua.achievement_id AND ua.user_id = ?
            ORDER BY unlocked DESC, a.xp_reward ASC
        ');
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function forUser(int $userId): array
    {
        $stmt = db()->prepare('
            SELECT a.*, ua.unlocked_at FROM user_achievements ua
            JOIN achievements a ON ua.achievement_id = a.id
            WHERE ua.user_id = ? ORDER BY ua.unlocked_at DESC
        ');
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
}

