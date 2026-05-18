<?php $hour = (int) date('H'); $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening'); ?>
<section class="mb-4 sm:mb-6 lg:mb-8">
    <div class="relative overflow-hidden rounded-2xl sm:rounded-3xl bg-gradient-to-br from-indigo-600 via-violet-600 to-purple-700 p-4 sm:p-6 sm:p-8 text-white shadow-xl shadow-indigo-500/20">
        <div class="absolute top-0 right-0 w-48 sm:w-64 h-48 sm:h-64 bg-white/10 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2"></div>
        <div class="relative">
            <p class="text-indigo-200 text-xs sm:text-sm font-medium mb-1"><?= $greeting ?></p>
            <h1 class="text-xl sm:text-2xl sm:text-3xl font-bold tracking-tight mb-1 sm:mb-2"><?= e($user['name']) ?></h1>
            <p class="text-indigo-100/90 text-xs sm:text-sm max-w-lg">You have <?= (int)($taskStats['in_progress'] ?? 0) ?> tasks in progress and a <?= (int)($user['streak'] ?? 0) ?>-day streak. Keep the momentum going.</p>
            <div class="flex flex-wrap gap-2 sm:gap-3 mt-4 sm:mt-6">
                <a href="<?= url('/focus') ?>" class="inline-flex items-center gap-2 px-3 sm:px-4 py-2 rounded-xl bg-white/20 hover:bg-white/30 text-xs sm:text-sm font-semibold backdrop-blur transition-colors">
                    <i data-lucide="timer" class="w-3 h-3 sm:w-4 sm:h-4"></i> Start Focus
                </a>
                <a href="<?= url('/groups/create') ?>" class="inline-flex items-center gap-2 px-3 sm:px-4 py-2 rounded-xl bg-white text-indigo-600 text-xs sm:text-sm font-semibold hover:bg-indigo-50 transition-colors">
                    <i data-lucide="plus" class="w-3 h-3 sm:w-4 sm:h-4"></i> New Group
                </a>
            </div>
        </div>
    </div>
</section>

<?php partial('story-rail', [
    'studyStoryPayload' => $studyStoryPayload,
    'storyRailTitle' => 'Study Story',
    'storyRailSubtitle' => 'Campus feed · disappears after ~24 hours',
    'storyComposerRedirect' => '/dashboard',
]); ?>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-3 lg:gap-4 mb-4 sm:mb-6 lg:mb-8">
    <?php foreach ([
        ['check-circle', 'Tasks Done', $taskStats['completed'] ?? 0, 'emerald'],
        ['timer', 'Focus Today', format_focus_duration((int) ($focusStats['today_minutes'] ?? 0)), 'sky'],
        ['flame', 'Streak', $user['streak'] ?? 0, 'amber'],
        ['zap', 'Total XP', number_format((int) ($user['xp'] ?? 0)), 'violet'],
    ] as [$icon, $label, $val, $color]): ?>
    <div class="stat-card">
        <div class="flex items-center gap-1.5 sm:gap-2 lg:gap-3 mb-1 lg:mb-2">
            <span class="w-7 h-7 sm:w-8 lg:w-10 sm:h-8 lg:h-10 rounded-lg lg:rounded-xl bg-<?= $color ?>-500/10 flex items-center justify-center">
                <i data-lucide="<?= $icon ?>" class="w-3.5 h-3.5 sm:w-4 sm:h-4 lg:w-5 lg:h-5 text-<?= $color ?>-500"></i>
            </span>
            <span class="text-[9px] sm:text-[10px] lg:text-xs font-medium text-slate-500 dark:text-slate-400 leading-tight"><?= $label ?></span>
        </div>
        <p class="text-lg sm:text-xl lg:text-2xl font-bold leading-none"><?= $val ?></p>
    </div>
    <?php endforeach; ?>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
    <section class="lg:col-span-2 space-y-3 sm:space-y-4 order-1">
        <div class="flex items-center justify-between">
            <h2 class="text-base sm:text-lg font-bold">Your Study Groups</h2>
            <a href="<?= url('/groups') ?>" class="text-xs sm:text-sm text-indigo-500 font-medium hover:text-indigo-400">View all</a>
        </div>
        <?php if (empty($groups)): ?>
        <div class="glass-card rounded-2xl p-6 sm:p-8 text-center">
            <i data-lucide="users" class="w-10 h-10 sm:w-12 sm:h-12 mx-auto text-slate-300 mb-3"></i>
            <p class="text-slate-500 mb-4">No groups yet. Create one or join with an invite code.</p>
            <a href="<?= url('/groups/create') ?>" class="btn-primary">Create Group</a>
        </div>
        <?php else: foreach (array_slice($groups, 0, 4) as $g): ?>
        <a href="<?= url('/groups/' . $g['id']) ?>" class="block glass-card rounded-2xl p-3 sm:p-4 hover:shadow-lg transition-all group">
            <div class="flex items-start sm:items-center gap-3 sm:gap-4">
                <span class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-gradient-to-br from-<?= e($g['color']) ?>-500 to-<?= e($g['color']) ?>-600 flex items-center justify-center text-white shadow-lg shrink-0">
                    <i data-lucide="<?= e($g['icon']) ?>" class="w-5 h-5 sm:w-6 sm:h-6"></i>
                </span>
                <div class="flex-1 min-w-0">
                    <h3 class="font-semibold text-sm sm:text-base group-hover:text-indigo-500 transition-colors"><?= e($g['name']) ?></h3>
                    <p class="text-xs sm:text-sm text-slate-500"><?= e($g['description']) ?></p>
                </div>
                <div class="text-right text-xs sm:text-sm shrink-0 hidden sm:block">
                    <p class="font-semibold"><?= (int)$g['open_tasks'] ?> open</p>
                    <p class="text-slate-400"><?= (int)$g['member_count'] ?> members</p>
                </div>
                <div class="text-right text-[11px] sm:text-sm shrink-0 sm:hidden">
                    <p class="font-semibold text-[11px]"><?= (int)$g['member_count'] ?> members</p>
                    <p class="text-slate-400 text-[10px]"><?= (int)$g['open_tasks'] ?> open</p>
                </div>
            </div>
        </a>
        <?php endforeach; endif; ?>
    </section>

    <section class="space-y-4 order-2 lg:order-2">
        <div class="glass-card rounded-2xl p-4 sm:p-5">
            <h2 class="text-base sm:text-lg font-bold mb-3 sm:mb-4 flex items-center gap-2"><i data-lucide="trophy" class="w-4 h-4 sm:w-5 sm:h-5 text-amber-500"></i> Leaderboard</h2>
            <ol class="space-y-2 sm:space-y-3">
                <?php foreach ($leaderboard as $i => $u): ?>
                <li class="flex items-center gap-2 sm:gap-3">
                    <span class="w-5 sm:w-6 text-center text-xs sm:text-sm font-bold text-slate-400"><?= $i + 1 ?></span>
                    <img src="<?= avatar_url($u['avatar'] ?? null) ?>" class="w-7 h-7 sm:w-8 sm:h-8 rounded-full" alt="">
                    <span class="flex-1 text-xs sm:text-sm font-medium truncate"><?= e($u['name']) ?></span>
                    <span class="text-[10px] sm:text-xs font-semibold text-indigo-500"><?= number_format($u['xp']) ?> XP</span>
                </li>
                <?php endforeach; ?>
            </ol>
            <a href="<?= url('/leaderboard') ?>" class="block mt-3 sm:mt-4 text-center text-xs sm:text-sm text-indigo-500 font-medium">Full leaderboard →</a>
        </div>

        <div class="glass-card rounded-2xl p-4 sm:p-5">
            <h2 class="text-base sm:text-lg font-bold mb-2 sm:mb-3">Recent Activity</h2>
            <?php if (empty($recentActivity)): ?>
            <p class="text-xs sm:text-sm text-slate-400">Complete tasks or focus sessions to see activity here.</p>
            <?php else: foreach ($recentActivity as $a): ?>
            <div class="flex gap-2 sm:gap-3 py-2 border-b border-slate-100 dark:border-white/5 last:border-0 text-xs sm:text-sm">
                <span class="w-1.5 sm:w-2 h-1.5 sm:h-2 mt-1.5 sm:mt-2 rounded-full bg-indigo-500 shrink-0"></span>
                <div>
                    <p class="font-medium capitalize"><?= e(str_replace('_', ' ', $a['action'])) ?></p>
                    <p class="text-[10px] sm:text-xs text-slate-400"><?= time_ago($a['created_at']) ?></p>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </section>
</div>
