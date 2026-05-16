<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Models\Group;
use App\Models\Story;
use App\Models\Task;
use App\Services\GamificationService;
use App\Services\NotificationService;
use App\Utils\Validator;

class GroupController
{
    public function index(): void
    {
        view('groups/index', [
            'title' => 'Study Groups',
            'groups' => Group::forUser(Auth::id()),
        ]);
    }

    public function show(string $id): void
    {
        $group = $this->authorize((int) $id);
        $storyGroups = array_map(
            static fn (array $g): array => ['id' => $g['id'], 'name' => $g['name']],
            Group::forUser(Auth::id())
        );
        $gid = (int) $id;
        $studyStoryPayload = [
            'buckets' => Story::serializeBuckets(Story::railBucketsForGroup($gid)),
            'viewerUserId' => Auth::id(),
            'composerRedirect' => '/groups/' . $gid,
            'userGroups' => $storyGroups,
            'defaultGroupId' => $gid,
            'variant' => 'group',
        ];

        view('groups/show', [
            'title' => $group['name'],
            'group' => $group,
            'members' => Group::members((int) $id),
            'tasks' => Task::forGroup((int) $id),
            'studyStoryPayload' => $studyStoryPayload,
        ]);
    }

    public function createForm(): void
    {
        view('groups/create', ['title' => 'Create Group']);
    }

    public function create(): void
    {
        verify_csrf();
        $v = new Validator();
        if (!$v->validate($_POST, ['name' => 'required|min:2|max:80'])) {
            flash('error', $v->first());
            redirect('/groups/create');
        }
        $id = Group::create([
            'name' => $_POST['name'],
            'description' => $_POST['description'] ?? '',
            'color' => $_POST['color'] ?? 'indigo',
            'icon' => $_POST['icon'] ?? 'book-open',
            'owner_id' => Auth::id(),
        ]);
        GamificationService::addXp(Auth::id(), 25);
        GamificationService::logActivity(Auth::id(), 'group_created', ['group_id' => $id]);
        flash('success', 'Study group created!');
        redirect("/groups/{$id}");
    }

    public function edit(string $id): void
    {
        $group = $this->authorize((int) $id);
        if ((int) $group['owner_id'] !== Auth::id()) {
            flash('error', 'Only the owner can edit this group.');
            redirect("/groups/{$id}");
        }
        view('groups/edit', ['title' => 'Edit Group', 'group' => $group]);
    }

    public function update(string $id): void
    {
        verify_csrf();
        $group = $this->authorize((int) $id);
        if ((int) $group['owner_id'] !== Auth::id()) {
            flash('error', 'Permission denied.');
            redirect("/groups/{$id}");
        }
        Group::update((int) $id, $_POST);
        flash('success', 'Group updated.');
        redirect("/groups/{$id}");
    }

    public function delete(string $id): void
    {
        verify_csrf();
        $group = $this->authorize((int) $id);
        if ((int) $group['owner_id'] !== Auth::id()) {
            json_response(['error' => 'Permission denied'], 403);
        }
        Group::delete((int) $id);
        flash('success', 'Group deleted.');
        redirect('/groups');
    }

    public function join(): void
    {
        verify_csrf();
        $code = trim($_POST['invite_code'] ?? '');
        $group = Group::findByInvite($code);
        if (!$group) {
            flash('error', 'Invalid invite code.');
            redirect('/groups');
        }
        if (Group::isMember((int) $group['id'], Auth::id())) {
            flash('info', 'You are already a member.');
            redirect("/groups/{$group['id']}");
        }
        Group::addMember((int) $group['id'], Auth::id());
        NotificationService::create((int) $group['owner_id'], 'member', 'New member joined', Auth::user()['name'] . ' joined ' . $group['name'], "/groups/{$group['id']}");
        flash('success', 'Joined ' . $group['name'] . '!');
        redirect("/groups/{$group['id']}");
    }

    public function removeMember(string $id): void
    {
        verify_csrf();
        $group = $this->authorize((int) $id);
        $memberId = (int) ($_POST['user_id'] ?? 0);
        if ((int) $group['owner_id'] !== Auth::id() && $memberId !== Auth::id()) {
            json_response(['error' => 'Permission denied'], 403);
        }
        Group::removeMember((int) $id, $memberId);
        json_response(['success' => true]);
    }

    private function authorize(int $id): array
    {
        $group = Group::find($id);
        if (!$group || !Group::isMember($id, Auth::id())) {
            flash('error', 'Group not found.');
            redirect('/groups');
        }
        return $group;
    }
}

