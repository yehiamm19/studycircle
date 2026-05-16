<?php

declare(strict_types=1);

namespace App\Services;

class GamificationService
{
    public static function addXp(int $userId, int $amount): array
    {
        db()->prepare('UPDATE users SET xp = xp + ?, updated_at = datetime("now") WHERE id = ?')
            ->execute([$amount, $userId]);
        self::updateStreak($userId);
        return self::checkAchievements($userId);
    }

    public static function updateStreak(int $userId): void
    {
        $stmt = db()->prepare('SELECT streak, last_active_date FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        if (!$user) {
            return;
        }

        $today = date('Y-m-d');
        $last = $user['last_active_date'];

        if ($last === $today) {
            return;
        }

        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $streak = ($last === $yesterday) ? (int) $user['streak'] + 1 : 1;

        db()->prepare('UPDATE users SET streak = ?, last_active_date = ? WHERE id = ?')
            ->execute([$streak, $today, $userId]);
    }

    public static function logActivity(int $userId, string $action, array $meta = []): void
    {
        db()->prepare('INSERT INTO activity_log (user_id, action, meta) VALUES (?,?,?)')
            ->execute([$userId, $action, json_encode($meta)]);
    }

    public static function checkAchievements(int $userId, bool $chain = true): array
    {
        $unlocked = [];
        $stats = self::userStats($userId);

        $achievements = db()->query('SELECT * FROM achievements')->fetchAll();
        $owned = db()->prepare('SELECT achievement_id FROM user_achievements WHERE user_id = ?');
        $owned->execute([$userId]);
        $ownedIds = array_map('intval', array_column($owned->fetchAll(), 'achievement_id'));

        $insert = db()->prepare('INSERT OR IGNORE INTO user_achievements (user_id, achievement_id) VALUES (?,?)');
        $addRewardXp = db()->prepare('UPDATE users SET xp = xp + ?, updated_at = datetime("now") WHERE id = ?');

        foreach ($achievements as $ach) {
            $achId = (int) $ach['id'];
            if (in_array($achId, $ownedIds, true)) {
                continue;
            }

            $value = $stats[$ach['requirement_type']] ?? 0;
            if ($value < (int) $ach['requirement_value']) {
                continue;
            }

            $insert->execute([$userId, $achId]);
            if ($insert->rowCount() === 0) {
                continue;
            }

            $ownedIds[] = $achId;
            $addRewardXp->execute([(int) $ach['xp_reward'], $userId]);
            $unlocked[] = $ach;
        }

        if ($chain && count($unlocked) > 0) {
            $more = self::checkAchievements($userId, false);
            $unlocked = array_merge($unlocked, $more);
        }

        return $unlocked;
    }

    public static function userStats(int $userId): array
    {
        $pdo = db();

        $tasks = $pdo->prepare('SELECT COUNT(*) FROM tasks t JOIN group_members gm ON t.group_id = gm.group_id WHERE gm.user_id = ? AND t.status = "completed"');
        $tasks->execute([$userId]);

        $focus = $pdo->prepare('SELECT COUNT(*) FROM focus_sessions WHERE user_id = ? AND completed = 1');
        $focus->execute([$userId]);

        $messages = $pdo->prepare('SELECT COUNT(*) FROM messages WHERE user_id = ?');
        $messages->execute([$userId]);

        $groups = $pdo->prepare('SELECT COUNT(*) FROM groups WHERE owner_id = ?');
        $groups->execute([$userId]);

        $resources = $pdo->prepare('SELECT COUNT(*) FROM resources WHERE user_id = ?');
        $resources->execute([$userId]);

        $user = $pdo->prepare('SELECT xp, streak FROM users WHERE id = ?');
        $user->execute([$userId]);
        $u = $user->fetch();

        return [
            'tasks_completed' => (int) $tasks->fetchColumn(),
            'focus_sessions' => (int) $focus->fetchColumn(),
            'messages_sent' => (int) $messages->fetchColumn(),
            'groups_created' => (int) $groups->fetchColumn(),
            'resources_uploaded' => (int) $resources->fetchColumn(),
            'streak' => (int) ($u['streak'] ?? 0),
            'xp' => (int) ($u['xp'] ?? 0),
        ];
    }
}
