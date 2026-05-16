<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Models\Group;
use App\Models\Story;
use App\Services\GamificationService;
use App\Utils\Uploader;

class StoryController
{
    private const MOODS = ['achievement', 'task', 'focus', 'general'];

    public function hub(): void
    {
        Story::purgeExpired();
        $storyGroups = array_map(
            static fn (array $g): array => ['id' => $g['id'], 'name' => $g['name']],
            Group::forUser(Auth::id())
        );
        $studyStoryPayload = [
            'buckets' => Story::serializeBuckets(Story::railBucketsPublic()),
            'viewerUserId' => Auth::id(),
            'composerRedirect' => '/stories',
            'userGroups' => $storyGroups,
            'defaultGroupId' => null,
            'variant' => 'public',
        ];

        view('stories/hub', [
            'title' => 'Study Story',
            'studyStoryPayload' => $studyStoryPayload,
        ]);
    }

    public function store(): void
    {
        verify_csrf();
        $wantsJson = $this->wantsJson();
        $redirectTo = $this->safeRedirectPath();

        if (empty($_FILES['photo']) || ($_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            if ($wantsJson) {
                json_response(['error' => 'Choose a photo for your Study Story.'], 422);
            }
            flash('error', 'Choose a photo for your Study Story.');
            redirect($redirectTo);
        }

        $caption = mb_substr(trim($_POST['caption'] ?? ''), 0, (int) config('story_caption_max', 220));
        $mood = $_POST['mood'] ?? 'general';
        if (!in_array($mood, self::MOODS, true)) {
            $mood = 'general';
        }

        $groupIdRaw = $_POST['group_id'] ?? '';
        $groupId = ($groupIdRaw === '' || $groupIdRaw === '0') ? null : (int) $groupIdRaw;
        if ($groupId !== null) {
            if (!Group::find($groupId) || !Group::isMember($groupId, Auth::id())) {
                if ($wantsJson) {
                    json_response(['error' => 'Invalid group for this story.'], 422);
                }
                flash('error', 'Invalid group for this story.');
                redirect($redirectTo);
            }
        }

        $maxBytes = (int) config('story_upload_max_size', config('upload_max_size'));
        $result = Uploader::upload($_FILES['photo'], base_path('uploads/stories'), config('story_upload_allowed'), $maxBytes);
        if (isset($result['error'])) {
            if ($wantsJson) {
                json_response(['error' => $result['error']], 422);
            }
            flash('error', $result['error']);
            redirect($redirectTo);
        }

        $ctx = trim($_POST['context_note'] ?? '');
        $meta = ['note' => $ctx !== '' ? mb_substr($ctx, 0, 120) : ''];
        Story::create([
            'user_id' => Auth::id(),
            'group_id' => $groupId,
            'caption' => $caption,
            'mood' => $mood,
            'context_json' => json_encode($meta, JSON_UNESCAPED_UNICODE) ?: '{}',
            'filename' => $result['filename'],
            'mime_type' => $result['mime_type'],
            'file_size' => $result['file_size'],
        ]);

        GamificationService::logActivity(Auth::id(), 'story_shared', [
            'public' => $groupId === null,
            'group_id' => $groupId,
        ]);

        flash('success', 'Your Study Story is live — it will disappear in about 24 hours.');
        if ($wantsJson) {
            json_response(['success' => true, 'redirect' => $redirectTo]);
        }
        redirect($redirectTo);
    }

    public function recordView(string $id): void
    {
        verify_csrf();
        Story::purgeExpired();
        $sid = (int) $id;
        $story = Story::find($sid);
        if (!$story || !Story::canView($story, Auth::id())) {
            json_response(['error' => 'Not found'], 404);
        }
        $count = Story::incrementViewCount($sid);

        json_response(['success' => true, 'view_count' => $count]);
    }

    public function media(string $id): void
    {
        Story::purgeExpired();
        $story = Story::find((int) $id);
        if (!$story || !Story::canView($story, Auth::id())) {
            http_response_code(404);
            exit('Not found');
        }
        $path = base_path('uploads/stories/' . $story['filename']);
        if (!is_file($path)) {
            http_response_code(404);
            exit('Not found');
        }
        header('Content-Type: ' . $story['mime_type']);
        header('Cache-Control: private, max-age=3600');
        readfile($path);
        exit;
    }

    public function destroy(string $id): void
    {
        verify_csrf();
        if (!Story::deleteById((int) $id, Auth::id())) {
            if (is_ajax() || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
                json_response(['error' => 'Not found'], 404);
            }
            flash('error', 'Could not delete story.');
            $this->redirectBack();
        }
        if (is_ajax() || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
            json_response(['success' => true]);
        }
        flash('success', 'Story removed.');
        $this->redirectBack();
    }

    private function redirectBack(): never
    {
        redirect($this->safeRedirectPath());
    }

    private function wantsJson(): bool
    {
        return is_ajax() || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
    }

    private function safeRedirectPath(): string
    {
        $path = trim((string) ($_POST['_redirect'] ?? '/stories'));
        if (!str_starts_with($path, '/') || str_starts_with($path, '//')) {
            return '/stories';
        }

        return $path;
    }
}
