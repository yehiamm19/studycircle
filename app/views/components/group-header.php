<div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 mb-8">
    <div class="flex items-center gap-4">
        <span class="w-14 h-14 rounded-2xl bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center text-white shadow-lg">
            <i data-lucide="<?= e($group['icon']) ?>" class="w-7 h-7"></i>
        </span>
        <div>
            <h1 class="text-2xl font-bold"><?= e($group['name']) ?></h1>
            <p class="text-slate-500 text-sm mt-0.5"><?= e($group['description']) ?></p>
        </div>
    </div>
    <?php if ((int)$group['owner_id'] === \App\Auth::id()): ?>
    <a href="<?= url('/groups/' . $group['id'] . '/edit') ?>" class="btn-secondary"><i data-lucide="settings" class="w-4 h-4"></i> Settings</a>
    <?php endif; ?>
</div>
