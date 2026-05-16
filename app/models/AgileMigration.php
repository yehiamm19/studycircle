<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

/**
 * Idempotent SQLite migrations for Agile / Scrum features.
 */
final class AgileMigration
{
    public static function ensure(): void
    {
        self::createSprintsTable();
        self::addTaskAgileColumns();
        self::addMemberScrumRoleColumn();
        self::createUseCasesTable();
        self::createRequirementsTable();
        self::createTaskRequirementsTable();
        self::createAgileReportsTable();
    }

    private static function tableColumns(string $table): array
    {
        try {
            $rows = db()->query('PRAGMA table_info(' . $table . ')')->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable) {
            return [];
        }
        $names = [];
        foreach ($rows as $r) {
            if (!empty($r['name'])) {
                $names[] = $r['name'];
            }
        }

        return $names;
    }

    /** Sprints must exist before tasks.sprint_id FK (SQLite allows forward ref in ALTER). */
    private static function createSprintsTable(): void
    {
        db()->exec('
            CREATE TABLE IF NOT EXISTS sprints (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                group_id INTEGER NOT NULL,
                name TEXT NOT NULL,
                goal TEXT DEFAULT \'\',
                duration_days INTEGER NOT NULL DEFAULT 14,
                start_date TEXT NOT NULL,
                end_date TEXT NOT NULL,
                status TEXT NOT NULL DEFAULT \'planned\',
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE
            )
        ');
        db()->exec('CREATE INDEX IF NOT EXISTS idx_sprints_group_status ON sprints(group_id, status)');
    }

    private static function addTaskAgileColumns(): void
    {
        $cols = self::tableColumns('tasks');
        if (!in_array('sprint_id', $cols, true)) {
            db()->exec('ALTER TABLE tasks ADD COLUMN sprint_id INTEGER REFERENCES sprints(id) ON DELETE SET NULL');
        }
        if (!in_array('moscow_priority', $cols, true)) {
            db()->exec("ALTER TABLE tasks ADD COLUMN moscow_priority TEXT NOT NULL DEFAULT 'could'");
        }
        if (!in_array('story_points', $cols, true)) {
            db()->exec('ALTER TABLE tasks ADD COLUMN story_points REAL NOT NULL DEFAULT 1');
        }
    }

    private static function addMemberScrumRoleColumn(): void
    {
        $cols = self::tableColumns('group_members');
        if (!in_array('scrum_role', $cols, true)) {
            db()->exec("ALTER TABLE group_members ADD COLUMN scrum_role TEXT NOT NULL DEFAULT 'developer'");
        }
    }

    private static function createUseCasesTable(): void
    {
        db()->exec('
            CREATE TABLE IF NOT EXISTS use_cases (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                group_id INTEGER NOT NULL,
                code TEXT NOT NULL,
                title TEXT NOT NULL,
                description TEXT DEFAULT \'\',
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE,
                UNIQUE(group_id, code)
            )
        ');
        db()->exec('CREATE INDEX IF NOT EXISTS idx_use_cases_group ON use_cases(group_id)');
    }

    private static function createRequirementsTable(): void
    {
        db()->exec('
            CREATE TABLE IF NOT EXISTS requirements (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                group_id INTEGER NOT NULL,
                requirement_ref TEXT NOT NULL,
                title TEXT NOT NULL,
                description TEXT DEFAULT \'\',
                use_case_id INTEGER,
                status TEXT NOT NULL DEFAULT \'draft\',
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE,
                FOREIGN KEY (use_case_id) REFERENCES use_cases(id) ON DELETE SET NULL,
                UNIQUE(group_id, requirement_ref)
            )
        ');
        db()->exec('CREATE INDEX IF NOT EXISTS idx_requirements_group ON requirements(group_id)');
        db()->exec('CREATE INDEX IF NOT EXISTS idx_requirements_uc ON requirements(use_case_id)');
    }

    private static function createTaskRequirementsTable(): void
    {
        db()->exec('
            CREATE TABLE IF NOT EXISTS task_requirements (
                task_id INTEGER NOT NULL,
                requirement_id INTEGER NOT NULL,
                PRIMARY KEY (task_id, requirement_id),
                FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
                FOREIGN KEY (requirement_id) REFERENCES requirements(id) ON DELETE CASCADE
            )
        ');
        db()->exec('CREATE INDEX IF NOT EXISTS idx_task_requirements_req ON task_requirements(requirement_id)');
    }

    private static function createAgileReportsTable(): void
    {
        db()->exec('
            CREATE TABLE IF NOT EXISTS agile_reports (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                group_id INTEGER NOT NULL,
                sprint_id INTEGER,
                report_type TEXT NOT NULL,
                payload_json TEXT NOT NULL,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE,
                FOREIGN KEY (sprint_id) REFERENCES sprints(id) ON DELETE SET NULL
            )
        ');
        db()->exec('CREATE INDEX IF NOT EXISTS idx_agile_reports_group ON agile_reports(group_id, created_at)');
    }
}
