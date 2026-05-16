<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
    <div>
        <h1 class="text-2xl font-bold">Study Groups</h1>
        <p class="text-slate-500 text-sm mt-1">Collaborate with classmates on shared goals</p>
    </div>
    <a href="<?= url('/groups/create') ?>" class="btn-primary"><i data-lucide="plus" class="w-4 h-4"></i> New Group</a>
</div>

<form method="POST" action="<?= url('/groups/join') ?>" class="glass-card rounded-2xl p-4 mb-6 flex flex-col sm:flex-row gap-3" x-data>
    <?= csrf_field() ?>
    <input type="text" name="invite_code" required placeholder="Enter invite code" class="input flex-1">
    <button type="submit" class="btn-secondary shrink-0">Join Group</button>
</form>

<?php if (empty($groups)): ?>
<div class="glass-card rounded-2xl p-12 text-center">
    <i data-lucide="users" class="w-16 h-16 mx-auto text-slate-300 mb-4"></i>
    <h2 class="text-lg font-semibold mb-2">No groups yet</h2>
    <p class="text-slate-500 mb-6">Create a study group or join with an invite code from a classmate.</p>
    <a href="<?= url('/groups/create') ?>" class="btn-primary">Create your first group</a>
</div>
<?php else: ?>
<div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-4">
    <?php foreach ($groups as $g): ?>
    <a href="<?= url('/groups/' . $g['id']) ?>" class="glass-card rounded-2xl p-5 hover:shadow-xl transition-all group block">
        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center text-white mb-4 shadow-lg group-hover:scale-105 transition-transform">
            <i data-lucide="<?= e($g['icon']) ?>" class="w-6 h-6"></i>
        </div>
        <h3 class="font-bold text-lg mb-1 group-hover:text-indigo-500 transition-colors"><?= e($g['name']) ?></h3>
        <p class="text-sm text-slate-500 line-clamp-2 mb-4"><?= e($g['description']) ?></p>
        <p class="flex justify-between text-xs text-slate-400">
            <span><?= (int)$g['member_count'] ?> members</span>
            <span><?= (int)$g['open_tasks'] ?> open tasks</span>
        </p>
    </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>
