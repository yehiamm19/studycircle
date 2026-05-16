<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Models\Group;
use App\Models\Task;
use App\Models\FocusSession;
use App\Models\Notification;
use App\Models\Story;
use App\Models\User;
use App\Services\GamificationService;

class DashboardController
{
    public function index(): void
    {
        $userId = Auth::id();
        $user = User::find($userId);
        $groups = Group::forUser($userId);
        $taskStats = Task::statsForUser($userId);
        $focusStats = FocusSession::stats($userId);
        $notifications = Notification::forUser($userId, 5);
        $leaderboard = User::leaderboard(5);

        $recentActivity = db()->prepare('SELECT * FROM activity_log WHERE user_id = ? ORDER BY created_at DESC LIMIT 8');
        $recentActivity->execute([$userId]);

        $storyGroups = array_map(
            static fn (array $g): array => ['id' => $g['id'], 'name' => $g['name']],
            $groups
        );
        $studyStoryPayload = [
            'buckets' => Story::serializeBuckets(Story::railBucketsPublic()),
            'viewerUserId' => $userId,
            'composerRedirect' => '/dashboard',
            'userGroups' => $storyGroups,
            'defaultGroupId' => null,
            'variant' => 'public',
        ];

        view('dashboard/index', [
            'title' => 'Dashboard',
            'user' => $user,
            'groups' => $groups,
            'taskStats' => $taskStats,
            'focusStats' => $focusStats,
            'notifications' => $notifications,
            'leaderboard' => $leaderboard,
            'recentActivity' => $recentActivity->fetchAll(),
            'stats' => GamificationService::userStats($userId),
            'studyStoryPayload' => $studyStoryPayload,
        ]);
    }

    public function search(): void
    {
        $q = trim($_GET['q'] ?? '');
        if (strlen($q) < 2) {
            json_response(['results' => []]);
        }
        $userId = Auth::id();
        $groups = db()->prepare('
            SELECT g.id, g.name, g.color, "group" as type FROM groups g
            JOIN group_members gm ON g.id = gm.group_id
            WHERE gm.user_id = ? AND g.name LIKE ?
            LIMIT 5
        ');
        $groups->execute([$userId, "%{$q}%"]);

        $tasks = db()->prepare('
            SELECT t.id, t.title, t.group_id, g.name as group_name, "task" as type FROM tasks t
            JOIN groups g ON t.group_id = g.id
            JOIN group_members gm ON g.id = gm.group_id
            WHERE gm.user_id = ? AND t.title LIKE ?
            LIMIT 5
        ');
        $tasks->execute([$userId, "%{$q}%"]);

        json_response(['results' => array_merge($groups->fetchAll(), $tasks->fetchAll())]);
    }
}

