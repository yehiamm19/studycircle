<?php
$s = $stats;
$signupCounts = array_column($signups, 'count');
$totalSignupsWindow = array_sum(array_map('intval', $signupCounts));
$maxSignup = $signupCounts ? max(1, ...array_map('intval', $signupCounts)) : 1;
$signupBarMaxPx = 120;
?>
<section class="mb-8">
    <div class="relative overflow-hidden rounded-3xl admin-hero p-6 sm:p-8 text-white shadow-xl shadow-rose-500/15">
        <div class="absolute top-0 right-0 w-72 h-72 bg-white/10 rounded-full blur-3xl -translate-y-1/2 translate-x-1/3"></div>
        <div class="absolute bottom-0 left-0 w-48 h-48 bg-rose-400/20 rounded-full blur-2xl translate-y-1/2 -translate-x-1/4"></div>
        <div class="relative">
            <p class="text-rose-200 text-sm font-medium mb-1 flex items-center gap-2">
                <i data-lucide="shield" class="w-4 h-4"></i> Administration
            </p>
            <h1 class="text-2xl sm:text-3xl font-bold tracking-tight mb-2">Platform Overview</h1>
            <p class="text-rose-100/90 text-sm max-w-xl">Monitor users, study groups, focus sessions, and platform activity in real time.</p>
            <div class="flex flex-wrap gap-3 mt-6">
                <a href="<?= url('/admin/users') ?>" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white/20 hover:bg-white/30 text-sm font-semibold backdrop-blur transition-colors">
                    <i data-lucide="users" class="w-4 h-4"></i> Manage Users
                </a>
                <a href="<?= url('/admin/groups') ?>" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white text-rose-600 text-sm font-semibold hover:bg-rose-50 transition-colors">
                    <i data-lucide="folder-kanban" class="w-4 h-4"></i> All Groups
                </a>
            </div>
        </div>
    </div>
</section>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <?php foreach ([
        ['users', 'Total Users', $s['users'], 'indigo'],
        ['users-round', 'Students', $s['students'], 'sky'],
        ['shield', 'Admins', $s['admins'], 'rose'],
        ['folder-kanban', 'Groups', $s['groups'], 'violet'],
    ] as [$icon, $label, $val, $color]): ?>
    <div class="stat-card">
        <div class="flex items-center gap-3 mb-2">
            <span class="w-10 h-10 rounded-xl bg-<?= $color ?>-500/10 flex items-center justify-center">
                <i data-lucide="<?= $icon ?>" class="w-5 h-5 text-<?= $color ?>-500"></i>
            </span>
            <span class="text-xs font-medium text-slate-500 dark:text-slate-400"><?= $label ?></span>
        </div>
        <p class="text-2xl font-bold"><?= number_format((int) $val) ?></p>
    </div>
    <?php endforeach; ?>
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <?php foreach ([
        ['check-square', 'Tasks', $s['tasks'], 'emerald'],
        ['check-circle', 'Completed', $s['tasks_done'], 'teal'],
        ['timer', 'Focus Sessions', $s['focus_sessions'], 'amber'],
        ['clock', 'Focus Hours', round($s['focus_minutes'] / 60, 1), 'orange'],
    ] as [$icon, $label, $val, $color]): ?>
    <div class="stat-card">
        <div class="flex items-center gap-3 mb-2">
            <span class="w-10 h-10 rounded-xl bg-<?= $color ?>-500/10 flex items-center justify-center">
                <i data-lucide="<?= $icon ?>" class="w-5 h-5 text-<?= $color ?>-500"></i>
            </span>
            <span class="text-xs font-medium text-slate-500 dark:text-slate-400"><?= $label ?></span>
        </div>
        <p class="text-2xl font-bold"><?= is_numeric($val) && !is_float($val) ? number_format((int) $val) : $val ?></p>
    </div>
    <?php endforeach; ?>
</div>

<div class="grid lg:grid-cols-3 gap-6 mb-8">
    <section class="lg:col-span-2 glass-card rounded-2xl p-5 sm:p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg font-bold flex items-center gap-2">
                <i data-lucide="trending-up" class="w-5 h-5 text-rose-500"></i>
                New signups (14 days)
            </h2>
            <span class="text-xs font-medium text-slate-500 dark:text-slate-400"><?= number_format($totalSignupsWindow) ?> total</span>
        </div>
        <?php if ($totalSignupsWindow < 1): ?>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">No signups in the last 14 days.</p>
        <?php endif; ?>
        <div class="flex gap-1 sm:gap-2 h-44" role="img" aria-label="New signups per day, last 14 days">
            <?php foreach ($signups as $row):
                $c = (int) $row['count'];
                $hPx = $maxSignup > 0 ? (int) max($c > 0 ? 6 : 2, round(($c / $maxSignup) * $signupBarMaxPx)) : 2;
                ?>
            <div class="flex-1 flex flex-col items-center min-w-0 h-full min-h-0">
                <span class="text-xs font-semibold tabular-nums text-slate-600 dark:text-slate-300 shrink-0 leading-none mb-1"><?= $c ?></span>
                <div class="flex-1 w-full flex flex-col justify-end min-h-0">
                    <div
                        class="w-full max-w-[2.25rem] mx-auto rounded-t-lg bg-gradient-to-t from-rose-600 to-rose-400 dark:from-rose-500 dark:to-rose-300 shadow-sm"
                        style="height: <?= $hPx ?>px"
                    ></div>
                </div>
                <span class="text-[10px] text-slate-400 dark:text-slate-500 truncate w-full text-center mt-1.5 leading-tight"><?= e(date('M j', strtotime($row['day']))) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="glass-card rounded-2xl p-5">
        <h2 class="text-lg font-bold mb-4">Platform totals</h2>
        <ul class="space-y-3 text-sm">
            <?php foreach ([
                ['message-square', 'Messages', $s['messages']],
                ['paperclip', 'Resources', $s['resources']],
                ['award', 'Achievements unlocked', $s['achievements_unlocked']],
                ['music', 'Custom ambient tracks', $s['custom_tracks']],
            ] as [$icon, $label, $val]): ?>
            <li class="flex items-center justify-between py-2 border-b border-slate-100 dark:border-white/5 last:border-0">
                <span class="flex items-center gap-2 text-slate-600 dark:text-slate-300">
                    <i data-lucide="<?= $icon ?>" class="w-4 h-4 text-slate-400"></i>
                    <?= $label ?>
                </span>
                <span class="font-bold"><?= number_format((int) $val) ?></span>
            </li>
            <?php endforeach; ?>
        </ul>
    </section>
</div>

<div class="grid lg:grid-cols-2 gap-6">
    <section>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-bold">Recent Users</h2>
            <a href="<?= url('/admin/users') ?>" class="text-sm text-rose-500 font-medium hover:text-rose-400">View all →</a>
        </div>
        <div class="glass-card rounded-2xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="admin-table w-full text-sm">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Role</th>
                            <th>XP</th>
                            <th>Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentUsers as $u): ?>
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    <img src="<?= avatar_url($u['avatar'] ?? null) ?>" alt="" class="w-8 h-8 rounded-full">
                                    <div class="min-w-0">
                                        <p class="font-medium truncate"><?= e($u['name']) ?></p>
                                        <p class="text-xs text-slate-400 truncate"><?= e($u['email']) ?></p>
                                    </div>
                                </div>
                            </td>
                            <td><span class="admin-badge admin-badge-<?= e($u['role']) ?>"><?= e($u['role']) ?></span></td>
                            <td class="font-semibold"><?= number_format((int) $u['xp']) ?></td>
                            <td class="text-slate-400 whitespace-nowrap"><?= time_ago($u['created_at']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-bold">Recent Groups</h2>
            <a href="<?= url('/admin/groups') ?>" class="text-sm text-rose-500 font-medium hover:text-rose-400">View all →</a>
        </div>
        <?php if (empty($recentGroups)): ?>
        <div class="glass-card rounded-2xl p-8 text-center text-slate-500">No groups yet.</div>
        <?php else: foreach ($recentGroups as $g): ?>
        <div class="glass-card rounded-2xl p-4 mb-3 last:mb-0">
            <div class="flex items-center gap-4">
                <span class="w-11 h-11 rounded-2xl bg-gradient-to-br from-<?= e($g['color']) ?>-500 to-<?= e($g['color']) ?>-600 flex items-center justify-center text-white shadow-lg shrink-0">
                    <i data-lucide="<?= e($g['icon']) ?>" class="w-5 h-5"></i>
                </span>
                <div class="flex-1 min-w-0">
                    <p class="font-semibold truncate"><?= e($g['name']) ?></p>
                    <p class="text-xs text-slate-400">Owner: <?= e($g['owner_name']) ?> · <?= (int) $g['member_count'] ?> members</p>
                </div>
                <div class="text-right text-sm shrink-0">
                    <p class="font-semibold"><?= (int) $g['task_count'] ?> tasks</p>
                    <p class="text-xs text-slate-400 font-mono"><?= e($g['invite_code']) ?></p>
                </div>
            </div>
        </div>
        <?php endforeach; endif; ?>
    </section>
</div>

<section class="mt-6">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-bold">Latest Activity</h2>
        <a href="<?= url('/admin/activity') ?>" class="text-sm text-rose-500 font-medium hover:text-rose-400">Full log →</a>
    </div>
    <div class="glass-card rounded-2xl p-5 divide-y divide-slate-100 dark:divide-white/5">
        <?php if (empty($recentActivity)): ?>
        <p class="text-sm text-slate-500 py-4 text-center">No activity recorded yet.</p>
        <?php else: foreach ($recentActivity as $a): ?>
        <div class="flex gap-4 py-3 text-sm">
            <span class="w-9 h-9 rounded-xl bg-rose-500/10 flex items-center justify-center shrink-0">
                <i data-lucide="activity" class="w-4 h-4 text-rose-500"></i>
            </span>
            <div class="flex-1 min-w-0">
                <p class="font-medium capitalize"><?= e(str_replace('_', ' ', $a['action'])) ?></p>
                <p class="text-xs text-slate-400"><?= e($a['user_name'] ?? 'Unknown') ?> · <?= time_ago($a['created_at']) ?></p>
            </div>
        </div>
        <?php endforeach; endif; ?>
    </div>
</section>
