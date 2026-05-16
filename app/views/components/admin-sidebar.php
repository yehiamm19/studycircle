<?php
$user = \App\Auth::user();
$current = $_SERVER['REQUEST_URI'] ?? '';
$links = [
    ['/admin', 'layout-dashboard', 'Overview'],
    ['/admin/users', 'users', 'Users'],
    ['/admin/groups', 'folder-kanban', 'Groups'],
    ['/admin/activity', 'activity', 'Activity Log'],
];
?>
<aside class="hidden lg:flex lg:flex-col lg:fixed lg:inset-y-0 lg:w-64 glass-sidebar border-r border-white/10 z-40 admin-sidebar">
    <div class="flex items-center justify-between px-4 h-[4.5rem] border-b border-white/10">
        <?php partial('logo', ['size' => 'sm']); ?>
        <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-1 rounded-lg bg-rose-500/20 text-rose-300 border border-rose-500/30">Admin</span>
    </div>
    <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
        <?php foreach ($links as [$href, $icon, $label]):
            $active = $href === '/admin'
                ? (str_ends_with(rtrim($current, '/'), '/admin') || str_contains($current, '/admin?'))
                : str_contains($current, $href);
        ?>
        <a href="<?= url($href) ?>" class="nav-link admin-nav-link <?= $active ? 'admin-nav-link-active' : '' ?>">
            <i data-lucide="<?= $icon ?>" class="w-5 h-5"></i>
            <span><?= $label ?></span>
        </a>
        <?php endforeach; ?>
    </nav>
    <div class="p-3 space-y-1 border-t border-white/10">
        <a href="<?= url('/dashboard') ?>" class="nav-link admin-nav-link">
            <i data-lucide="arrow-left" class="w-5 h-5"></i>
            <span>Back to App</span>
        </a>
        <div class="flex items-center gap-3 p-2 rounded-xl mt-2">
            <img src="<?= avatar_url($user['avatar'] ?? null) ?>" alt="" class="w-9 h-9 rounded-full object-cover ring-2 ring-rose-500/40">
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold truncate"><?= e($user['name']) ?></p>
                <p class="text-xs text-rose-300/80">Administrator</p>
            </div>
        </div>
    </div>
</aside>
