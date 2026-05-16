<h1 class="text-2xl font-bold mb-1">Welcome back</h1>
<p class="auth-subtitle text-sm mb-7">Sign in to continue your study journey</p>
<form method="POST" action="<?= url('/login') ?>" class="space-y-5">
    <?= csrf_field() ?>
    <div>
        <label class="auth-label" for="email">Email</label>
        <input type="email" id="email" name="email" value="<?= old('email') ?>" required class="auth-input" placeholder="you@university.edu" autocomplete="email">
    </div>
    <div>
        <label class="auth-label" for="password">Password</label>
        <div class="auth-input-wrap">
            <input type="password" id="password" name="password" required class="auth-input" placeholder="Enter your password" autocomplete="current-password">
            <button type="button" class="auth-toggle-pwd" onclick="togglePasswordVisibility('password', this)" aria-label="Toggle password visibility" tabindex="-1">
                <span data-pwd-eye></span>
            </button>
        </div>
    </div>
    <div class="flex justify-end pt-1">
        <a href="<?= url('/forgot-password') ?>" class="auth-link text-sm">Forgot password?</a>
    </div>
    <button type="submit" class="btn-primary w-full py-3 text-base">Sign in</button>
</form>
<?php partial('auth-demo-quick', ['demoReturn' => 'login']); ?>
<p class="mt-7 text-center text-sm auth-footer-text">
    No account? <a href="<?= url('/register') ?>" class="auth-link">Create one</a>
</p>
