<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Models\Group;
use App\Models\Resource;
use App\Services\GamificationService;
use App\Utils\Uploader;

class ResourceController
{
    public function index(string $groupId): void
    {
        $group = Group::find((int) $groupId);
        if (!$group || !Group::isMember((int) $groupId, Auth::id())) {
            flash('error', 'Group not found.');
            redirect('/groups');
        }
        view('resources/index', [
            'title' => $group['name'] . ' — Resources',
            'group' => $group,
            'resources' => Resource::forGroup((int) $groupId),
        ]);
    }

    public function upload(string $groupId): void
    {
        verify_csrf();
        if (!Group::isMember((int) $groupId, Auth::id())) {
            json_response(['error' => 'Unauthorized'], 403);
        }
        if (empty($_FILES['file'])) {
            json_response(['error' => 'No file uploaded'], 422);
        }

        $result = Uploader::upload(
            $_FILES['file'],
            base_path('uploads/resources'),
            config('upload_allowed'),
            config('upload_max_size')
        );

        if (isset($result['error'])) {
            json_response(['error' => $result['error']], 422);
        }

        $id = Resource::create([
            'group_id' => (int) $groupId,
            'user_id' => Auth::id(),
            'title' => $_POST['title'] ?? $result['original_name'],
            'category' => $_POST['category'] ?? 'notes',
            'filename' => $result['filename'],
            'original_name' => $result['original_name'],
            'mime_type' => $result['mime_type'],
            'file_size' => $result['file_size'],
        ]);

        GamificationService::addXp(Auth::id(), 10);

        json_response(['success' => true, 'resource' => Resource::find($id)]);
    }

    public function download(string $id): void
    {
        $resource = Resource::find((int) $id);
        if (!$resource || !Group::isMember((int) $resource['group_id'], Auth::id())) {
            http_response_code(404);
            exit('Not found');
        }
        $path = base_path('uploads/resources/' . $resource['filename']);
        if (!file_exists($path)) {
            http_response_code(404);
            exit('File not found');
        }
        header('Content-Type: ' . $resource['mime_type']);
        header('Content-Disposition: attachment; filename="' . $resource['original_name'] . '"');
        header('Content-Length: ' . $resource['file_size']);
        readfile($path);
        exit;
    }

    public function delete(string $id): void
    {
        verify_csrf();
        $resource = Resource::find((int) $id);
        if (!$resource) json_response(['error' => 'Not found'], 404);
        if (!Group::isMember((int) $resource['group_id'], Auth::id())) {
            json_response(['error' => 'Unauthorized'], 403);
        }
        if ((int) $resource['user_id'] !== Auth::id()) {
            $group = Group::find((int) $resource['group_id']);
            if ((int) ($group['owner_id'] ?? 0) !== Auth::id()) {
                json_response(['error' => 'Permission denied'], 403);
            }
        }
        Resource::delete((int) $id);
        Uploader::delete(base_path('uploads/resources'), $resource['filename']);
        json_response(['success' => true]);
    }
}

