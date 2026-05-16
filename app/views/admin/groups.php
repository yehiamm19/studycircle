<section class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold">Study Groups</h1>
        <p class="text-sm text-slate-500 mt-1"><?= count($groups) ?> groups shown</p>
    </div>
    <form method="get" action="<?= url('/admin/groups') ?>" class="flex gap-2 w-full sm:max-w-md">
        <div class="relative flex-1">
            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
            <input type="search" name="q" value="<?= e($search) ?>" placeholder="Search name or invite code…"
                class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-slate-100/80 dark:bg-white/5 border border-transparent dark:border-white/10 text-sm focus:ring-2 focus:ring-rose-500/40">
        </div>
        <button type="submit" class="btn-primary shrink-0">Search</button>
    </form>
</section>

<div class="grid gap-4">
    <?php if (empty($groups)): ?>
    <div class="glass-card rounded-2xl p-12 text-center text-slate-500">
        <i data-lucide="folder-kanban" class="w-12 h-12 mx-auto mb-3 opacity-40"></i>
        No groups found.
    </div>
    <?php else: foreach ($groups as $g): ?>
    <div class="glass-card rounded-2xl p-5">
        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
            <span class="w-14 h-14 rounded-2xl bg-gradient-to-br from-<?= e($g['color']) ?>-500 to-<?= e($g['color']) ?>-600 flex items-center justify-center text-white shadow-lg shrink-0">
                <i data-lucide="<?= e($g['icon']) ?>" class="w-7 h-7"></i>
            </span>
            <div class="flex-1 min-w-0">
                <h3 class="text-lg font-bold truncate"><?= e($g['name']) ?></h3>
                <p class="text-sm text-slate-500 line-clamp-2"><?= e($g['description'] ?: 'No description') ?></p>
                <div class="flex flex-wrap gap-3 mt-2 text-xs text-slate-400">
                    <span class="inline-flex items-center gap-1"><i data-lucide="user" class="w-3.5 h-3.5"></i> <?= e($g['owner_name']) ?></span>
                    <span class="inline-flex items-center gap-1"><i data-lucide="users" class="w-3.5 h-3.5"></i> <?= (int) $g['member_count'] ?> members</span>
                    <span class="inline-flex items-center gap-1"><i data-lucide="list-todo" class="w-3.5 h-3.5"></i> <?= (int) $g['task_count'] ?> tasks</span>
                    <span class="font-mono bg-slate-100 dark:bg-white/5 px-2 py-0.5 rounded"><?= e($g['invite_code']) ?></span>
                </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <a href="<?= url('/groups/' . $g['id']) ?>" class="admin-action-btn" title="View in app">
                    <i data-lucide="external-link" class="w-4 h-4"></i>
                </a>
                <form method="post" action="<?= url('/admin/groups/' . $g['id'] . '/delete') ?>" onsubmit="return confirm('Delete this group and all its data?')">
                    <?= csrf_field() ?>
                    <button type="submit" class="admin-action-btn admin-action-danger" title="Delete">
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; endif; ?>
</div>
