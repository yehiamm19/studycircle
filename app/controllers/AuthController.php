<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Models\User;
use App\Utils\Validator;

class AuthController
{
    /** Matches `database/seed.php` demo passwords for quick-login verification. */
    private const DEMO_QUICK_PASSWORD = 'password123';

    /** demo_role form value => seeded demo email */
    private const DEMO_QUICK_EMAILS = [
        'admin' => 'alex@studycircle.app',
        'student' => 'jordan@studycircle.app',
    ];

    public function loginForm(): void
    {
        view('auth/login', ['title' => 'Sign In', 'layout' => 'layouts/guest']);
    }

    public function login(): void
    {
        verify_csrf();
        $v = new Validator();
        if (!$v->validate($_POST, ['email' => 'required|email', 'password' => 'required'])) {
            set_old($_POST);
            flash('error', $v->first());
            redirect('/login');
        }

        $user = User::findByEmail($_POST['email']);
        if (!$user || !password_verify($_POST['password'], $user['password'])) {
            set_old($_POST);
            flash('error', 'Invalid email or password.');
            redirect('/login');
        }

        Auth::login(User::find((int) $user['id']));
        flash('success', 'Welcome back, ' . $user['name'] . '!');
        redirect('/dashboard');
    }

    public function demoQuickLogin(): void
    {
        verify_csrf();
        if (!config('demo_quick_login', false)) {
            flash('error', 'Demo quick sign-in is disabled.');
            redirect('/login');
        }

        $returnPage = strtolower(trim((string) ($_POST['_demo_return'] ?? 'login')));
        $failRedirect = in_array($returnPage, ['register'], true) ? '/register' : '/login';

        $role = strtolower(trim((string) ($_POST['demo_role'] ?? '')));
        $email = self::DEMO_QUICK_EMAILS[$role] ?? null;
        if ($email === null) {
            flash('error', 'Invalid demo choice.');
            redirect($failRedirect);
        }

        $user = User::findByEmail($email);
        if (!$user || !password_verify(self::DEMO_QUICK_PASSWORD, $user['password'])) {
            flash('error', 'Demo accounts are not available. Run the database seed first.');
            redirect($failRedirect);
        }

        Auth::login(User::find((int) $user['id']));
        flash('success', 'Signed in as demo — ' . ($user['name'] ?? 'User'));

        $target = ($user['role'] ?? '') === 'admin' ? '/admin' : '/dashboard';
        redirect($target);
    }

    public function registerForm(): void
    {
        view('auth/register', ['title' => 'Create Account', 'layout' => 'layouts/guest']);
    }

    public function register(): void
    {
        verify_csrf();
        $v = new Validator();
        if (!$v->validate($_POST, [
            'name' => 'required|min:2|max:100',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ])) {
            set_old($_POST);
            flash('error', $v->first());
            redirect('/register');
        }

        if (User::findByEmail($_POST['email'])) {
            set_old($_POST);
            flash('error', 'An account with this email already exists.');
            redirect('/register');
        }

        $id = User::create($_POST);
        Auth::login(User::find($id));
        flash('success', 'Welcome to StudyCircle!');
        redirect('/dashboard');
    }

    public function forgotForm(): void
    {
        view('auth/forgot', ['title' => 'Reset Password', 'layout' => 'layouts/guest']);
    }

    public function forgot(): void
    {
        verify_csrf();
        $email = $_POST['email'] ?? '';
        $user = User::findByEmail($email);
        if ($user) {
            $token = bin2hex(random_bytes(32));
            User::setResetToken((int) $user['id'], $token);
            $_SESSION['reset_link'] = url("/reset/{$token}");
        }
        flash('success', 'If that email exists, we sent reset instructions.');
        redirect('/forgot-password');
    }

    public function resetForm(string $token): void
    {
        if (!User::findByResetToken($token)) {
            flash('error', 'Invalid or expired reset link.');
            redirect('/login');
        }
        view('auth/reset', ['title' => 'New Password', 'token' => $token, 'layout' => 'layouts/guest']);
    }

    public function reset(string $token): void
    {
        verify_csrf();
        $user = User::findByResetToken($token);
        if (!$user) {
            flash('error', 'Invalid or expired reset link.');
            redirect('/login');
        }
        $v = new Validator();
        if (!$v->validate($_POST, ['password' => 'required|min:8|confirmed'])) {
            flash('error', $v->first());
            redirect("/reset/{$token}");
        }
        User::updatePassword((int) $user['id'], $_POST['password']);
        flash('success', 'Password updated. You can sign in now.');
        redirect('/login');
    }

    public function logout(): void
    {
        Auth::logout();
        redirect('/login');
    }
}

