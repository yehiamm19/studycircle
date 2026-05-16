<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Models\Group;
use App\Models\Message;
use App\Services\GamificationService;
use App\Services\NotificationService;

class ChatController
{
    public function index(string $groupId): void
    {
        $group = Group::find((int) $groupId);
        if (!$group || !Group::isMember((int) $groupId, Auth::id())) {
            flash('error', 'Group not found.');
            redirect('/groups');
        }
        view('chat/index', [
            'title' => $group['name'] . ' — Chat',
            'group' => $group,
            'messages' => Message::forGroup((int) $groupId),
            'members' => Group::members((int) $groupId),
        ]);
    }

    public function fetch(string $groupId): void
    {
        if (!Group::isMember((int) $groupId, Auth::id())) {
            json_response(['error' => 'Unauthorized'], 403);
        }
        $afterId = (int) ($_GET['after'] ?? 0);
        json_response(['messages' => Message::forGroup((int) $groupId, 50, $afterId ?: null)]);
    }

    public function send(string $groupId): void
    {
        verify_csrf();
        if (!Group::isMember((int) $groupId, Auth::id())) {
            json_response(['error' => 'Unauthorized'], 403);
        }
        $body = trim($_POST['body'] ?? '');
        if ($body === '') json_response(['error' => 'Message cannot be empty'], 422);

        $message = Message::create((int) $groupId, Auth::id(), $body);
        GamificationService::addXp(Auth::id(), config('xp_per_message', 2));
        NotificationService::notifyGroup((int) $groupId, Auth::id(), 'message', 'New message', substr($body, 0, 80), "/groups/{$groupId}/chat");

        json_response(['message' => $message]);
    }
}

