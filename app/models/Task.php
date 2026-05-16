<?php

declare(strict_types=1);

namespace App\Models;

class Task
{
    public const MOSCOW = ['must', 'should', 'could', 'wont'];

    public static function sanitizeMoscow(string $m): string
    {
        $m = strtolower(trim($m));

        return in_array($m, self::MOSCOW, true) ? $m : 'could';
    }

    public static function forGroup(int $groupId, array $filters = []): array
    {
        $where = ['t.group_id = ?'];
        $params = [$groupId];
        if (!empty($filters['sprint_id'])) {
            if ($filters['sprint_id'] === 'none' || $filters['sprint_id'] === 'backlog') {
                $where[] = 't.sprint_id IS NULL';
            } elseif (is_numeric($filters['sprint_id'])) {
                $where[] = 't.sprint_id = ?';
                $params[] = (int) $filters['sprint_id'];
            }
        }
        if (!empty($filters['moscow_priority']) && $filters['moscow_priority'] !== '') {
            $mo = self::sanitizeMoscow((string) $filters['moscow_priority']);
            $where[] = 't.moscow_priority = ?';
            $params[] = $mo;
        }
        $sql = '
            SELECT t.*, u.name as assignee_name, u.avatar as assignee_avatar,
                s.name as sprint_name, s.status as sprint_status,
                (SELECT GROUP_CONCAT(tr.requirement_id) FROM task_requirements tr WHERE tr.task_id = t.id) AS requirement_ids_csv,
                (SELECT GROUP_CONCAT(r.requirement_ref) FROM task_requirements tr
                    JOIN requirements r ON r.id = tr.requirement_id WHERE tr.task_id = t.id) AS requirement_refs
            FROM tasks t
            LEFT JOIN users u ON t.assignee_id = u.id
            LEFT JOIN sprints s ON s.id = t.sprint_id
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY t.position ASC, t.created_at ASC
        ';
        $stmt = db()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * MoSCoW counts per Kanban column for the current filtered task set.
     *
     * @return array<string, array<string, int>>
     */
    public static function moscowCountsByStatus(int $groupId, array $filters = []): array
    {
        $where = ['group_id = ?'];
        $params = [$groupId];
        if (!empty($filters['sprint_id'])) {
            if ($filters['sprint_id'] === 'none' || $filters['sprint_id'] === 'backlog') {
                $where[] = 'sprint_id IS NULL';
            } elseif (is_numeric($filters['sprint_id'])) {
                $where[] = 'sprint_id = ?';
                $params[] = (int) $filters['sprint_id'];
            }
        }
        if (!empty($filters['moscow_priority']) && $filters['moscow_priority'] !== '') {
            $where[] = 'moscow_priority = ?';
            $params[] = self::sanitizeMoscow((string) $filters['moscow_priority']);
        }
        $sql = 'SELECT status, moscow_priority as m, COUNT(*) as c FROM tasks WHERE ' . implode(' AND ', $where) . ' GROUP BY status, moscow_priority';
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $out = [
            'todo' => ['must' => 0, 'should' => 0, 'could' => 0, 'wont' => 0],
            'in_progress' => ['must' => 0, 'should' => 0, 'could' => 0, 'wont' => 0],
            'completed' => ['must' => 0, 'should' => 0, 'could' => 0, 'wont' => 0],
        ];
        foreach ($stmt->fetchAll() as $row) {
            $st = $row['status'] ?? '';
            $m = $row['m'] ?? 'could';
            if (isset($out[$st]) && isset($out[$st][$m])) {
                $out[$st][$m] = (int) $row['c'];
            }
        }

        return $out;
    }

    public static function completedPerDay(int $groupId, int $days = 28): array
    {
        $stmt = db()->prepare("
            SELECT date(completed_at) as d, COUNT(*) as n
            FROM tasks
            WHERE group_id = ? AND status = 'completed' AND completed_at IS NOT NULL
            AND date(completed_at) >= date('now', '-' || CAST(? AS TEXT) || ' days')
            GROUP BY date(completed_at)
            ORDER BY d ASC
        ");
        $stmt->execute([$groupId, (string) $days]);

        return $stmt->fetchAll() ?: [];
    }

    public static function find(int $id): ?array
    {
        $stmt = db()->prepare('
            SELECT t.*, u.name as assignee_name
            FROM tasks t LEFT JOIN users u ON t.assignee_id = u.id
            WHERE t.id = ?
        ');
        $stmt->execute([$id]);
        $row = $stmt->fetch() ?: null;
        if ($row) {
            $row['linked_requirement_ids'] = Requirement::idsForTask($id);
        }

        return $row;
    }

    public static function create(array $data): int
    {
        $maxPos = db()->prepare('SELECT COALESCE(MAX(position), -1) + 1 FROM tasks WHERE group_id = ? AND status = ?');
        $maxPos->execute([$data['group_id'], $data['status'] ?? 'todo']);
        $position = (int) $maxPos->fetchColumn();

        $stmt = db()->prepare('
            INSERT INTO tasks (group_id, title, description, status, priority, label, due_date, assignee_id, position, created_by, sprint_id, moscow_priority, story_points)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)
        ');
        $stmt->execute([
            $data['group_id'],
            $data['title'],
            $data['description'] ?? '',
            $data['status'] ?? 'todo',
            $data['priority'] ?? 'medium',
            $data['label'] ?? 'homework',
            $data['due_date'] ?? null,
            $data['assignee_id'] ?? null,
            $position,
            $data['created_by'],
            !empty($data['sprint_id']) ? (int) $data['sprint_id'] : null,
            self::sanitizeMoscow((string) ($data['moscow_priority'] ?? 'could')),
            (float) ($data['story_points'] ?? 1),
        ]);

        return (int) db()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $fields = [];
        $values = [];
        foreach (
            ['title', 'description', 'status', 'priority', 'label', 'due_date', 'assignee_id', 'position',
                'sprint_id', 'moscow_priority', 'story_points', ]
            as $f
        ) {
            if (!array_key_exists($f, $data)) {
                continue;
            }
            $fields[] = "$f = ?";
            if ($f === 'moscow_priority') {
                $values[] = self::sanitizeMoscow((string) $data[$f]);
            } elseif ($f === 'sprint_id' && ($data[$f] === '' || $data[$f] === null)) {
                $values[] = null;
            } elseif ($f === 'story_points') {
                $values[] = max(0.0, (float) $data[$f]);
            } else {
                $values[] = $data[$f];
            }
        }
        if (isset($data['status']) && $data['status'] === 'completed') {
            $fields[] = 'completed_at = datetime("now")';
        } elseif (isset($data['status']) && $data['status'] !== 'completed') {
            $fields[] = 'completed_at = NULL';
        }
        if (empty($fields)) {
            return;
        }
        $fields[] = 'updated_at = datetime("now")';
        $values[] = $id;
        db()->prepare('UPDATE tasks SET ' . implode(', ', $fields) . ' WHERE id = ?')->execute($values);
    }

    public static function delete(int $id): void
    {
        db()->prepare('DELETE FROM tasks WHERE id = ?')->execute([$id]);
    }

    public static function reorder(int $groupId, string $status, array $taskIds): void
    {
        $stmt = db()->prepare('UPDATE tasks SET status = ?, position = ?, updated_at = datetime("now") WHERE id = ? AND group_id = ?');
        foreach ($taskIds as $pos => $taskId) {
            $stmt->execute([$status, $pos, $taskId, $groupId]);
        }
    }

    public static function comments(int $taskId): array
    {
        $stmt = db()->prepare('SELECT c.*, u.name, u.avatar FROM task_comments c JOIN users u ON c.user_id = u.id WHERE c.task_id = ? ORDER BY c.created_at ASC');
        $stmt->execute([$taskId]);

        return $stmt->fetchAll();
    }

    public static function addComment(int $taskId, int $userId, string $body): int
    {
        db()->prepare('INSERT INTO task_comments (task_id, user_id, body) VALUES (?,?,?)')->execute([$taskId, $userId, $body]);

        return (int) db()->lastInsertId();
    }

    public static function statsForUser(int $userId): array
    {
        $stmt = db()->prepare('
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN t.status = "completed" THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN t.status = "in_progress" THEN 1 ELSE 0 END) as in_progress
            FROM tasks t JOIN group_members gm ON t.group_id = gm.group_id
            WHERE gm.user_id = ?
        ');
        $stmt->execute([$userId]);

        return $stmt->fetch() ?: ['total' => 0, 'completed' => 0, 'in_progress' => 0];
    }

    public static function moscowCountsForGroup(int $groupId, ?int $sprintId = null): array
    {
        $sql = 'SELECT moscow_priority as m, COUNT(*) as c FROM tasks WHERE group_id = ?';
        $args = [$groupId];
        if ($sprintId) {
            $sql .= ' AND sprint_id = ?';
            $args[] = $sprintId;
        }
        $sql .= ' GROUP BY moscow_priority';
        $stmt = db()->prepare($sql);
        $stmt->execute($args);
        $out = ['must' => 0, 'should' => 0, 'could' => 0, 'wont' => 0];
        foreach ($stmt->fetchAll() as $row) {
            $k = $row['m'] ?? 'could';
            if (isset($out[$k])) {
                $out[$k] = (int) $row['c'];
            }
        }

        return $out;
    }
}
