<?php
/** @var array $profile */
/** @var bool $isOwn */
/** @var int $campusRank */
/** @var int $totalCampusUsers */
/** @var array $taskStats */
/** @var array $focusStats */
/** @var array $achievements */
/** @var array $allAchievements */
?>
<div class="max-w-3xl mx-auto pb-12">
    <section class="rounded-3xl p-6 sm:p-8 mb-6 border border-white/10 bg-gradient-to-br from-slate-900/95 via-indigo-950/40 to-slate-950/95 shadow-2xl shadow-indigo-950/50">
        <div class="flex flex-col sm:flex-row items-center gap-6">
            <img src="<?= avatar_url($profile['avatar'] ?? null) ?>" class="w-24 h-24 rounded-2xl object-cover ring-4 ring-indigo-500/30" alt="">
            <div class="text-center sm:text-left flex-1">
                <?php if (!empty($campusRank)): ?>
                <p class="text-xs font-bold uppercase tracking-widest text-amber-400 mb-1">
                    <?php if (!empty($totalCampusUsers) && $totalCampusUsers > 0): ?>
                        Rank #<?= (int) $campusRank ?> of <?= (int) $totalCampusUsers ?> on campus
                    <?php else: ?>
                        Campus rank · #<?= (int) $campusRank ?>
                    <?php endif; ?>
                </p>
                <?php endif; ?>
                <h1 class="text-2xl font-bold text-white"><?= e($profile['name']) ?></h1>
                <p class="text-slate-400 mt-1"><?= e($profile['bio'] ?: 'No bio yet.') ?></p>
                <div class="flex flex-wrap justify-center sm:justify-start gap-4 mt-4 text-sm">
                    <span class="font-semibold text-indigo-300"><?= number_format((int) $profile['xp']) ?> XP</span>
                    <span class="text-slate-500">🔥 <?= (int) $profile['streak'] ?> day streak</span>
                    <span class="text-slate-500">Joined <?= date('M Y', strtotime($profile['created_at'])) ?></span>
                </div>
                <?php if (!empty($isOwn)): ?>
                <div class="mt-4">
                    <a href="<?= url('/profile/edit') ?>" class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-white/10 border border-white/15 text-sm font-semibold text-white hover:bg-white/15 transition-colors">Edit profile</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <div class="grid sm:grid-cols-3 gap-4 mb-6">
        <div class="rounded-2xl border border-white/10 bg-slate-900/50 p-4 text-center">
            <p class="text-2xl font-bold text-white"><?= (int) ($taskStats['completed'] ?? 0) ?></p>
            <p class="text-xs text-slate-500">Tasks completed</p>
        </div>
        <div class="rounded-2xl border border-white/10 bg-slate-900/50 p-4 text-center">
            <p class="text-2xl font-bold text-white"><?= round(($focusStats['total_minutes'] ?? 0) / 60, 1) ?>h</p>
            <p class="text-xs text-slate-500">Focus hours</p>
        </div>
        <div class="rounded-2xl border border-white/10 bg-slate-900/50 p-4 text-center">
            <p class="text-2xl font-bold text-white"><?= count($achievements) ?></p>
            <p class="text-xs text-slate-500">Achievements</p>
        </div>
    </div>

    <section class="rounded-2xl border border-white/10 bg-slate-900/40 p-6 mb-6">
        <h2 class="text-lg font-bold text-white mb-4">Achievements</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
            <?php foreach ($allAchievements as $a): ?>
            <div class="p-4 rounded-xl border text-center <?= $a['unlocked'] ? 'border-amber-500/35 bg-amber-500/[0.07]' : 'border-white/10 bg-slate-950/30 opacity-45' ?>">
                <i data-lucide="<?= e($a['icon']) ?>" class="w-8 h-8 mx-auto mb-2 <?= $a['unlocked'] ? 'text-amber-400' : 'text-slate-600' ?>"></i>
                <p class="text-sm font-semibold text-slate-100"><?= e($a['name']) ?></p>
                <p class="text-xs text-slate-500 mt-1"><?= e($a['description']) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

</div>
