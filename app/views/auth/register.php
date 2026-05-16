<h1 class="text-2xl font-bold mb-1">Create your account</h1>
<p class="auth-subtitle text-sm mb-7">Join thousands of students studying smarter</p>
<form method="POST" action="<?= url('/register') ?>" class="space-y-5">
    <?= csrf_field() ?>
    <div>
        <label class="auth-label" for="name">Full name</label>
        <input type="text" id="name" name="name" value="<?= old('name') ?>" required class="auth-input" placeholder="Alex Morgan" autocomplete="name">
    </div>
    <div>
        <label class="auth-label" for="email">Email</label>
        <input type="email" id="email" name="email" value="<?= old('email') ?>" required class="auth-input" placeholder="you@university.edu" autocomplete="email">
    </div>
    <div>
        <label class="auth-label" for="password">Password</label>
        <div class="auth-input-wrap">
            <input type="password" id="password" name="password" required class="auth-input" placeholder="At least 8 characters" autocomplete="new-password">
            <button type="button" class="auth-toggle-pwd" onclick="togglePasswordVisibility('password', this)" aria-label="Toggle password visibility" tabindex="-1">
                <span data-pwd-eye></span>
            </button>
        </div>
    </div>
    <div>
        <label class="auth-label" for="password_confirmation">Confirm password</label>
        <div class="auth-input-wrap">
            <input type="password" id="password_confirmation" name="password_confirmation" required class="auth-input" placeholder="Repeat password" autocomplete="new-password">
            <button type="button" class="auth-toggle-pwd" onclick="togglePasswordVisibility('password_confirmation', this)" aria-label="Toggle password visibility" tabindex="-1">
                <span data-pwd-eye></span>
            </button>
        </div>
    </div>
    <button type="submit" class="btn-primary w-full py-3 text-base">Create account</button>
</form>
<?php partial('auth-demo-quick', ['demoReturn' => 'register']); ?>
<p class="mt-7 text-center text-sm auth-footer-text">
    Already have an account? <a href="<?= url('/login') ?>" class="auth-link">Sign in</a>
</p>
