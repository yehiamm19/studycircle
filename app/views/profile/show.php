<div class="max-w-4xl mx-auto">
    <section class="glass-card rounded-3xl p-6 sm:p-8 mb-6">
        <div class="flex flex-col sm:flex-row items-center gap-6">
            <img src="<?= avatar_url($profile['avatar'] ?? null) ?>" class="w-24 h-24 rounded-2xl object-cover ring-4 ring-indigo-500/20" alt="">
            <div class="text-center sm:text-left flex-1">
                <?php if (!empty($campusRank)): ?>
                <p class="text-xs font-bold uppercase tracking-widest text-amber-500 dark:text-amber-400 mb-1">
                    <?php if (!empty($totalCampusUsers) && $totalCampusUsers > 0): ?>
                        Rank #<?= (int) $campusRank ?> of <?= (int) $totalCampusUsers ?> on campus
                    <?php else: ?>
                        Campus rank · #<?= (int) $campusRank ?>
                    <?php endif; ?>
                </p>
                <?php endif; ?>
                <h1 class="text-2xl font-bold"><?= e($profile['name']) ?></h1>
                <p class="text-slate-500 mt-1"><?= e($profile['bio'] ?: 'No bio yet.') ?></p>
                <div class="flex flex-wrap justify-center sm:justify-start gap-4 mt-4 text-sm">
                    <span class="font-semibold text-indigo-500"><?= number_format($profile['xp']) ?> XP</span>
                    <span class="text-slate-400">🔥 <?= (int) $profile['streak'] ?> day streak</span>
                    <span class="text-slate-400">Joined <?= date('M Y', strtotime($profile['created_at'])) ?></span>
                </div>
            </div>
            <?php if ($isOwn): ?>
            <a href="<?= url('/profile/edit') ?>" class="btn-secondary">Edit Profile</a>
            <?php endif; ?>
        </div>
    </section>

    <div class="grid sm:grid-cols-3 gap-4 mb-6">
        <div class="stat-card text-center"><p class="text-2xl font-bold"><?= (int)($taskStats['completed'] ?? 0) ?></p><p class="text-xs text-slate-400">Tasks Completed</p></div>
        <div class="stat-card text-center"><p class="text-2xl font-bold"><?= round(($focusStats['total_minutes'] ?? 0) / 60, 1) ?>h</p><p class="text-xs text-slate-400">Focus Hours</p></div>
        <div class="stat-card text-center"><p class="text-2xl font-bold"><?= count($achievements) ?></p><p class="text-xs text-slate-400">Achievements</p></div>
    </div>

    <section class="glass-card rounded-2xl p-6 mb-6">
        <h2 class="text-lg font-bold mb-4 text-center">Achievements</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
            <?php foreach ($allAchievements as $a): ?>
            <?php
            $sharePayloadJson = htmlspecialchars(
                json_encode(
                    [
                        'name' => $a['name'],
                        'description' => $a['description'],
                        'icon' => $a['icon'],
                        'slug' => $a['slug'] ?? '',
                    ],
                    JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP
                ),
                ENT_QUOTES,
                'UTF-8'
            );
            ?>
            <div class="p-4 rounded-xl border <?= $a['unlocked'] ? 'border-amber-500/30 bg-amber-500/5' : 'border-slate-200 dark:border-white/10 opacity-50' ?> text-center flex flex-col min-h-[11rem]">
                <div class="flex-1 flex flex-col">
                    <i data-lucide="<?= e($a['icon']) ?>" class="w-8 h-8 mx-auto mb-2 <?= $a['unlocked'] ? 'text-amber-500' : 'text-slate-400' ?>"></i>
                    <p class="text-sm font-semibold"><?= e($a['name']) ?></p>
                    <p class="text-xs text-slate-400 mt-1 grow"><?= e($a['description']) ?></p>
                </div>
                <?php if (!empty($isOwn) && $a['unlocked']): ?>
                <button type="button" class="achievement-share-trigger mt-3 w-full py-2 rounded-xl text-xs font-bold uppercase tracking-wide border border-amber-400/40 bg-white/70 text-amber-800 hover:bg-white dark:bg-slate-900/70 dark:text-amber-200 dark:border-amber-500/35 dark:hover:bg-slate-800/90 transition-colors" data-achievement="<?= $sharePayloadJson ?>">
                    Share card
                </button>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <?php if (!empty($activity)): ?>
    <section class="glass-card rounded-2xl p-6">
        <h2 class="text-lg font-bold mb-3">Activity</h2>
        <?php foreach ($activity as $a): ?>
        <p class="text-sm py-2 border-b border-slate-100 dark:border-white/5 capitalize"><?= e(str_replace('_', ' ', $a['action'])) ?> · <span class="text-slate-400"><?= time_ago($a['created_at']) ?></span></p>
        <?php endforeach; ?>
    </section>
    <?php endif; ?>
</div>

<?php if (!empty($isOwn)): ?>
<?php partial('achievement-share-modal'); ?>
<script type="application/json" id="achievement-share-boot"><?= json_encode([
    'appName' => config('app_name'),
    'logoUrl' => $shareLogoUrl,
    'profileUrl' => $shareProfileHref,
    'avatarUrl' => absolute_site_href(avatar_url($profile['avatar'] ?? null)),
    'userName' => $profile['name'],
    'xp' => (int) $profile['xp'],
    'rank' => (int) $campusRank,
    'totalCampusUsers' => (int) $totalCampusUsers,
 ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) ?></script>
<script src="<?= asset('js/achievement-share.js') ?>" defer></script>
<?php endif; ?>
