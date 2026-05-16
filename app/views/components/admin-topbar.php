<?php $pageTitle = $title ?? 'Admin'; ?>
<header class="sticky top-0 z-30 glass-topbar border-b border-slate-200/50 dark:border-white/10 px-4 sm:px-6 h-16 flex items-center justify-between gap-4 w-full">
    <div class="flex items-center gap-3 min-w-0">
        <a href="<?= url('/admin') ?>" class="lg:hidden p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-white/10">
            <i data-lucide="menu" class="w-5 h-5"></i>
        </a>
        <h1 class="text-lg font-bold truncate"><?= e($pageTitle) ?></h1>
    </div>
    <div class="flex items-center gap-1 sm:gap-2 flex-shrink-0">
        <a href="<?= url('/dashboard') ?>" class="hidden sm:inline-flex items-center gap-2 px-3 py-2 rounded-xl text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-white/10 transition-colors">
            <i data-lucide="home" class="w-4 h-4"></i>
            <span>App</span>
        </a>
        <button type="button" onclick="document.documentElement.classList.toggle('dark'); localStorage.setItem('theme', document.documentElement.classList.contains('dark') ? 'dark' : 'light')" class="p-2.5 rounded-xl hover:bg-slate-100 dark:hover:bg-white/10 transition-colors" title="Toggle theme">
            <i data-lucide="sun" class="w-5 h-5 hidden dark:block"></i>
            <i data-lucide="moon" class="w-5 h-5 block dark:hidden"></i>
        </button>
    </div>
</header>
