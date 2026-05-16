<h1 class="text-2xl font-bold mb-2">Leaderboard</h1>
<p class="text-slate-500 text-sm mb-8">Top students by XP earned across StudyCircle</p>

<div class="glass-card rounded-2xl overflow-hidden">
    <ol class="divide-y divide-slate-100 dark:divide-white/5">
        <?php foreach ($users as $i => $u): ?>
        <li class="flex items-center gap-4 p-4 hover:bg-slate-50 dark:hover:bg-white/5 transition-colors">
            <span class="w-8 text-center font-bold <?= $i < 3 ? 'text-amber-500 text-lg' : 'text-slate-400' ?>"><?= $i + 1 ?></span>
            <img src="<?= avatar_url($u['avatar'] ?? null) ?>" class="w-12 h-12 rounded-full ring-2 ring-indigo-500/20" alt="">
            <div class="flex-1">
                <a href="<?= profile_public_href($u) ?>" class="font-semibold hover:text-indigo-500"><?= e($u['name']) ?></a>
                <p class="text-xs text-slate-400">🔥 <?= (int)$u['streak'] ?> day streak</p>
            </div>
            <span class="text-lg font-bold text-indigo-500"><?= number_format($u['xp']) ?> <span class="text-xs font-normal text-slate-400">XP</span></span>
        </li>
        <?php endforeach; ?>
    </ol>
</div>
