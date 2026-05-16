<h1 class="text-2xl font-bold mb-1">Reset password</h1>
<p class="auth-subtitle text-sm mb-7">We'll send you a reset link if the email exists</p>
<?php if ($link = $_SESSION['reset_link'] ?? null): unset($_SESSION['reset_link']); ?>
<div class="mb-5 p-4 rounded-xl auth-demo-box text-xs break-all">
    <span class="text-slate-400 block mb-1">Demo reset link</span>
    <a href="<?= e($link) ?>" class="auth-link underline"><?= e($link) ?></a>
</div>
<?php endif; ?>
<form method="POST" action="<?= url('/forgot-password') ?>" class="space-y-5">
    <?= csrf_field() ?>
    <div>
        <label class="auth-label" for="email">Email</label>
        <input type="email" id="email" name="email" required class="auth-input" placeholder="you@university.edu" autocomplete="email">
    </div>
    <button type="submit" class="btn-primary w-full py-3 text-base">Send reset link</button>
</form>
<p class="mt-7 text-center text-sm auth-footer-text">
    <a href="<?= url('/login') ?>" class="auth-link">← Back to sign in</a>
</p>
