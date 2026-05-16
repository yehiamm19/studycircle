<?php
$size = $size ?? 'md';
$classes = match ($size) {
    'sm' => 'brand-logo brand-logo-sm',
    'lg' => 'brand-logo brand-logo-lg',
    default => 'brand-logo',
};
$href = $href ?? url('/dashboard');
?>
<a href="<?= e($href) ?>" class="brand-logo-link inline-flex items-center justify-center shrink-0 group <?= ($size ?? 'md') === 'lg' ? 'w-full' : '' ?>" aria-label="StudyCircle home">
    <img src="<?= logo_url() ?>" alt="StudyCircle" class="<?= $classes ?> transition-opacity duration-200 group-hover:opacity-90" width="180" height="48" decoding="async">
</a>
