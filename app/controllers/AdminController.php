<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Models\User;
use App\Services\AdminService;

class AdminController
{
    public function index(): void
    {
        view('admin/dashboard', [
            'title' => 'Admin Overview',
            'layout' => 'layouts/admin',
            'stats' => AdminService::overview(),
            'recentUsers' => AdminService::recentUsers(),
            'recentGroups' => AdminService::recentGroups(),
            'recentActivity' => AdminService::recentActivity(),
            'signups' => AdminService::signupsByDay(),
        ]);
    }

    public function users(): void
    {
        $search = trim($_GET['q'] ?? '');
        view('admin/users', [
            'title' => 'Users',
            'layout' => 'layouts/admin',
            'users' => User::allForAdmin($search),
            'search' => $search,
        ]);
    }

    public function userEdit(string $id): void
    {
        $user = User::find((int) $id);
        if (!$user) {
            flash('error', 'User not found.');
            redirect('/admin/users');
        }
        view('admin/user-edit', [
            'title' => 'Edit User',
            'layout' => 'layouts/admin',
            'editUser' => $user,
        ]);
    }

    public function userUpdate(string $id): void
    {
        verify_csrf();
        $userId = (int) $id;
        $user = User::find($userId);
        if (!$user) {
            flash('error', 'User not found.');
            redirect('/admin/users');
        }

        $role = $_POST['role'] ?? 'student';
        if (!in_array($role, ['student', 'admin'], true)) {
            $role = 'student';
        }

        $email = trim($_POST['email'] ?? '');
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Valid email is required.');
            redirect('/admin/users/' . $userId . '/edit');
        }

        $existing = User::findByEmail($email);
        if ($existing && (int) $existing['id'] !== $userId) {
            flash('error', 'Email already in use.');
            redirect('/admin/users/' . $userId . '/edit');
        }

        User::updateAdmin($userId, [
            'name' => trim($_POST['name'] ?? $user['name']),
            'email' => $email,
            'role' => $role,
            'bio' => trim($_POST['bio'] ?? ''),
            'xp' => max(0, (int) ($_POST['xp'] ?? 0)),
            'streak' => max(0, (int) ($_POST['streak'] ?? 0)),
        ]);

        $password = $_POST['password'] ?? '';
        if ($password !== '') {
            if (strlen($password) < 8) {
                flash('error', 'Password must be at least 8 characters.');
                redirect('/admin/users/' . $userId . '/edit');
            }
            User::updatePassword($userId, $password);
        }

        if ($userId === Auth::id()) {
            $_SESSION['user'] = User::refreshSession($userId);
        }

        flash('success', 'User updated successfully.');
        redirect('/admin/users');
    }

    public function userCreate(): void
    {
        view('admin/user-create', [
            'title' => 'Create User',
            'layout' => 'layouts/admin',
        ]);
    }

    public function userStore(): void
    {
        verify_csrf();

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'student';

        if ($name === '') {
            flash('error', 'Name is required.');
            redirect('/admin/users/create');
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Valid email is required.');
            redirect('/admin/users/create');
        }

        if (strlen($password) < 8) {
            flash('error', 'Password must be at least 8 characters.');
            redirect('/admin/users/create');
        }

        if (!in_array($role, ['student', 'admin'], true)) {
            $role = 'student';
        }

        $existing = User::findByEmail($email);
        if ($existing) {
            flash('error', 'Email already in use.');
            redirect('/admin/users/create');
        }

        $id = User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ]);

        if ($role === 'admin') {
            db()->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$role, $id]);
        }

        flash('success', 'User created successfully.');
        redirect('/admin/users');
    }

    public function userDelete(string $id): void
    {
        verify_csrf();
        $userId = (int) $id;
        if ($userId === Auth::id()) {
            flash('error', 'You cannot delete your own account.');
            redirect('/admin/users');
        }
        if (!User::delete($userId)) {
            flash('error', 'Could not delete user.');
            redirect('/admin/users');
        }
        flash('success', 'User deleted.');
        redirect('/admin/users');
    }

    public function groups(): void
    {
        $search = trim($_GET['q'] ?? '');
        view('admin/groups', [
            'title' => 'Groups',
            'layout' => 'layouts/admin',
            'groups' => AdminService::allGroups($search),
            'search' => $search,
        ]);
    }

    public function groupDelete(string $id): void
    {
        verify_csrf();
        $stmt = db()->prepare('DELETE FROM groups WHERE id = ?');
        $stmt->execute([(int) $id]);
        flash('success', 'Group deleted.');
        redirect('/admin/groups');
    }

    public function activity(): void
    {
        $stmt = db()->prepare('
            SELECT a.*, u.name AS user_name, u.email AS user_email
            FROM activity_log a
            LEFT JOIN users u ON u.id = a.user_id
            ORDER BY a.created_at DESC LIMIT 100
        ');
        $stmt->execute();
        view('admin/activity', [
            'title' => 'Activity Log',
            'layout' => 'layouts/admin',
            'activities' => $stmt->fetchAll(),
        ]);
    }
}
