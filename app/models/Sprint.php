<?php

declare(strict_types=1);

namespace App\Models;

class Sprint
{
    public const STATUSES = ['planned', 'active', 'completed'];

    public static function forGroup(int $groupId): array
    {
        $stmt = db()->prepare('SELECT * FROM sprints WHERE group_id = ? ORDER BY datetime(start_date) DESC, id DESC');
        $stmt->execute([$groupId]);

        return $stmt->fetchAll() ?: [];
    }

    public static function find(int $id): ?array
    {
        $stmt = db()->prepare('SELECT * FROM sprints WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function create(array $data): int
    {
        $stmt = db()->prepare('
            INSERT INTO sprints (group_id, name, goal, duration_days, start_date, end_date, status)
            VALUES (?,?,?,?,?,?,?)
        ');
        $stmt->execute([
            (int) $data['group_id'],
            $data['name'],
            $data['goal'] ?? '',
            (int) ($data['duration_days'] ?? 14),
            $data['start_date'],
            $data['end_date'],
            $data['status'] ?? 'planned',
        ]);

        return (int) db()->lastInsertId();
    }

    public static function updateRow(int $id, array $fields): void
    {
        $allowed = ['name', 'goal', 'duration_days', 'start_date', 'end_date', 'status'];
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
        db()->prepare('UPDATE sprints SET ' . implode(', ', $sets) . ' WHERE id = ?')->execute($vals);
    }

    public static function activate(int $sprintId, int $groupId): void
    {
        db()->prepare('UPDATE sprints SET status = "completed", updated_at = datetime("now") WHERE group_id = ? AND status = "active"')
            ->execute([$groupId]);
        db()->prepare('UPDATE sprints SET status = "active", updated_at = datetime("now") WHERE id = ? AND group_id = ?')->execute([$sprintId, $groupId]);
    }

    /** Tasks with points + completion for burndown */
    public static function taskStatsForSprint(int $sprintId): array
    {
        $stmt = db()->prepare('
            SELECT status, COUNT(*) as c, SUM(COALESCE(story_points, 1)) as pts
            FROM tasks WHERE sprint_id = ? GROUP BY status
        ');
        $stmt->execute([$sprintId]);
        $rows = $stmt->fetchAll();
        $by = ['todo' => ['n' => 0, 'pts' => 0.0], 'in_progress' => ['n' => 0, 'pts' => 0.0], 'completed' => ['n' => 0, 'pts' => 0.0]];
        foreach ($rows as $r) {
            $st = $r['status'];
            if (!isset($by[$st])) {
                continue;
            }
            $by[$st]['n'] = (int) $r['c'];
            $by[$st]['pts'] = (float) $r['pts'];
        }

        return $by;
    }

    public static function progressPercent(int $sprintId): float
    {
        $st = self::taskStatsForSprint($sprintId);
        $totalPts = $st['todo']['pts'] + $st['in_progress']['pts'] + $st['completed']['pts'];
        if ($totalPts <= 0) {
            $totalN = $st['todo']['n'] + $st['in_progress']['n'] + $st['completed']['n'];

            return $totalN > 0 ? round(100 * $st['completed']['n'] / $totalN, 1) : 0;
        }

        return round(100 * $st['completed']['pts'] / $totalPts, 1);
    }

    /** Completed story points per calendar day (SQLite date) */
    public static function burndownSeries(int $sprintId, string $startDate, string $endDate): array
    {
        $stmt = db()->prepare('
            SELECT date(completed_at) as d, SUM(COALESCE(story_points, 1)) as pts
            FROM tasks
            WHERE sprint_id = ? AND status = "completed" AND completed_at IS NOT NULL
            GROUP BY date(completed_at)
            ORDER BY d ASC
        ');
        $stmt->execute([$sprintId]);
        $doneByDay = [];
        foreach ($stmt->fetchAll() as $row) {
            $doneByDay[$row['d']] = (float) $row['pts'];
        }

        $stmt2 = db()->prepare('
            SELECT SUM(COALESCE(story_points, 1)) as total
            FROM tasks WHERE sprint_id = ?
        ');
        $stmt2->execute([$sprintId]);
        $totalScope = (float) ($stmt2->fetchColumn() ?: 0);
        if ($totalScope <= 0) {
            $stmt3 = db()->prepare('SELECT COUNT(*) FROM tasks WHERE sprint_id = ?');
            $stmt3->execute([$sprintId]);
            $totalScope = (float) max(1, (int) $stmt3->fetchColumn());
        }

        $days = [];
        $ts = strtotime($startDate);
        $end = strtotime($endDate);
        if ($ts === false || $end === false) {
            return ['labels' => [], 'ideal' => [], 'actual' => [], 'scope' => $totalScope];
        }
        while ($ts <= $end) {
            $days[] = date('Y-m-d', $ts);
            $ts = strtotime('+1 day', $ts);
        }
        $n = max(1, count($days));
        $ideal = [];
        foreach ($days as $i => $day) {
            $ideal[$day] = $totalScope - ($totalScope * ($i + 1) / $n);
        }

        $remaining = [];
        $cumDone = 0.0;
        foreach ($days as $day) {
            $cumDone += $doneByDay[$day] ?? 0.0;
            $remaining[$day] = max(0, round($totalScope - $cumDone, 2));
        }

        return [
            'labels' => $days,
            'ideal' => $ideal,
            'actual' => $remaining,
            'scope' => $totalScope,
        ];
    }

    /** Simple velocity = completed points in sprint */
    public static function velocityPoints(int $sprintId): float
    {
        $stmt = db()->prepare('SELECT SUM(COALESCE(story_points, 1)) FROM tasks WHERE sprint_id = ? AND status = "completed"');
        $stmt->execute([$sprintId]);

        return (float) ($stmt->fetchColumn() ?: 0);
    }

    /** Average velocity last N completed sprints */
    public static function avgVelocity(int $groupId, int $lastN = 3): float
    {
        $stmt = db()->prepare('SELECT id FROM sprints WHERE group_id = ? AND status = "completed" ORDER BY datetime(end_date) DESC LIMIT ?');
        $stmt->execute([$groupId, $lastN]);
        $ids = array_map('intval', array_column($stmt->fetchAll(), 'id'));
        if (empty($ids)) {
            return 0;
        }
        $sum = 0.0;
        foreach ($ids as $sid) {
            $sum += self::velocityPoints($sid);
        }

        return round($sum / count($ids), 1);
    }
}
