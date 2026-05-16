<?php

declare(strict_types=1);

if (!function_exists('studycircle_seed_demo_agile_workspace')) {
    /**
     * Idempotent demo workspace: Scrum / Sprints / Agile / Kanban sample data.
     * Safe to run every request — skips if demo group invite already exists.
     */
    function studycircle_seed_demo_agile_workspace(): void
    {
        if (!function_exists('db')) {
            return;
        }

        $inviteCode = 'DEMO-AGILE-SCRUM-KANBAN';

        try {
            $pdo = db();
        } catch (\Throwable) {
            return;
        }

        $chk = $pdo->prepare('SELECT id FROM groups WHERE invite_code = ? LIMIT 1');
        $chk->execute([$inviteCode]);
        if ($chk->fetchColumn()) {
            return;
        }

        $ownerId = (int) $pdo->query('SELECT id FROM users ORDER BY id ASC LIMIT 1')->fetchColumn();
        if ($ownerId < 1) {
            return;
        }

        if (class_exists(\App\Models\AgileMigration::class)) {
            \App\Models\AgileMigration::ensure();
        }

        try {
            $pdo->beginTransaction();

            $pdo->prepare('INSERT INTO groups (name, description, color, icon, owner_id, invite_code) VALUES (?,?,?,?,?,?)')->execute([
                'StudyCircle · Agile, Scrum & Kanban (Demo)',
                'Ready to explore: active and completed sprints, requirements, use cases, a Kanban board with MoSCoW priorities and story points. Ideal for onboarding.',
                'fuchsia',
                'orbit',
                $ownerId,
                $inviteCode,
            ]);
            $gid = (int) $pdo->lastInsertId();

            $userIds = $pdo->query('SELECT id FROM users ORDER BY id ASC LIMIT 6')->fetchAll(\PDO::FETCH_COLUMN);
            $userIds = array_map('intval', $userIds ?: [$ownerId]);

            $insMem = $pdo->prepare('INSERT OR IGNORE INTO group_members (group_id, user_id, role, scrum_role) VALUES (?,?,?,?)');
            $i = 0;
            foreach ($userIds as $uid) {
                if ($uid < 1) {
                    continue;
                }
                if ($uid === $ownerId) {
                    $insMem->execute([$gid, $uid, 'owner', 'product_owner']);
                } else {
                    $role = $i === 1 ? 'admin' : 'member';
                    $scrum = $i === 1 ? 'scrum_master' : 'developer';
                    $insMem->execute([$gid, $uid, $role, $scrum]);
                }
                ++$i;
            }

            // Use cases
            $ucStmt = $pdo->prepare('INSERT INTO use_cases (group_id, code, title, description) VALUES (?,?,?,?)');
            $ucStmt->execute([$gid, 'UC-KANBAN', 'Manage study tasks on Kanban', 'Drag columns, priorities, sprint assignment.']);
            $ucKanban = (int) $pdo->lastInsertId();
            $ucStmt->execute([$gid, 'UC-SPRINT', 'Run Scrum sprints', 'Plan backlog, activate sprint, review burndown.']);
            $ucSprint = (int) $pdo->lastInsertId();
            $ucStmt->execute([$gid, 'UC-TRACE', 'Requirements traceability', 'Link tasks to REQ IDs and use cases.']);
            $ucTrace = (int) $pdo->lastInsertId();

            $monday = strtotime('monday this week');
            $prevStart = date('Y-m-d', strtotime('-21 days', $monday));
            $prevEnd = date('Y-m-d', strtotime('-8 days', $monday));
            $actStart = date('Y-m-d', $monday);
            $actEnd = date('Y-m-d', strtotime('+13 days', $monday));
            $nextStart = date('Y-m-d', strtotime('+14 days', $monday));
            $nextEnd = date('Y-m-d', strtotime('+27 days', $monday));

            $spStmt = $pdo->prepare('INSERT INTO sprints (group_id, name, goal, duration_days, start_date, end_date, status) VALUES (?,?,?,?,?,?,?)');
            $spStmt->execute([$gid, 'Sprint 0 — Foundation (completed)', 'Close initial backlog items and stabilize board.', 14, $prevStart, $prevEnd, 'completed']);
            $sprintDone = (int) $pdo->lastInsertId();
            $spStmt->execute([$gid, 'Sprint 1 — Active (current)', 'Ship sprint planning, MoSCoW filters, and burndown.', 14, $actStart, $actEnd, 'active']);
            $sprintActive = (int) $pdo->lastInsertId();
            $spStmt->execute([$gid, 'Sprint 2 — Upcoming', 'Traceability matrix polish and velocity trend cards.', 14, $nextStart, $nextEnd, 'planned']);
            $sprintPlanned = (int) $pdo->lastInsertId();

            $reqStmt = $pdo->prepare('INSERT INTO requirements (group_id, requirement_ref, title, description, use_case_id, status) VALUES (?,?,?,?,?,?)');
            $reqStmt->execute([$gid, 'REQ-001', 'Kanban columns & drag state', 'Tasks must move between To Do / In Progress / Done.', $ucKanban, 'active']);
            $r1 = (int) $pdo->lastInsertId();
            $reqStmt->execute([$gid, 'REQ-002', 'MoSCoW prioritization', "Each task exposes Must/Should/Could/Won't.", $ucKanban, 'active']);
            $r2 = (int) $pdo->lastInsertId();
            $reqStmt->execute([$gid, 'REQ-003', 'Sprint backlog assignment', 'Tasks can belong to a sprint or backlog.', $ucSprint, 'active']);
            $r3 = (int) $pdo->lastInsertId();
            $reqStmt->execute([$gid, 'REQ-004', 'Sprint activation & velocity', 'One active sprint per group; velocity from story points.', $ucSprint, 'done']);
            $r4 = (int) $pdo->lastInsertId();
            $reqStmt->execute([$gid, 'REQ-005', 'Traceability links', 'Tasks link to requirement IDs.', $ucTrace, 'active']);
            $r5 = (int) $pdo->lastInsertId();
            $reqStmt->execute([$gid, 'REQ-006', 'Agile workspace hub', 'Single place for sprints, matrix, charts, roles.', $ucTrace, 'draft']);
            $r6 = (int) $pdo->lastInsertId();

            $assign = static function (int $idx) use ($userIds): ?int {
                if ($userIds === []) {
                    return null;
                }

                return $userIds[$idx % count($userIds)] ?: null;
            };

            $taskIns = $pdo->prepare('INSERT INTO tasks (group_id, title, description, status, priority, label, due_date, assignee_id, sprint_id, moscow_priority, story_points, position, created_by, completed_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)');

            $tasks = [
                ['Set up sprint board columns', 'Align To Do / In Progress / Done with team.', 'completed', 'medium', 'project', null, $assign(0), $sprintDone, 'must', 3.0, 0, $ownerId, date('Y-m-d H:i:s', strtotime('-18 days'))],
                ['Define MoSCoW legend for study tasks', 'Document priority meaning for the group.', 'completed', 'high', 'reading', null, $assign(1), $sprintDone, 'must', 2.0, 1, $ownerId, date('Y-m-d H:i:s', strtotime('-16 days'))],
                ['Link first tasks to REQ-001', 'Create traceability from board to requirements.', 'completed', 'medium', 'homework', null, $assign(2), $sprintDone, 'should', 2.0, 2, $ownerId, date('Y-m-d H:i:s', strtotime('-14 days'))],
                ['Review burndown chart with team', 'Explain ideal vs remaining curve.', 'todo', 'low', 'reading', null, $assign(3), $sprintDone, 'could', 1.0, 3, $ownerId, null],
                ['Draft sprint goal for Sprint 1', 'Outcome-oriented goal for active sprint.', 'completed', 'high', 'homework', null, $assign(0), $sprintActive, 'must', 2.0, 0, $ownerId, date('Y-m-d H:i:s', strtotime('-2 days'))],
                ['Apply MoSCoW to exam prep tasks', 'Tag high-stakes items as Must.', 'in_progress', 'high', 'exam', null, $assign(1), $sprintActive, 'must', 5.0, 1, $ownerId, null],
                ['Break down literature review card', 'Split into checklist sub-notes in description.', 'in_progress', 'medium', 'reading', null, $assign(2), $sprintActive, 'should', 3.0, 2, $ownerId, null],
                ['Backlog grooming — unassigned cards', 'Keep no-sprint tasks in backlog filter.', 'todo', 'medium', 'project', null, $assign(3), null, 'should', 2.0, 3, $ownerId, null],
                ['Align REQ-005 links on Kanban cards', 'Ensure each in-scope task references a REQ.', 'todo', 'high', 'homework', null, $assign(0), $sprintActive, 'must', 2.0, 4, $ownerId, null],
                ['Prepare sprint review notes', 'What shipped vs carry-over to Sprint 2.', 'todo', 'medium', 'project', null, $assign(1), $sprintActive, 'could', 1.5, 5, $ownerId, null],
                ['Spike: chart.js theming', 'Match StudyCircle glass / dark mode.', 'completed', 'low', 'project', null, $assign(2), $sprintActive, 'could', 1.0, 6, $ownerId, date('Y-m-d H:i:s', strtotime('-1 day'))],
                ['Plan Sprint 2 scope', 'Pull items from backlog into next sprint.', 'todo', 'medium', 'homework', null, $assign(0), $sprintPlanned, 'should', 2.0, 0, $ownerId, null],
                ['Update traceability matrix', 'REQ vs task mapping for stakeholders.', 'todo', 'high', 'project', null, $assign(1), $sprintPlanned, 'must', 3.0, 1, $ownerId, null],
                ['Scrum roles review', 'Confirm PO/SM/Developer responsibilities.', 'todo', 'low', 'reading', null, $assign(2), $sprintPlanned, 'wont', 0.5, 2, $ownerId, null],
            ];

            $taskIds = [];
            foreach ($tasks as $row) {
                $taskIns->execute(array_merge([$gid], $row));
                $taskIds[] = (int) $pdo->lastInsertId();
            }

            $link = $pdo->prepare('INSERT OR IGNORE INTO task_requirements (task_id, requirement_id) VALUES (?,?)');
            $pairs = [
                [0, $r1], [1, $r2], [2, $r5], [3, $r6], [4, $r3], [5, $r2], [5, $r1], [6, $r1], [7, $r3], [8, $r5], [9, $r4], [10, $r6], [11, $r3], [12, $r5], [13, $r4],
            ];
            foreach ($pairs as [$ti, $rid]) {
                if (isset($taskIds[$ti])) {
                    $link->execute([$taskIds[$ti], $rid]);
                }
            }

            $payload = json_encode([
                'title' => 'Demo velocity snapshot',
                'avg_velocity_hint' => 'Computed live from completed story points in recent sprints.',
                'generated_by' => 'seed_agile_demo',
            ], JSON_THROW_ON_ERROR);
            $pdo->prepare('INSERT INTO agile_reports (group_id, sprint_id, report_type, payload_json) VALUES (?,?,?,?)')->execute([
                $gid,
                $sprintActive,
                'demo_onboarding_summary',
                $payload,
            ]);

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $msg = $e->getMessage();
            // Concurrent bootstrap: another request inserted the demo group first
            if (str_contains($msg, 'invite_code') && str_contains($msg, 'UNIQUE')) {
                return;
            }
            if (function_exists('error_log')) {
                error_log('studycircle_seed_demo_agile_workspace: ' . $msg);
            }
        }
    }
}
