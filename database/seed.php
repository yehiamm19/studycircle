<?php

declare(strict_types=1);

$pdo = db();

$count = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
if ($count > 0) {
    return;
}

$password = password_hash('password123', PASSWORD_DEFAULT);

$pdo->prepare('INSERT INTO users (name, email, password, role, bio, xp, streak) VALUES (?,?,?,?,?,?,?)')
    ->execute(['Alex Morgan', 'alex@studycircle.app', $password, 'admin', 'Computer Science major. Always learning.', 1250, 7]);
$pdo->prepare('INSERT INTO users (name, email, password, role, bio, xp, streak) VALUES (?,?,?,?,?,?,?)')
    ->execute(['Jordan Lee', 'jordan@studycircle.app', $password, 'student', 'Biology enthusiast & study group leader.', 890, 5]);
$pdo->prepare('INSERT INTO users (name, email, password, role, bio, xp, streak) VALUES (?,?,?,?,?,?,?)')
    ->execute(['Sam Rivera', 'sam@studycircle.app', $password, 'student', 'Math tutor. Pomodoro addict.', 720, 3]);
$pdo->prepare('INSERT INTO users (name, email, password, role, bio, xp, streak) VALUES (?,?,?,?,?,?,?)')
    ->execute(['Taylor Kim', 'taylor@studycircle.app', $password, 'student', 'Design student. Night owl.', 540, 2]);

$achievements = [
    ['first_task', 'First Step', 'Complete your first task', 'check-circle', 25, 'tasks_completed', 1],
    ['task_master', 'Task Master', 'Complete 10 tasks', 'trophy', 100, 'tasks_completed', 10],
    ['focus_starter', 'Focus Starter', 'Complete 5 focus sessions', 'timer', 50, 'focus_sessions', 5],
    ['focus_champion', 'Focus Champion', 'Complete 25 focus sessions', 'zap', 150, 'focus_sessions', 25],
    ['streak_3', 'On Fire', 'Maintain a 3-day streak', 'flame', 75, 'streak', 3],
    ['streak_7', 'Unstoppable', 'Maintain a 7-day streak', 'flame', 200, 'streak', 7],
    ['social_butterfly', 'Social Butterfly', 'Send 50 chat messages', 'message-circle', 75, 'messages_sent', 50],
    ['group_creator', 'Trailblazer', 'Create your first study group', 'users', 50, 'groups_created', 1],
    ['resource_sharer', 'Knowledge Keeper', 'Upload 5 resources', 'folder-open', 75, 'resources_uploaded', 5],
    ['xp_1000', 'Rising Star', 'Earn 1000 XP', 'star', 100, 'xp', 1000],
];

$stmt = $pdo->prepare('INSERT INTO achievements (slug, name, description, icon, xp_reward, requirement_type, requirement_value) VALUES (?,?,?,?,?,?,?)');
foreach ($achievements as $a) {
    $stmt->execute($a);
}

$invite1 = bin2hex(random_bytes(4));
$invite2 = bin2hex(random_bytes(4));

$pdo->prepare('INSERT INTO groups (name, description, color, icon, owner_id, invite_code) VALUES (?,?,?,?,?,?)')
    ->execute(['CS 301 Study Squad', 'Algorithms, data structures, and weekly problem sets.', 'indigo', 'code', 1, $invite1]);
$pdo->prepare('INSERT INTO groups (name, description, color, icon, owner_id, invite_code) VALUES (?,?,?,?,?,?)')
    ->execute(['Bio Lab Partners', 'Lab reports, exam prep, and lecture notes.', 'emerald', 'flask-conical', 2, $invite2]);

foreach ([[1, 1, 'owner'], [1, 2, 'member'], [1, 3, 'member'], [1, 4, 'member'], [2, 2, 'owner'], [2, 1, 'member'], [2, 3, 'member']] as $m) {
    $pdo->prepare('INSERT INTO group_members (group_id, user_id, role) VALUES (?,?,?)')->execute($m);
}

$tasks = [
    [1, 'Review binary trees', 'Chapter 12 + practice problems', 'todo', 'high', 'exam', 0, 2],
    [1, 'Implement Dijkstra', 'Assignment due Friday', 'in_progress', 'high', 'homework', 1, 3],
    [1, 'Read CLRS Chapter 15', 'Dynamic programming intro', 'todo', 'medium', 'reading', 2, null],
    [1, 'Practice midterm problems', '2019-2023 past exams', 'completed', 'high', 'exam', 3, 1],
    [1, 'Group presentation slides', 'Final project deck', 'in_progress', 'medium', 'project', 4, 4],
    [2, 'Lab report draft', 'Experiment 4 analysis', 'todo', 'high', 'homework', 0, 2],
    [2, 'Flashcard set - cell biology', '50 key terms', 'in_progress', 'medium', 'reading', 1, 3],
    [2, 'Study guide compilation', 'Combine all lecture notes', 'completed', 'low', 'project', 2, 1],
];

$taskStmt = $pdo->prepare('INSERT INTO tasks (group_id, title, description, status, priority, label, position, assignee_id, created_by) VALUES (?,?,?,?,?,?,?,?,?)');
foreach ($tasks as $t) {
    $taskStmt->execute([$t[0], $t[1], $t[2], $t[3], $t[4], $t[5], $t[6], $t[7], 1]);
}

$messages = [
    [1, 2, 'Hey team — meeting at 3pm in the library?'],
    [1, 3, 'Works for me. I\'ll bring the practice sheets.'],
    [1, 1, 'Perfect. Let\'s review the graph algorithms section.'],
    [1, 4, 'Can someone share yesterday\'s notes?'],
    [2, 1, 'Uploaded the lab guidelines to resources.'],
    [2, 3, 'Thanks! The microscopy section is tricky.'],
];
$msgStmt = $pdo->prepare('INSERT INTO messages (group_id, user_id, body) VALUES (?,?,?)');
foreach ($messages as $m) {
    $msgStmt->execute($m);
}

$pdo->prepare('INSERT INTO focus_sessions (user_id, group_id, duration_minutes, completed, started_at, ended_at) VALUES (1,1,25,1,datetime("now","-2 days"),datetime("now","-2 days","+25 minutes"))')->execute();
$pdo->prepare('INSERT INTO focus_sessions (user_id, group_id, duration_minutes, completed, started_at, ended_at) VALUES (2,1,25,1,datetime("now","-1 day"),datetime("now","-1 day","+25 minutes"))')->execute();
$pdo->prepare('INSERT INTO focus_sessions (user_id, group_id, duration_minutes, completed, started_at, ended_at) VALUES (3,2,50,1,datetime("now","-3 hours"),datetime("now","-2 hours"))')->execute();

$pdo->prepare('INSERT INTO notifications (user_id, type, title, body, link) VALUES (2,"invite","New group invite","Alex invited you to CS 301 Study Squad","/groups/1")')->execute();
$pdo->prepare('INSERT INTO notifications (user_id, type, title, body, link) VALUES (3,"task","Task assigned","You were assigned: Implement Dijkstra","/groups/1/tasks")')->execute();

$pdo->prepare('INSERT INTO user_achievements (user_id, achievement_id) VALUES (1,1),(1,3),(2,1),(3,4)')->execute();

