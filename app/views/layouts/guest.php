<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="referrer" content="strict-origin-when-cross-origin">
    <title><?= e($title ?? 'StudyCircle') ?> · StudyCircle</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body class="auth-page h-full font-sans antialiased" data-base-url="<?= e(rtrim(url(''), '/')) ?>">
    <section class="min-h-full relative overflow-hidden bg-gradient-to-br from-slate-950 via-indigo-950 to-slate-900">
        <div class="absolute inset-0 overflow-hidden pointer-events-none" aria-hidden="true">
            <span class="absolute -top-40 -right-40 w-[28rem] h-[28rem] rounded-full bg-indigo-500/25 blur-3xl animate-float block"></span>
            <span class="absolute -bottom-40 -left-40 w-[28rem] h-[28rem] rounded-full bg-violet-600/20 blur-3xl animate-float block" style="animation-delay:-3s"></span>
            <span class="absolute top-1/3 left-1/2 -translate-x-1/2 w-[32rem] h-[32rem] rounded-full bg-indigo-400/5 blur-3xl block"></span>
        </div>
        <div class="relative min-h-full flex flex-col items-center justify-center p-6 sm:p-10">
            <div class="mb-12 flex justify-center w-full">
                <?php partial('logo', ['size' => 'lg', 'href' => url('/')]); ?>
            </div>
            <article class="w-full max-w-[420px] auth-card rounded-3xl p-8 sm:p-10 page-enter">
                <?php partial('flash', ['auth' => true]); ?>
                <?= $content ?>
            </article>
            <p class="mt-10 text-sm text-slate-500 text-center max-w-sm">Collaborative study management for students</p>
        </div>
    </section>
    <script src="<?= asset('js/app.js') ?>"></script>
    <script>lucide.createIcons();</script>
</body>
</html>
