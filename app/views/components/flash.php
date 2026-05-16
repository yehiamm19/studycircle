<?php $authFlash = $auth ?? false; ?>
<?php if ($msg = flash('success')): ?>
<div class="mb-4 p-4 rounded-xl text-sm flex items-center gap-2 toast-enter <?= $authFlash ? 'flash-success' : 'bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400' ?>">
    <i data-lucide="check-circle" class="w-4 h-4 shrink-0"></i><?= e($msg) ?>
</div>
<?php endif; ?>
<?php if ($msg = flash('error')): ?>
<div class="mb-4 p-4 rounded-xl text-sm flex items-center gap-2 toast-enter <?= $authFlash ? 'flash-error' : 'bg-rose-500/10 border border-rose-500/20 text-rose-600 dark:text-rose-400' ?>">
    <i data-lucide="alert-circle" class="w-4 h-4 shrink-0"></i><?= e($msg) ?>
</div>
<?php endif; ?>
<?php if ($msg = flash('info')): ?>
<div class="mb-4 p-4 rounded-xl text-sm flex items-center gap-2 toast-enter <?= $authFlash ? 'flash-info' : 'bg-sky-500/10 border border-sky-500/20 text-sky-600 dark:text-sky-400' ?>">
    <i data-lucide="info" class="w-4 h-4 shrink-0"></i><?= e($msg) ?>
</div>
<?php endif; ?>
