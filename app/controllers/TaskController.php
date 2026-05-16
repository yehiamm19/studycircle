<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Models\Group;
use App\Models\Requirement;
use App\Models\Sprint;
use App\Models\Task;
use App\Services\GamificationService;
use App\Services\NotificationService;
use App\Utils\Validator;

class TaskController
{
    public function board(string $groupId): void
    {
        $group = $this->authorize((int) $groupId, false);
        $filters = $this->boardFiltersFromRequest();
        $tasks = Task::forGroup((int) $groupId, $filters);
        $columns = ['todo' => [], 'in_progress' => [], 'completed' => []];
        foreach ($tasks as $t) {
            if (isset($columns[$t['status']])) {
                $columns[$t['status']][] = $t;
            }
        }
        $gid = (int) $groupId;
        view('tasks/board', [
            'title' => $group['name'] . ' — Tasks',
            'group' => $group,
            'columns' => $columns,
            'members' => Group::members($gid),
            'sprints' => Sprint::forGroup($gid),
            'requirements' => Requirement::forGroup($gid),
            'board_filters' => $filters,
            'moscow_by_column' => Task::moscowCountsByStatus($gid, $filters),
        ]);
    }

    /** @return array{sprint_id?: string|int, moscow_priority?: string} */
    private function boardFiltersFromRequest(): array
    {
        $f = [];
        if (!empty($_GET['sprint'])) {
            $s = (string) $_GET['sprint'];
            $f['sprint_id'] = $s === 'backlog' ? 'none' : $s;
        }
        if (!empty($_GET['moscow'])) {
            $f['moscow_priority'] = Task::sanitizeMoscow((string) $_GET['moscow']);
        }

        return $f;
    }

    public function store(string $groupId): void
    {
        verify_csrf();
        $this->authorize((int) $groupId);
        $v = new Validator();
        if (!$v->validate($_POST, ['title' => 'required|min:1|max:200'])) {
            json_response(['error' => $v->first()], 422);
        }
        $id = Task::create([
            'group_id' => (int) $groupId,
            'title' => $_POST['title'],
            'description' => $_POST['description'] ?? '',
            'priority' => $_POST['priority'] ?? 'medium',
            'label' => $_POST['label'] ?? 'homework',
            'due_date' => $_POST['due_date'] ?: null,
            'assignee_id' => ($_POST['assignee_id'] ?? '') !== '' ? (int) $_POST['assignee_id'] : null,
            'created_by' => Auth::id(),
            'sprint_id' => isset($_POST['sprint_id']) && $_POST['sprint_id'] !== '' ? (int) $_POST['sprint_id'] : null,
            'moscow_priority' => Task::sanitizeMoscow((string) ($_POST['moscow_priority'] ?? 'could')),
            'story_points' => max(0, (float) ($_POST['story_points'] ?? 1)),
        ]);
        Requirement::syncTaskLinks($id, $this->requirementIdsFromRequest());
        if (!empty($_POST['assignee_id'])) {
            NotificationService::create((int) $_POST['assignee_id'], 'task', 'Task assigned', 'You were assigned: ' . $_POST['title'], "/groups/{$groupId}/tasks");
        }
        $task = Task::find($id);
        json_response(['success' => true, 'task' => $task]);
    }

    public function update(string $id): void
    {
        verify_csrf();
        $task = Task::find((int) $id);
        if (!$task) json_response(['error' => 'Not found'], 404);
        $this->authorize((int) $task['group_id']);

        $data = [];
        foreach (['title', 'description', 'status', 'priority', 'label', 'due_date'] as $f) {
            if (isset($_POST[$f])) {
                $data[$f] = $_POST[$f] ?: ($f === 'description' ? '' : null);
            }
        }
        if (isset($_POST['assignee_id'])) {
            $data['assignee_id'] = $_POST['assignee_id'] !== '' ? (int) $_POST['assignee_id'] : null;
        }
        foreach (['sprint_id'] as $f) {
            if (!array_key_exists($f, $_POST)) {
                continue;
            }
            $data[$f] = $_POST[$f] === '' || $_POST[$f] === null ? null : (int) $_POST[$f];
        }
        if (isset($_POST['moscow_priority'])) {
            $data['moscow_priority'] = $_POST['moscow_priority'];
        }
        if (isset($_POST['story_points'])) {
            $data['story_points'] = max(0, (float) $_POST['story_points']);
        }

        Task::update((int) $id, $data);

        if (!empty($_POST['sync_requirements'])) {
            Requirement::syncTaskLinks((int) $id, $this->requirementIdsFromRequest());
        }

        if (($data['status'] ?? '') === 'completed') {
            $unlocked = GamificationService::addXp(Auth::id(), config('xp_per_task', 15));
            GamificationService::logActivity(Auth::id(), 'task_completed', ['task_id' => $id]);
            json_response(['success' => true, 'achievements' => $unlocked]);
        }
        json_response(['success' => true, 'task' => Task::find((int) $id)]);
    }

    public function reorder(string $groupId): void
    {
        verify_csrf();
        $this->authorize((int) $groupId);
        $input = json_input() ?: $_POST;
        $status = $input['status'] ?? 'todo';
        $taskIds = $input['task_ids'] ?? [];
        Task::reorder((int) $groupId, $status, array_map('intval', $taskIds));
        json_response(['success' => true]);
    }

    public function delete(string $id): void
    {
        verify_csrf();
        $task = Task::find((int) $id);
        if (!$task) json_response(['error' => 'Not found'], 404);
        $this->authorize((int) $task['group_id']);
        Task::delete((int) $id);
        json_response(['success' => true]);
    }

    public function comments(string $id): void
    {
        $task = Task::find((int) $id);
        if (!$task) json_response(['error' => 'Not found'], 404);
        $this->authorize((int) $task['group_id']);
        json_response(['comments' => Task::comments((int) $id)]);
    }

    public function addComment(string $id): void
    {
        verify_csrf();
        $task = Task::find((int) $id);
        if (!$task) json_response(['error' => 'Not found'], 404);
        $this->authorize((int) $task['group_id']);
        $body = trim($_POST['body'] ?? '');
        if ($body === '') json_response(['error' => 'Comment cannot be empty'], 422);
        Task::addComment((int) $id, Auth::id(), $body);
        json_response(['comments' => Task::comments((int) $id)]);
    }

    private function authorize(int $groupId, bool $json = true): array
    {
        $group = Group::find($groupId);
        if (!$group || !Group::isMember($groupId, Auth::id())) {
            if ($json) {
                json_response(['error' => 'Unauthorized'], 403);
            }
            flash('error', 'Group not found.');
            redirect('/groups');
        }
        return $group;
    }

    /** @return int[] */
    private function requirementIdsFromRequest(): array
    {
        $raw = $_POST['requirement_ids'] ?? null;
        if ($raw === null) {
            return [];
        }
        if (!is_array($raw)) {
            $raw = [$raw];
        }
        $out = [];
        foreach ($raw as $x) {
            $n = (int) $x;
            if ($n > 0) {
                $out[] = $n;
            }
        }

        return array_values(array_unique($out));
    }
}
