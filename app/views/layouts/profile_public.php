<!DOCTYPE html>
<html lang="en" class="h-full dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="referrer" content="strict-origin-when-cross-origin">
    <title><?= e($title ?? 'Profile') ?> · StudyCircle</title>
    <meta name="description" content="Achievements &amp; study profile on StudyCircle">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="<?= asset('js/app.js') ?>" defer></script>
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body class="min-h-full font-sans antialiased bg-slate-950 text-slate-100 pb-14" style="font-family:'Plus Jakarta Sans',system-ui,sans-serif" data-base-url="<?= e(rtrim(url(''), '/')) ?>">
    <header class="border-b border-white/10 backdrop-blur bg-slate-950/85 sticky top-0 z-40">
        <div class="max-w-3xl mx-auto px-4 py-4 flex items-center justify-between gap-4">
            <span class="shrink-0"><?php partial('logo', ['href' => url('/login'), 'size' => 'sm']); ?></span>
            <div class="flex items-center gap-2 text-xs sm:text-sm">
                <a href="<?= url('/login') ?>" class="px-4 py-2 rounded-xl bg-white text-slate-900 font-semibold hover:bg-slate-100 transition-colors whitespace-nowrap">Sign in</a>
                <?php if (!\App\Auth::check()): ?>
                <a href="<?= url('/register') ?>" class="px-4 py-2 rounded-xl border border-white/20 font-semibold text-slate-200 hover:bg-white/5 transition-colors whitespace-nowrap">Join</a>
                <?php else: ?>
                <a href="<?= url('/dashboard') ?>" class="px-4 py-2 rounded-xl border border-white/20 font-semibold text-slate-200 hover:bg-white/5 transition-colors whitespace-nowrap">Dashboard</a>
                <?php endif; ?>
            </div>
        </div>
    </header>
    <main class="max-w-3xl mx-auto px-4 pt-10">
        <?= $content ?>
    </main>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>lucide.createIcons();</script>
</body>
</html>
