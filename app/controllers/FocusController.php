<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Models\AmbientTrack;
use App\Models\FocusAmbient;
use App\Models\FocusSession;
use App\Models\Group;
use App\Services\GamificationService;

class FocusController
{
    public function index(): void
    {
        $userId = Auth::id();
        view('focus/index', [
            'title' => 'Focus Timer',
            'stats' => FocusSession::stats($userId),
            'history' => FocusSession::history($userId),
            'groups' => Group::forUser($userId),
            'ambient' => FocusAmbient::get($userId),
            'customTracks' => AmbientTrack::forUser($userId),
            'trackIcons' => AmbientTrack::ALLOWED_ICONS,
        ]);
    }

    public function complete(): void
    {
        verify_csrf();
        $input = json_input() ?: $_POST;

        $duration = max(1, (int) ($input['duration_minutes'] ?? config('pomodoro_default', 25)));
        $completed = (int) ($input['completed'] ?? 0);
        $isBreak = (int) ($input['is_break'] ?? 0);

        if ($isBreak) {
            json_response(['success' => true, 'break' => true]);
        }

        $startedAt = $input['started_at'] ?? null;
        if (!$startedAt || !strtotime($startedAt)) {
            $startedAt = date('Y-m-d H:i:s', time() - ($duration * 60));
        }

        $id = FocusSession::create([
            'user_id' => Auth::id(),
            'group_id' => $input['group_id'] ?? null,
            'task_id' => $input['task_id'] ?? null,
            'duration_minutes' => $duration,
            'completed' => $completed ? 1 : 0,
            'started_at' => $startedAt,
            'ended_at' => date('Y-m-d H:i:s'),
        ]);

        $session = FocusSession::find($id);
        $userId = Auth::id();
        $response = [
            'success' => true,
            'session' => $session,
            'stats' => FocusSession::stats($userId),
        ];

        if ($completed) {
            $response['achievements'] = GamificationService::addXp($userId, config('xp_per_focus', 10));
            GamificationService::logActivity($userId, 'focus_completed', ['minutes' => $duration]);
        }

        json_response($response);
    }

    public function ambientSettings(): void
    {
        json_response(['settings' => FocusAmbient::get(Auth::id())]);
    }

    public function saveAmbient(): void
    {
        verify_csrf();
        $input = json_input() ?: $_POST;
        FocusAmbient::save(Auth::id(), [
            'youtube_url' => $input['youtube_url'] ?? '',
            'volume' => $input['volume'] ?? 0.5,
            'active_sound' => $input['active_sound'] ?? '',
        ]);
        json_response(['success' => true, 'settings' => FocusAmbient::get(Auth::id())]);
    }

    public function listTracks(): void
    {
        json_response(['tracks' => AmbientTrack::forUser(Auth::id())]);
    }

    public function storeTrack(): void
    {
        verify_csrf();
        $input = json_input() ?: $_POST;
        $name = trim((string) ($input['name'] ?? ''));
        $url = trim((string) ($input['youtube_url'] ?? ''));
        $icon = (string) ($input['icon'] ?? 'youtube');

        if ($name === '') {
            json_response(['error' => 'Name is required'], 422);
        }
        if ($url === '') {
            json_response(['error' => 'YouTube URL is required'], 422);
        }
        if (!preg_match('/(?:youtube\.com|youtu\.be)/i', $url)) {
            json_response(['error' => 'Invalid YouTube link'], 422);
        }

        $id = AmbientTrack::create(Auth::id(), $name, $icon, $url);
        $track = AmbientTrack::find(Auth::id(), $id);
        json_response(['success' => true, 'track' => $track, 'tracks' => AmbientTrack::forUser(Auth::id())]);
    }

    public function deleteTrack(string $id): void
    {
        verify_csrf();
        if (!AmbientTrack::delete(Auth::id(), (int) $id)) {
            json_response(['error' => 'Track not found'], 404);
        }
        json_response(['success' => true, 'tracks' => AmbientTrack::forUser(Auth::id())]);
    }

    public function groupTasks(string $groupId): void
    {
        if (!Group::isMember((int) $groupId, Auth::id())) {
            json_response(['error' => 'Unauthorized'], 403);
        }
        $tasks = db()->prepare('SELECT id, title, status FROM tasks WHERE group_id = ? AND status != "completed" ORDER BY title');
        $tasks->execute([(int) $groupId]);
        json_response(['tasks' => $tasks->fetchAll()]);
    }
}
