<h1 class="text-2xl font-bold mb-1">Set new password</h1>
<p class="auth-subtitle text-sm mb-7">Choose a strong password for your account</p>
<form method="POST" action="<?= url('/reset/' . $token) ?>" class="space-y-5">
    <?= csrf_field() ?>
    <div>
        <label class="auth-label" for="password">New password</label>
        <div class="auth-input-wrap">
            <input type="password" id="password" name="password" required class="auth-input" placeholder="At least 8 characters" minlength="8" autocomplete="new-password">
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
    <button type="submit" class="btn-primary w-full py-3 text-base">Update password</button>
</form>
