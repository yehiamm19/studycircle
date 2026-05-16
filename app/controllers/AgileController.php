<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Models\Group;
use App\Models\Requirement;
use App\Models\Sprint;
use App\Models\Task;
use App\Models\UseCase;
use App\Utils\Validator;

class AgileController
{
    public function index(string $groupId): void
    {
        $gid = (int) $groupId;
        $group = $this->authorizeGroup($gid);
        $member = Group::membership($gid, Auth::id());
        $canManage = Group::canManageScrumArtifacts($member);
        $canRoles = Group::canAssignScrumRoles($member);

        $sprints = Sprint::forGroup($gid);
        $useCases = UseCase::forGroup($gid);
        $requirements = Requirement::forGroup($gid);
        foreach ($requirements as $i => $r) {
            $requirements[$i]['completion_pct'] = Requirement::completionPercent((int) $r['id']);
        }

        $matrix = Requirement::traceabilityMatrix($gid);

        $active = null;
        foreach ($sprints as $sp) {
            if (($sp['status'] ?? '') === 'active') {
                $active = $sp;
                break;
            }
        }
        $chartSprintId = (int) ($active['id'] ?? (int) ($sprints[0]['id'] ?? 0));

        $burndown = ['labels' => [], 'ideal' => [], 'actual' => [], 'scope' => 0];
        $sprintProgress = 0.0;
        if ($chartSprintId > 0) {
            $sp = Sprint::find($chartSprintId);
            if ($sp && (int) $sp['group_id'] === $gid) {
                $bd = Sprint::burndownSeries((int) $sp['id'], $sp['start_date'], $sp['end_date']);
                $labels = $bd['labels'];
                $ideal = array_values($bd['ideal']);
                $actual = array_values($bd['actual']);
                $burndown = ['labels' => $labels, 'ideal' => $ideal, 'actual' => $actual, 'scope' => $bd['scope']];
                $sprintProgress = Sprint::progressPercent((int) $sp['id']);
            }
        }

        $velocityAvg = Sprint::avgVelocity($gid);
        $weeklyTrend = Task::completedPerDay($gid, 28);
        $moscowTotals = Task::moscowCountsForGroup($gid);

        $trendLabels = [];
        $trendValues = [];
        $byDay = [];
        foreach ($weeklyTrend as $row) {
            $byDay[$row['d'] ?? ''] = (int) ($row['n'] ?? 0);
        }
        for ($i = 27; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime('-' . $i . ' days'));
            $trendLabels[] = date('M j', strtotime($d));
            $trendValues[] = $byDay[$d] ?? 0;
        }

        view('agile/index', [
            'title' => $group['name'] . ' — Agile Workspace',
            'group' => $group,
            'members' => Group::members($gid),
            'sprints' => $sprints,
            'useCases' => $useCases,
            'requirements' => $requirements,
            'matrix' => $matrix,
            'burndown' => $burndown,
            'velocityAvg' => $velocityAvg,
            'activeSprint' => $active,
            'chartSprintId' => $chartSprintId,
            'sprintProgress' => $sprintProgress,
            'weeklyTrend' => $weeklyTrend,
            'trendLabels' => $trendLabels,
            'trendValues' => $trendValues,
            'moscowTotals' => $moscowTotals,
            'canManage' => $canManage,
            'canRoles' => $canRoles,
        ]);
    }

    public function sprintStore(string $groupId): void
    {
        verify_csrf();
        $gid = (int) $groupId;
        $this->authorizeGroup($gid, true);
        $this->requireArtifactAccess($gid);

        $v = new Validator();
        if (!$v->validate($_POST, [
            'name' => 'required|min:2|max:120',
            'start_date' => 'required|min:8|max:32',
        ])) {
            json_response(['error' => $v->first()], 422);
        }

        $dur = max(1, min(90, (int) ($_POST['duration_days'] ?? 14)));
        $startRaw = trim((string) $_POST['start_date']);
        $startTs = strtotime($startRaw);
        if ($startTs === false) {
            json_response(['error' => 'Invalid start date'], 422);
        }
        $endTs = strtotime('+' . $dur . ' days', $startTs);
        if ($endTs === false) {
            json_response(['error' => 'Could not compute sprint end'], 422);
        }

        $status = $_POST['status'] ?? 'planned';
        if (!in_array($status, Sprint::STATUSES, true)) {
            $status = 'planned';
        }

        $id = Sprint::create([
            'group_id' => $gid,
            'name' => trim((string) $_POST['name']),
            'goal' => (string) ($_POST['goal'] ?? ''),
            'duration_days' => $dur,
            'start_date' => date('Y-m-d', $startTs),
            'end_date' => date('Y-m-d', $endTs),
            'status' => $status,
        ]);

        json_response(['success' => true, 'sprint' => Sprint::find($id)]);
    }

    public function sprintUpdate(string $id): void
    {
        verify_csrf();
        $sid = (int) $id;
        $row = Sprint::find($sid);
        if (!$row) {
            json_response(['error' => 'Not found'], 404);
        }
        $gid = (int) $row['group_id'];
        $this->authorizeGroup($gid, true);
        $this->requireArtifactAccess($gid);

        $fields = [];
        if (isset($_POST['name'])) {
            $fields['name'] = substr(trim((string) $_POST['name']), 0, 120);
        }
        if (isset($_POST['goal'])) {
            $fields['goal'] = (string) $_POST['goal'];
        }
        if (isset($_POST['duration_days'])) {
            $fields['duration_days'] = max(1, min(90, (int) $_POST['duration_days']));
        }
        foreach (['start_date', 'end_date'] as $dk) {
            if (isset($_POST[$dk]) && $_POST[$dk] !== '') {
                $fields[$dk] = substr(trim((string) $_POST[$dk]), 0, 32);
            }
        }
        if (isset($_POST['status'])) {
            $st = (string) $_POST['status'];
            $fields['status'] = in_array($st, Sprint::STATUSES, true) ? $st : 'planned';
        }
        if (empty($fields)) {
            json_response(['success' => true, 'sprint' => $row]);
        }
        Sprint::updateRow($sid, $fields);

        json_response(['success' => true, 'sprint' => Sprint::find($sid)]);
    }

    public function sprintActivate(string $id): void
    {
        verify_csrf();
        $sid = (int) $id;
        $row = Sprint::find($sid);
        if (!$row) {
            json_response(['error' => 'Not found'], 404);
        }
        $gid = (int) $row['group_id'];
        $this->authorizeGroup($gid, true);
        $this->requireArtifactAccess($gid);

        Sprint::activate($sid, $gid);
        json_response(['success' => true, 'sprint' => Sprint::find($sid)]);
    }

    public function useCaseStore(string $groupId): void
    {
        verify_csrf();
        $gid = (int) $groupId;
        $this->authorizeGroup($gid, true);
        $this->requireArtifactAccess($gid);

        $v = new Validator();
        if (!$v->validate($_POST, [
            'code' => 'required|min:2|max:32',
            'title' => 'required|min:2|max:200',
        ])) {
            json_response(['error' => $v->first()], 422);
        }

        try {
            $id = UseCase::create(
                $gid,
                strtoupper(preg_replace('/\s+/', '-', trim((string) $_POST['code']))),
                trim((string) $_POST['title']),
                (string) ($_POST['description'] ?? ''),
            );
        } catch (\Throwable) {
            json_response(['error' => 'Duplicate code or invalid data'], 422);
        }

        $stmt = db()->prepare('SELECT * FROM use_cases WHERE id = ?');
        $stmt->execute([$id]);
        json_response(['success' => true, 'use_case' => $stmt->fetch()]);
    }

    public function requirementStore(string $groupId): void
    {
        verify_csrf();
        $gid = (int) $groupId;
        $this->authorizeGroup($gid, true);
        $this->requireArtifactAccess($gid);

        $v = new Validator();
        if (!$v->validate($_POST, [
            'requirement_ref' => 'required|min:2|max:48',
            'title' => 'required|min:2|max:200',
        ])) {
            json_response(['error' => $v->first()], 422);
        }

        $status = (string) ($_POST['status'] ?? 'active');
        if (!in_array($status, Requirement::STATUSES, true)) {
            $status = 'draft';
        }

        try {
            $rid = Requirement::create([
                'group_id' => $gid,
                'requirement_ref' => strtoupper(trim((string) $_POST['requirement_ref'])),
                'title' => trim((string) $_POST['title']),
                'description' => (string) ($_POST['description'] ?? ''),
                'use_case_id' => isset($_POST['use_case_id']) && $_POST['use_case_id'] !== '' ? (int) $_POST['use_case_id'] : null,
                'status' => $status,
            ]);
        } catch (\Throwable) {
            json_response(['error' => 'Duplicate requirement ID or invalid use case'], 422);
        }

        json_response(['success' => true, 'requirement' => Requirement::find($rid)]);
    }

    public function requirementUpdate(string $id): void
    {
        verify_csrf();
        $rid = (int) $id;
        $req = Requirement::find($rid);
        if (!$req) {
            json_response(['error' => 'Not found'], 404);
        }
        $gid = (int) $req['group_id'];
        $this->authorizeGroup($gid, true);
        $this->requireArtifactAccess($gid);

        $patch = [];
        foreach (['title', 'description'] as $f) {
            if (isset($_POST[$f])) {
                $patch[$f] = $f === 'description' ? (string) $_POST[$f] : substr(trim((string) $_POST[$f]), 0, 200);
            }
        }
        if (isset($_POST['use_case_id'])) {
            $patch['use_case_id'] = $_POST['use_case_id'] === '' ? null : (int) $_POST['use_case_id'];
        }
        if (isset($_POST['status'])) {
            $st = (string) $_POST['status'];
            $patch['status'] = in_array($st, Requirement::STATUSES, true) ? $st : 'draft';
        }
        if ($patch) {
            Requirement::updateRow($rid, $patch);
        }

        $row = Requirement::find($rid);
        if ($row) {
            $row['completion_pct'] = Requirement::completionPercent($rid);
        }

        json_response(['success' => true, 'requirement' => $row]);
    }

    public function memberScrumRole(string $groupId): void
    {
        verify_csrf();
        $gid = (int) $groupId;
        $this->authorizeGroup($gid, true);
        $member = Group::membership($gid, Auth::id());
        if (!Group::canAssignScrumRoles($member)) {
            json_response(['error' => 'Forbidden'], 403);
        }

        $target = (int) ($_POST['user_id'] ?? 0);
        if ($target < 1) {
            json_response(['error' => 'Invalid member'], 422);
        }
        $tm = Group::membership($gid, $target);
        if (!$tm) {
            json_response(['error' => 'Member not in group'], 422);
        }
        if (($tm['role'] ?? '') === 'owner') {
            json_response(['error' => 'Cannot change owner Scrum role here'], 422);
        }

        Group::updateMemberScrumRole($gid, $target, (string) ($_POST['scrum_role'] ?? 'developer'));
        json_response(['success' => true, 'member' => Group::membership($gid, $target)]);
    }

    /** JSON for Chart.js when switching sprint in analytics */
    public function burndownData(string $groupId): void
    {
        $gid = (int) $groupId;
        $this->authorizeGroup($gid, true);
        $sid = (int) ($_GET['sprint_id'] ?? 0);
        if ($sid < 1) {
            json_response(['error' => 'Invalid sprint'], 422);
        }
        $sp = Sprint::find($sid);
        if (!$sp || (int) $sp['group_id'] !== $gid) {
            json_response(['error' => 'Not found'], 404);
        }
        $bd = Sprint::burndownSeries($sid, $sp['start_date'], $sp['end_date']);
        json_response([
            'success' => true,
            'progress' => Sprint::progressPercent($sid),
            'labels' => $bd['labels'],
            'ideal' => array_values($bd['ideal']),
            'actual' => array_values($bd['actual']),
            'scope' => $bd['scope'],
        ]);
    }

    private function authorizeGroup(int $groupId, bool $json = false): array
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

    private function requireArtifactAccess(int $groupId): void
    {
        $m = Group::membership($groupId, Auth::id());
        if (!Group::canManageScrumArtifacts($m)) {
            json_response(['error' => 'Only Product Owner, Scrum Master, or admins can change agile artifacts.'], 403);
        }
    }
}
