<?php

declare(strict_types=1);

/**
 * Startup health check — runs before bootstrap to catch the most common
 * hosting issues that would otherwise produce a blank HTTP 500.
 */
$startupErrors = [];

if (PHP_VERSION_ID < 80000) {
    $startupErrors[] = 'PHP 8.0 or newer is required (you have ' . PHP_VERSION . ').';
}

$requiredExts = ['pdo_sqlite', 'pdo', 'mbstring'];
foreach ($requiredExts as $ext) {
    if (!extension_loaded($ext)) {
        $startupErrors[] = 'Missing PHP extension: "' . $ext . '". Enable it in your hosting control panel or php.ini.';
    }
}

foreach (['database', 'uploads'] as $dir) {
    $path = __DIR__ . '/' . $dir;
    if (!is_dir($path)) {
        @mkdir($path, 0755, true);
    }
    if (!is_writable($path)) {
        $startupErrors[] = 'Directory "' . $dir . '/" is not writable. Set permissions to 755 (chmod 755 ' . $dir . '/).';
    }
}

if ($startupErrors) {
    http_response_code(500);
    ?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>StudyCircle — Setup Required</title>
<style>
  *{margin:0;padding:0;box-sizing:border-box}
  body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#0f0f1a;color:#e0e0e0;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:24px}
  .card{background:#1a1a2e;border:1px solid #2a2a4a;border-radius:16px;padding:40px;max-width:640px;width:100%;box-shadow:0 8px 32px rgba(0,0,0,.5)}
  h1{font-size:22px;margin-bottom:8px;color:#f0f0ff}
  p{color:#a0a0c0;font-size:14px;margin-bottom:20px;line-height:1.6}
  ul{list-style:none;padding:0}
  li{background:#2a1a2e;border-left:4px solid #f43f5e;padding:12px 16px;margin-bottom:10px;border-radius:0 8px 8px 0;font-size:14px;color:#f0c0c8}
  li.ok{border-left-color:#22c55e;background:#1a2a1e;color:#c0f0c8}
  hr{border:none;border-top:1px solid #2a2a4a;margin:20px 0}
  code{background:#0f0f1a;padding:2px 6px;border-radius:4px;font-size:13px;color:#c0c0e0}
</style>
</head>
<body>
<div class="card">
  <h1>StudyCircle — Setup Required</h1>
  <p>Verify these items, then refresh the page.</p>
  <ul>
    <?php foreach ($startupErrors as $err): ?>
      <li><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></li>
    <?php endforeach; ?>
    <?php if (PHP_VERSION_ID >= 80000): ?>
      <li class="ok">PHP <?= PHP_VERSION ?> ✓</li>
    <?php endif; ?>
    <?php if (extension_loaded('pdo_sqlite')): ?>
      <li class="ok">SQLite (PDO) ✓</li>
    <?php endif; ?>
  </ul>
  <hr>
  <p style="margin-bottom:0;font-size:13px;color:#707090">Need help? Check <code>README.md</code> or contact support.</p>
</div>
</body>
</html>
    <?php
    exit;
}

/**
 * Fatal-error catch — turns blank 500s into a readable page.
 */
register_shutdown_function(function (): void {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        http_response_code(500);
        $msg = htmlspecialchars($err['message'], ENT_QUOTES, 'UTF-8');
        $file = htmlspecialchars($err['file'], ENT_QUOTES, 'UTF-8');
        $line = (int) $err['line'];
        ?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>StudyCircle — Server Error</title>
<style>
  *{margin:0;padding:0;box-sizing:border-box}
  body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#0f0f1a;color:#e0e0e0;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:24px}
  .card{background:#1a1a2e;border:1px solid #2a2a4a;border-radius:16px;padding:40px;max-width:640px;width:100%;box-shadow:0 8px 32px rgba(0,0,0,.5)}
  h1{font-size:22px;margin-bottom:8px;color:#f0f0ff}
  p{color:#a0a0c0;font-size:14px;margin-bottom:8px;line-height:1.6}
  .err{background:#2a1a2e;border-left:4px solid #f43f5e;padding:16px;border-radius:0 8px 8px 0;font-family:monospace;font-size:13px;color:#f0c0c8;word-break:break-word;margin-top:16px}
  .meta{color:#707090;font-size:12px;margin-top:8px}
</style>
</head>
<body>
<div class="card">
  <h1>StudyCircle — Server Error</h1>
  <p>The application encountered an internal error. Enable <code>debug</code> in <code>app/config.php</code> for details, or check your server error log.</p>
  <div class="err"><?= $msg ?></div>
  <div class="meta"><?= $file ?> : <?= $line ?></div>
</div>
</body>
</html>
        <?php
    }
});

require __DIR__ . '/app/bootstrap.php';

use App\Auth;
use App\Router;
use App\Controllers\AgileController;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\GroupController;
use App\Controllers\TaskController;
use App\Controllers\FocusController;
use App\Controllers\ChatController;
use App\Controllers\ResourceController;
use App\Controllers\ProfileController;
use App\Controllers\NotificationController;
use App\Controllers\AdminController;
use App\Controllers\StoryController;

$router = new Router();
$auth = fn() => Auth::requireAuth();
$admin = fn() => Auth::requireAdmin();
$guest = fn() => Auth::guestOnly();

// Public
$router->get('/', fn() => Auth::check() ? redirect('/dashboard') : redirect('/login'));
$router->get('/login', [AuthController::class, 'loginForm'], [$guest]);
$router->post('/login', [AuthController::class, 'login'], [$guest]);
$router->post('/login/demo-quick', [AuthController::class, 'demoQuickLogin'], [$guest]);
$router->get('/register', [AuthController::class, 'registerForm'], [$guest]);
$router->post('/register', [AuthController::class, 'register'], [$guest]);
$router->get('/forgot-password', [AuthController::class, 'forgotForm'], [$guest]);
$router->post('/forgot-password', [AuthController::class, 'forgot'], [$guest]);
$router->get('/reset/{token}', [AuthController::class, 'resetForm'], [$guest]);
$router->post('/reset/{token}', [AuthController::class, 'reset'], [$guest]);
$router->get('/logout', [AuthController::class, 'logout']);

// Dashboard
$router->get('/dashboard', [DashboardController::class, 'index'], [$auth]);
$router->get('/api/search', [DashboardController::class, 'search'], [$auth]);

// Groups
$router->get('/groups', [GroupController::class, 'index'], [$auth]);
$router->get('/groups/create', [GroupController::class, 'createForm'], [$auth]);
$router->post('/groups', [GroupController::class, 'create'], [$auth]);
$router->post('/groups/join', [GroupController::class, 'join'], [$auth]);
$router->get('/groups/{id}', [GroupController::class, 'show'], [$auth]);
$router->get('/groups/{id}/edit', [GroupController::class, 'edit'], [$auth]);
$router->post('/groups/{id}', [GroupController::class, 'update'], [$auth]);
$router->post('/groups/{id}/delete', [GroupController::class, 'delete'], [$auth]);
$router->post('/groups/{id}/members/remove', [GroupController::class, 'removeMember'], [$auth]);

// Tasks
$router->get('/groups/{groupId}/tasks', [TaskController::class, 'board'], [$auth]);
$router->post('/groups/{groupId}/tasks', [TaskController::class, 'store'], [$auth]);
$router->post('/tasks/{id}', [TaskController::class, 'update'], [$auth]);
$router->post('/tasks/{id}/delete', [TaskController::class, 'delete'], [$auth]);
$router->post('/groups/{groupId}/tasks/reorder', [TaskController::class, 'reorder'], [$auth]);
$router->get('/tasks/{id}/comments', [TaskController::class, 'comments'], [$auth]);
$router->post('/tasks/{id}/comments', [TaskController::class, 'addComment'], [$auth]);

// Agile / Scrum workspace
$router->get('/groups/{groupId}/agile', [AgileController::class, 'index'], [$auth]);
$router->get('/groups/{groupId}/agile/burndown', [AgileController::class, 'burndownData'], [$auth]);
$router->post('/groups/{groupId}/sprints', [AgileController::class, 'sprintStore'], [$auth]);
$router->post('/sprints/{id}/activate', [AgileController::class, 'sprintActivate'], [$auth]);
$router->post('/sprints/{id}', [AgileController::class, 'sprintUpdate'], [$auth]);
$router->post('/groups/{groupId}/use-cases', [AgileController::class, 'useCaseStore'], [$auth]);
$router->post('/groups/{groupId}/requirements', [AgileController::class, 'requirementStore'], [$auth]);
$router->post('/requirements/{id}', [AgileController::class, 'requirementUpdate'], [$auth]);
$router->post('/groups/{groupId}/members/scrum-role', [AgileController::class, 'memberScrumRole'], [$auth]);

// Stories (24h ephemeral, public or group)
$router->get('/stories', [StoryController::class, 'hub'], [$auth]);
$router->post('/stories', [StoryController::class, 'store'], [$auth]);
$router->get('/stories/{id}/media', [StoryController::class, 'media'], [$auth]);
$router->post('/stories/{id}/view', [StoryController::class, 'recordView'], [$auth]);
$router->post('/stories/{id}/delete', [StoryController::class, 'destroy'], [$auth]);

// Focus
$router->get('/focus', [FocusController::class, 'index'], [$auth]);
$router->post('/focus/complete', [FocusController::class, 'complete'], [$auth]);
$router->get('/focus/groups/{groupId}/tasks', [FocusController::class, 'groupTasks'], [$auth]);
$router->get('/api/focus/ambient', [FocusController::class, 'ambientSettings'], [$auth]);
$router->post('/api/focus/ambient', [FocusController::class, 'saveAmbient'], [$auth]);
$router->get('/api/focus/tracks', [FocusController::class, 'listTracks'], [$auth]);
$router->post('/api/focus/tracks', [FocusController::class, 'storeTrack'], [$auth]);
$router->post('/api/focus/tracks/{id}/delete', [FocusController::class, 'deleteTrack'], [$auth]);

// Chat
$router->get('/groups/{groupId}/chat', [ChatController::class, 'index'], [$auth]);
$router->get('/groups/{groupId}/chat/messages', [ChatController::class, 'fetch'], [$auth]);
$router->post('/groups/{groupId}/chat', [ChatController::class, 'send'], [$auth]);

// Resources
$router->get('/groups/{groupId}/resources', [ResourceController::class, 'index'], [$auth]);
$router->post('/groups/{groupId}/resources', [ResourceController::class, 'upload'], [$auth]);
$router->get('/resources/{id}/download', [ResourceController::class, 'download'], [$auth]);
$router->post('/resources/{id}/delete', [ResourceController::class, 'delete'], [$auth]);

// Profile — public slug (guests OK); /profile/edit before /profile/{id}
$router->get('/p/{slug}', [ProfileController::class, 'publicShow'], []);
$router->get('/profile/edit', [ProfileController::class, 'edit'], [$auth]);
$router->post('/profile', [ProfileController::class, 'update'], [$auth]);
$router->get('/profile/{id}', [ProfileController::class, 'show'], [$auth]);
$router->get('/profile', [ProfileController::class, 'show'], [$auth]);
$router->get('/leaderboard', [ProfileController::class, 'leaderboard'], [$auth]);

// Notifications
$router->get('/api/notifications', [NotificationController::class, 'index'], [$auth]);
$router->get('/api/notifications/unread', [NotificationController::class, 'unread'], [$auth]);
$router->post('/api/notifications/{id}/read', [NotificationController::class, 'markRead'], [$auth]);
$router->post('/api/notifications/read-all', [NotificationController::class, 'markAllRead'], [$auth]);

// Admin
$router->get('/admin', [AdminController::class, 'index'], [$admin]);
$router->get('/admin/users', [AdminController::class, 'users'], [$admin]);
$router->get('/admin/users/create', [AdminController::class, 'userCreate'], [$admin]);
$router->post('/admin/users/create', [AdminController::class, 'userStore'], [$admin]);
$router->get('/admin/users/{id}/edit', [AdminController::class, 'userEdit'], [$admin]);
$router->post('/admin/users/{id}', [AdminController::class, 'userUpdate'], [$admin]);
$router->post('/admin/users/{id}/delete', [AdminController::class, 'userDelete'], [$admin]);
$router->get('/admin/groups', [AdminController::class, 'groups'], [$admin]);
$router->post('/admin/groups/{id}/delete', [AdminController::class, 'groupDelete'], [$admin]);
$router->get('/admin/activity', [AdminController::class, 'activity'], [$admin]);

$router->dispatch(request_method(), request_uri());

