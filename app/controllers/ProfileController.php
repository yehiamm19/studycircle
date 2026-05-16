<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Models\Achievement;
use App\Models\FocusSession;
use App\Models\Task;
use App\Models\User;
use App\Services\GamificationService;
use App\Utils\Uploader;
use App\Utils\Validator;


class ProfileController
{
    public function publicShow(string $slug): void
    {
        $user = User::findByPublicProfileSlug($slug);
        if (!$user) {
            http_response_code(404);
            view('errors/404', ['title' => 'Profile Not Found', 'layout' => false]);

            return;
        }
        unset($user['email'], $user['password'], $user['reset_token'], $user['reset_expires'], $user['role']);

        $profileId = (int) $user['id'];
        User::ensurePublicProfileSlug($profileId);

        view('profile/public', [
            'title' => $user['name'],
            'profile' => $user,
            'isOwn' => Auth::check() && Auth::id() === $profileId,
            'campusRank' => User::xpRank($profileId),
            'taskStats' => Task::statsForUser($profileId),
            'focusStats' => FocusSession::stats($profileId),
            'achievements' => Achievement::forUser($profileId),
            'allAchievements' => Achievement::allWithProgress($profileId),
            'totalCampusUsers' => User::totalCount(),
            'layout' => 'layouts/profile_public',
        ]);
    }

    public function show(?string $id = null): void
    {
        $profileId = ($id !== null && $id !== '') ? (int) $id : Auth::id();
        if ($profileId === null || $profileId <= 0) {
            flash('error', 'User not found.');
            redirect('/dashboard');
        }

        $user = User::find((int) $profileId);
        if (!$user) {
            flash('error', 'User not found.');
            redirect('/dashboard');
        }
        $slug = User::ensurePublicProfileSlug($profileId);

        view('profile/show', [
            'title' => $user['name'],
            'profile' => $user,
            'isOwn' => $profileId === Auth::id(),
            'campusRank' => User::xpRank($profileId),
            'taskStats' => Task::statsForUser($profileId),
            'focusStats' => FocusSession::stats($profileId),
            'achievements' => Achievement::forUser($profileId),
            'allAchievements' => Achievement::allWithProgress($profileId),
            'stats' => GamificationService::userStats($profileId),
            'activity' => $this->getActivity($profileId),
            'shareProfileHref' => absolute_site_href(url('/p/' . $slug)),
            'shareLogoUrl' => absolute_site_href(logo_url()),
            'totalCampusUsers' => User::totalCount(),
        ]);
    }

    public function edit(): void
    {
        $uid = Auth::id();
        if ($uid === null || $uid <= 0) {
            redirect('/login');
        }

        $user = User::find((int) $uid);
        if (!$user) {
            flash('error', 'Account not found.');
            redirect('/login');
        }

        view('profile/edit', ['title' => 'Edit Profile', 'user' => $user]);
    }

    public function update(): void
    {
        verify_csrf();
        $v = new Validator();
        if (!$v->validate($_POST, ['name' => 'required|min:2|max:100'])) {
            flash('error', $v->first());
            redirect('/profile/edit');
        }
        $data = ['name' => $_POST['name'], 'bio' => $_POST['bio'] ?? ''];

        if (!empty($_FILES['avatar']['name'])) {
            $result = Uploader::upload($_FILES['avatar'], base_path('uploads/avatars'), config('avatar_allowed'), config('upload_max_size'));
            if (isset($result['error'])) {
                flash('error', $result['error']);
                redirect('/profile/edit');
            }
            $old = Auth::user()['avatar'] ?? null;
            if ($old) Uploader::delete(base_path('uploads/avatars'), $old);
            $data['avatar'] = $result['filename'];
        }

        User::update(Auth::id(), $data);
        $fresh = User::find((int) Auth::id());
        if ($fresh) {
            Auth::login($fresh);
        }
        flash('success', 'Profile updated.');
        redirect('/profile');
    }

    public function leaderboard(): void
    {
        view('profile/leaderboard', [
            'title' => 'Leaderboard',
            'users' => User::leaderboard(50),
        ]);
    }

    private function getActivity(int $userId): array
    {
        $stmt = db()->prepare('SELECT * FROM activity_log WHERE user_id = ? ORDER BY created_at DESC LIMIT 15');
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
}

