<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Models\Notification;

class NotificationController
{
    public function index(): void
    {
        json_response(['notifications' => Notification::forUser(Auth::id())]);
    }

    public function unread(): void
    {
        json_response(['count' => Notification::unreadCount(Auth::id())]);
    }

    public function markRead(string $id): void
    {
        verify_csrf();
        Notification::markRead((int) $id, Auth::id());
        json_response(['success' => true]);
    }

    public function markAllRead(): void
    {
        verify_csrf();
        Notification::markAllRead(Auth::id());
        json_response(['success' => true]);
    }
}

