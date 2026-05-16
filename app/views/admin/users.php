<section class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold">Users</h1>
        <p class="text-sm text-slate-500 mt-1"><?= count($users) ?> users shown</p>
    </div>
    <div class="flex gap-2 w-full sm:max-w-md">
        <form method="get" action="<?= url('/admin/users') ?>" class="flex gap-2 flex-1">
            <div class="relative flex-1">
                <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
                <input type="search" name="q" value="<?= e($search) ?>" placeholder="Search name or email…"
                    class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-slate-100/80 dark:bg-white/5 border border-transparent dark:border-white/10 text-sm focus:ring-2 focus:ring-rose-500/40">
            </div>
            <button type="submit" class="btn-primary shrink-0">Search</button>
        </form>
        <a href="<?= url('/admin/users/create') ?>" class="btn-primary shrink-0" title="Add user">
            <i data-lucide="plus" class="w-4 h-4"></i>
        </a>
    </div>
</section>

<div class="glass-card rounded-2xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="admin-table w-full text-sm">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Role</th>
                    <th>XP</th>
                    <th>Streak</th>
                    <th>Joined</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                <tr><td colspan="6" class="text-center py-12 text-slate-500">No users found.</td></tr>
                <?php else: foreach ($users as $u): ?>
                <tr>
                    <td>
                        <div class="flex items-center gap-3">
                            <img src="<?= avatar_url($u['avatar'] ?? null) ?>" alt="" class="w-9 h-9 rounded-full">
                            <div class="min-w-0">
                                <p class="font-semibold truncate"><?= e($u['name']) ?></p>
                                <p class="text-xs text-slate-400 truncate"><?= e($u['email']) ?></p>
                            </div>
                        </div>
                    </td>
                    <td><span class="admin-badge admin-badge-<?= e($u['role']) ?>"><?= e($u['role']) ?></span></td>
                    <td class="font-semibold"><?= number_format((int) $u['xp']) ?></td>
                    <td><?= (int) $u['streak'] ?>d</td>
                    <td class="text-slate-400 whitespace-nowrap"><?= time_ago($u['created_at']) ?></td>
                    <td class="text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="<?= url('/admin/users/' . $u['id'] . '/edit') ?>" class="admin-action-btn" title="Edit">
                                <i data-lucide="pencil" class="w-4 h-4"></i>
                            </a>
                            <?php if ((int) $u['id'] !== \App\Auth::id()): ?>
                            <form method="post" action="<?= url('/admin/users/' . $u['id'] . '/delete') ?>" class="inline" onsubmit="return confirm('Delete this user permanently?')">
                                <?= csrf_field() ?>
                                <button type="submit" class="admin-action-btn admin-action-danger" title="Delete">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
