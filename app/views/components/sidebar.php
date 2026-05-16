<?php $user = \App\Auth::user(); $current = $_SERVER['REQUEST_URI'] ?? ''; ?>
<aside class="hidden lg:flex lg:flex-col lg:fixed lg:inset-y-0 lg:w-64 glass-sidebar border-r border-white/10 z-40">
    <div class="flex items-center justify-center px-4 h-[4.5rem] border-b border-white/10">
        <?php partial('logo', ['size' => 'sm']); ?>
    </div>
    <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
        <?php
        $links = [
            ['/dashboard', 'layout-dashboard', 'Dashboard'],
            ['/groups', 'users', 'Groups'],
            ['/focus', 'timer', 'Focus'],
            ['/leaderboard', 'trophy', 'Leaderboard'],
            ['/profile', 'user', 'Profile'],
            ['/stories', 'images', 'Study Story'],
        ];
        if (\App\Auth::isAdmin()) {
            $links[] = ['/admin', 'shield', 'Admin'];
        }
        foreach ($links as [$href, $icon, $label]):
            $active = str_contains($current, $href);
        ?>
        <a href="<?= url($href) ?>" class="nav-link <?= $active ? 'nav-link-active' : '' ?>">
            <i data-lucide="<?= $icon ?>" class="w-5 h-5"></i>
            <span><?= $label ?></span>
        </a>
        <?php endforeach; ?>
    </nav>
    <div class="p-4 border-t border-white/10">
        <div class="flex items-center gap-3 p-2 rounded-xl">
            <img src="<?= avatar_url($user['avatar'] ?? null) ?>" alt="" class="w-9 h-9 rounded-full object-cover ring-2 ring-indigo-500/30">
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold truncate"><?= e($user['name']) ?></p>
                <p class="text-xs text-slate-400"><?= number_format($user['xp']) ?> XP · <?= (int)$user['streak'] ?> day streak</p>
            </div>
        </div>
    </div>
</aside>
