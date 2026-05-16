<?php

declare(strict_types=1);

namespace App\Models;

class FocusSession
{
    public static function create(array $data): int
    {
        $groupId = !empty($data['group_id']) ? (int) $data['group_id'] : null;
        $taskId = !empty($data['task_id']) ? (int) $data['task_id'] : null;

        $stmt = db()->prepare('INSERT INTO focus_sessions (user_id, group_id, task_id, duration_minutes, completed, started_at, ended_at) VALUES (?,?,?,?,?,?,?)');
        $stmt->execute([
            $data['user_id'],
            $groupId,
            $taskId,
            (int) $data['duration_minutes'],
            (int) ($data['completed'] ?? 0),
            $data['started_at'] ?? date('Y-m-d H:i:s'),
            $data['ended_at'] ?? date('Y-m-d H:i:s'),
        ]);
        return (int) db()->lastInsertId();
    }

    public static function find(int $id): ?array
    {
        $stmt = db()->prepare('
            SELECT fs.*, g.name as group_name, t.title as task_title
            FROM focus_sessions fs
            LEFT JOIN groups g ON fs.group_id = g.id
            LEFT JOIN tasks t ON fs.task_id = t.id
            WHERE fs.id = ?
        ');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function history(int $userId, int $limit = 30): array
    {
        $stmt = db()->prepare('
            SELECT fs.*, g.name as group_name, t.title as task_title
            FROM focus_sessions fs
            LEFT JOIN groups g ON fs.group_id = g.id
            LEFT JOIN tasks t ON fs.task_id = t.id
            WHERE fs.user_id = ? AND fs.completed = 1
            ORDER BY COALESCE(fs.ended_at, fs.created_at) DESC
            LIMIT ?
        ');
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    }

    public static function stats(int $userId): array
    {
        // Compare calendar day using app timezone (timestamps stored as local Y-m-d H:i:s strings).
        $today = date('Y-m-d');

        $stmt = db()->prepare('
            SELECT 
                COUNT(*) as total_sessions,
                COALESCE(SUM(duration_minutes), 0) as total_minutes,
                COALESCE(SUM(
                    CASE WHEN substr(COALESCE(ended_at, started_at, created_at), 1, 10) = ?
                    THEN duration_minutes ELSE 0 END
                ), 0) as today_minutes
            FROM focus_sessions
            WHERE user_id = ? AND completed = 1
        ');
        $stmt->execute([$today, $userId]);
        $row = $stmt->fetch();
        return [
            'total_sessions' => (int) ($row['total_sessions'] ?? 0),
            'total_minutes' => (int) ($row['total_minutes'] ?? 0),
            'today_minutes' => (int) ($row['today_minutes'] ?? 0),
        ];
    }
}
