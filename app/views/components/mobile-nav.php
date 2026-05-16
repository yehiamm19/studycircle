<?php $current = $_SERVER['REQUEST_URI'] ?? ''; ?>
<nav class="lg:hidden fixed bottom-0 inset-x-0 z-40 glass-topbar border-t border-slate-200/50 dark:border-white/10">
    <div class="flex justify-around items-center h-16 px-2">
        <?php foreach ([['/dashboard','layout-dashboard','Home'],['/groups','users','Groups'],['/stories','images','Study Story'],['/focus','timer','Focus'],['/profile','user','Profile']] as [$href,$icon,$label]):
            $active = str_contains($current, $href);
        ?>
        <a href="<?= url($href) ?>" class="flex flex-col items-center gap-0.5 px-2 py-1 <?= $active ? 'text-indigo-500' : 'text-slate-400' ?>">
            <i data-lucide="<?= $icon ?>" class="w-5 h-5"></i>
            <span class="text-[9px] font-medium leading-tight text-center max-w-[4.75rem] line-clamp-2"><?= e($label) ?></span>
        </a>
        <?php endforeach; ?>
    </div>
</nav>

