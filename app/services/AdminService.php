<?php

declare(strict_types=1);

namespace App\Services;

class AdminService
{
    public static function overview(): array
    {
        $db = db();
        return [
            'users' => (int) $db->query('SELECT COUNT(*) FROM users')->fetchColumn(),
            'students' => (int) $db->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn(),
            'admins' => (int) $db->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn(),
            'groups' => (int) $db->query('SELECT COUNT(*) FROM groups')->fetchColumn(),
            'tasks' => (int) $db->query('SELECT COUNT(*) FROM tasks')->fetchColumn(),
            'tasks_done' => (int) $db->query("SELECT COUNT(*) FROM tasks WHERE status = 'completed'")->fetchColumn(),
            'messages' => (int) $db->query('SELECT COUNT(*) FROM messages')->fetchColumn(),
            'resources' => (int) $db->query('SELECT COUNT(*) FROM resources')->fetchColumn(),
            'focus_sessions' => (int) $db->query('SELECT COUNT(*) FROM focus_sessions')->fetchColumn(),
            'focus_minutes' => (int) $db->query('SELECT COALESCE(SUM(duration_minutes), 0) FROM focus_sessions WHERE completed = 1')->fetchColumn(),
            'achievements_unlocked' => (int) $db->query('SELECT COUNT(*) FROM user_achievements')->fetchColumn(),
            'custom_tracks' => (int) $db->query('SELECT COUNT(*) FROM user_ambient_tracks')->fetchColumn(),
        ];
    }

    public static function recentUsers(int $limit = 8): array
    {
        $stmt = db()->prepare('SELECT id, name, email, role, xp, streak, created_at FROM users ORDER BY created_at DESC LIMIT ?');
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    public static function recentGroups(int $limit = 6): array
    {
        $stmt = db()->prepare('
            SELECT g.*, u.name AS owner_name,
                (SELECT COUNT(*) FROM group_members gm WHERE gm.group_id = g.id) AS member_count,
                (SELECT COUNT(*) FROM tasks t WHERE t.group_id = g.id) AS task_count
            FROM groups g
            JOIN users u ON u.id = g.owner_id
            ORDER BY g.created_at DESC LIMIT ?
        ');
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    public static function recentActivity(int $limit = 12): array
    {
        $stmt = db()->prepare('
            SELECT a.*, u.name AS user_name, u.email AS user_email
            FROM activity_log a
            LEFT JOIN users u ON u.id = a.user_id
            ORDER BY a.created_at DESC LIMIT ?
        ');
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    /**
     * One row per calendar day for the last N days (ascending), including zeros.
     *
     * @return list<array{day:string,count:int}>
     */
    public static function signupsByDay(int $days = 14): array
    {
        $days = max(1, $days);
        $tz = new \DateTimeZone(date_default_timezone_get());
        $today = new \DateTimeImmutable('today', $tz);
        $start = $today->modify('-' . ($days - 1) . ' days');
        $startStr = $start->format('Y-m-d');

        $stmt = db()->prepare('
            SELECT date(created_at) AS day, COUNT(*) AS signup_count
            FROM users
            WHERE date(created_at) >= ?
            GROUP BY date(created_at)
        ');
        $stmt->execute([$startStr]);

        $map = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $map[(string) $row['day']] = (int) $row['signup_count'];
        }

        $out = [];
        for ($cursor = $start; $cursor <= $today; $cursor = $cursor->modify('+1 day')) {
            $d = $cursor->format('Y-m-d');
            $out[] = [
                'day' => $d,
                'count' => $map[$d] ?? 0,
            ];
        }

        return $out;
    }

    public static function allGroups(string $search = '', int $limit = 100): array
    {
        if ($search !== '') {
            $stmt = db()->prepare('
                SELECT g.*, u.name AS owner_name,
                    (SELECT COUNT(*) FROM group_members gm WHERE gm.group_id = g.id) AS member_count,
                    (SELECT COUNT(*) FROM tasks t WHERE t.group_id = g.id) AS task_count
                FROM groups g
                JOIN users u ON u.id = g.owner_id
                WHERE g.name LIKE ? OR g.invite_code LIKE ?
                ORDER BY g.created_at DESC LIMIT ?
            ');
            $like = '%' . $search . '%';
            $stmt->execute([$like, $like, $limit]);
            return $stmt->fetchAll();
        }
        $stmt = db()->prepare('
            SELECT g.*, u.name AS owner_name,
                (SELECT COUNT(*) FROM group_members gm WHERE gm.group_id = g.id) AS member_count,
                (SELECT COUNT(*) FROM tasks t WHERE t.group_id = g.id) AS task_count
            FROM groups g
            JOIN users u ON u.id = g.owner_id
            ORDER BY g.created_at DESC LIMIT ?
        ');
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
}
