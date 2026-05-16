<?php partial('group-header', ['group' => $group]); ?>

<?php partial('story-rail', [
    'studyStoryPayload' => $studyStoryPayload,
    'storyRailTitle' => 'Study Story',
    'storyRailSubtitle' => 'This group only · ~24h',
    'storyRailBrowse' => url('/stories'),
    'storyComposerRedirect' => '/groups/' . (int) $group['id'],
]); ?>

    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
    <a href="<?= url('/groups/' . $group['id'] . '/tasks') ?>" class="stat-card group">
        <i data-lucide="square-kanban" class="w-8 h-8 text-indigo-500 mb-2 stroke-[1.75]"></i>
        <h3 class="font-semibold group-hover:text-indigo-500">Kanban Board</h3>
        <p class="text-sm text-slate-500">Manage tasks</p>
    </a>
    <a href="<?= url('/groups/' . $group['id'] . '/agile') ?>" class="stat-card group border-fuchsia-500/20 hover:border-fuchsia-500/40">
        <i data-lucide="orbit" class="w-8 h-8 text-fuchsia-500 mb-2 stroke-[1.75]"></i>
        <h3 class="font-semibold group-hover:text-fuchsia-500">Agile workspace</h3>
        <p class="text-sm text-slate-500">Sprints · MoSCoW · Traceability</p>
    </a>
    <a href="<?= url('/groups/' . $group['id'] . '/chat') ?>" class="stat-card group">
        <i data-lucide="message-circle" class="w-8 h-8 text-sky-500 mb-2"></i>
        <h3 class="font-semibold group-hover:text-sky-500">Group Chat</h3>
        <p class="text-sm text-slate-500">Message members</p>
    </a>
    <a href="<?= url('/groups/' . $group['id'] . '/resources') ?>" class="stat-card group">
        <i data-lucide="folder-open" class="w-8 h-8 text-emerald-500 mb-2"></i>
        <h3 class="font-semibold group-hover:text-emerald-500">Resources</h3>
        <p class="text-sm text-slate-500">Shared files</p>
    </a>
    <a href="<?= url('/focus') ?>" class="stat-card group">
        <i data-lucide="timer" class="w-8 h-8 text-amber-500 mb-2"></i>
        <h3 class="font-semibold group-hover:text-amber-500">Focus Timer</h3>
        <p class="text-sm text-slate-500">Pomodoro sessions</p>
    </a>
</div>

<section class="glass-card rounded-2xl p-6">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-bold">Members</h2>
        <span class="text-xs text-slate-400">Invite: <code class="px-2 py-1 rounded bg-slate-100 dark:bg-white/10 font-mono"><?= e($group['invite_code']) ?></code></span>
    </div>
    <ul class="space-y-3">
        <?php
        foreach ($members as $m):
            $sr = \App\Models\Group::sanitizeScrumRole((string) ($m['scrum_role'] ?? 'developer'));
            $srShort = ['product_owner' => 'Product Owner', 'scrum_master' => 'Scrum Master', 'developer' => 'Team Member'][$sr];
        ?>
        <li class="flex items-center gap-3">
            <img src="<?= avatar_url($m['avatar'] ?? null) ?>" class="w-10 h-10 rounded-full" alt="">
            <div class="flex-1">
                <p class="font-medium"><?= e($m['name']) ?> <span class="text-xs text-slate-400 capitalize">(<?= e($m['role']) ?>)</span></p>
                <p class="text-xs text-slate-400 flex flex-wrap items-center gap-2"><?= number_format($m['xp']) ?> XP
                    <span class="scrum-role-badge-mini scrum-role-<?= e($sr) ?>"><?= e($srShort) ?></span>
                </p>
            </div>
            <a href="<?= profile_public_href($m) ?>" class="text-sm text-indigo-500">View</a>
        </li>
        <?php endforeach; ?>
    </ul>
</section>
