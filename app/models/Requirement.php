<?php

declare(strict_types=1);

namespace App\Models;

class Requirement
{
    public const STATUSES = ['draft', 'active', 'done', 'archived'];

    public static function forGroup(int $groupId): array
    {
        $stmt = db()->prepare('
            SELECT r.*, uc.code AS use_case_code, uc.title AS use_case_title
            FROM requirements r
            LEFT JOIN use_cases uc ON uc.id = r.use_case_id
            WHERE r.group_id = ?
            ORDER BY r.requirement_ref ASC
        ');
        $stmt->execute([$groupId]);

        return $stmt->fetchAll() ?: [];
    }

    public static function find(int $id): ?array
    {
        $stmt = db()->prepare('SELECT * FROM requirements WHERE id = ?');
        $stmt->execute([$id]);
        $r = $stmt->fetch();

        return $r ?: null;
    }

    public static function create(array $data): int
    {
        db()->prepare('
            INSERT INTO requirements (group_id, requirement_ref, title, description, use_case_id, status, updated_at)
            VALUES (?,?,?,?,?,?,datetime("now"))
        ')->execute([
            (int) $data['group_id'],
            $data['requirement_ref'],
            $data['title'],
            $data['description'] ?? '',
            $data['use_case_id'] ?: null,
            $data['status'] ?? 'active',
        ]);

        return (int) db()->lastInsertId();
    }

    public static function updateRow(int $id, array $fields): void
    {
        $allowed = ['title', 'description', 'use_case_id', 'status'];
        $sets = [];
        $vals = [];
        foreach ($allowed as $f) {
            if (array_key_exists($f, $fields)) {
                $sets[] = "$f = ?";
                $vals[] = $fields[$f];
            }
        }
        if (empty($sets)) {
            return;
        }
        $sets[] = 'updated_at = datetime("now")';
        $vals[] = $id;
        db()->prepare('UPDATE requirements SET ' . implode(', ', $sets) . ' WHERE id = ?')->execute($vals);
    }

    /** % done from linked tasks (completed / total with link) */
    public static function completionPercent(int $requirementId): float
    {
        $stmt = db()->prepare('
            SELECT
                SUM(CASE WHEN t.status = "completed" THEN 1 ELSE 0 END) AS done_cnt,
                COUNT(*) AS total
            FROM task_requirements tr
            JOIN tasks t ON t.id = tr.task_id
            WHERE tr.requirement_id = ?
        ');
        $stmt->execute([$requirementId]);
        $row = $stmt->fetch();
        $total = (int) ($row['total'] ?? 0);
        if ($total === 0) {
            return 0;
        }
        $done = (int) ($row['done_cnt'] ?? 0);

        return round(100 * $done / $total, 1);
    }

    public static function traceabilityMatrix(int $groupId): array
    {
        $reqs = self::forGroup($groupId);
        $stmt = db()->prepare('
            SELECT tr.requirement_id, t.id as task_id, t.title as task_title, t.status as task_status
            FROM task_requirements tr
            JOIN tasks t ON t.id = tr.task_id
            JOIN requirements r ON r.id = tr.requirement_id
            WHERE r.group_id = ?
            ORDER BY r.requirement_ref, t.id
        ');
        $stmt->execute([$groupId]);

        return ['requirements' => $reqs, 'links' => $stmt->fetchAll() ?: []];
    }

    public static function idsForTask(int $taskId): array
    {
        $stmt = db()->prepare('SELECT requirement_id FROM task_requirements WHERE task_id = ?');
        $stmt->execute([$taskId]);

        return array_map('intval', array_column($stmt->fetchAll(), 'requirement_id'));
    }

    public static function syncTaskLinks(int $taskId, array $requirementIds): void
    {
        db()->prepare('DELETE FROM task_requirements WHERE task_id = ?')->execute([$taskId]);
        $ins = db()->prepare('INSERT INTO task_requirements (task_id, requirement_id) VALUES (?,?)');
        foreach ($requirementIds as $rid) {
            $rid = (int) $rid;
            if ($rid > 0) {
                $ins->execute([$taskId, $rid]);
            }
        }
    }
}
