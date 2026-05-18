<?php $current = $_SERVER['REQUEST_URI'] ?? ''; ?>
<nav class="lg:hidden fixed bottom-0 inset-x-0 z-40 glass-topbar border-t border-slate-200/50 dark:border-white/10 safe-bottom">
    <div class="flex justify-around items-center h-16 px-1">
        <?php foreach ([['/dashboard','layout-dashboard','Home'],['/groups','users','Groups'],['/focus','timer','Focus'],['/stories','images','Story'],['/profile','user','Profile']] as [$href,$icon,$label]):
            $active = str_contains($current, $href);
        ?>
        <a href="<?= url($href) ?>" class="flex flex-col items-center justify-center gap-0.5 px-1 py-1.5 min-w-[4rem] <?= $active ? 'text-indigo-500' : 'text-slate-400' ?>">
            <i data-lucide="<?= $icon ?>" class="w-5 h-5"></i>
            <span class="text-[10px] font-medium leading-tight text-center max-w-[4.5rem] truncate"><?= e($label) ?></span>
        </a>
        <?php endforeach; ?>
    </div>
</nav>

