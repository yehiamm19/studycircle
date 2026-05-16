<?php
if (!config('demo_quick_login', false)) {
    return;
}
$demoReturn = ($demoReturn ?? 'login') === 'register' ? 'register' : 'login';
?>
<div class="auth-demo-panel mt-8" role="region" aria-labelledby="auth-demo-heading">
    <p id="auth-demo-heading" class="auth-subtitle text-sm text-center mb-4">Demo accounts</p>
    <ul class="auth-demo-list">
        <li class="auth-demo-card auth-demo-card--admin">
            <span class="auth-demo-badge">Administrator</span>
            <dl class="auth-demo-creds">
                <div class="auth-demo-cred-row">
                    <dt>Email</dt>
                    <dd>alex@studycircle.app</dd>
                </div>
                <div class="auth-demo-cred-row">
                    <dt>Password</dt>
                    <dd>password123</dd>
                </div>
            </dl>
            <form class="auth-demo-form" method="POST" action="<?= url('/login/demo-quick') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="_demo_return" value="<?= e($demoReturn) ?>">
                <input type="hidden" name="demo_role" value="admin">
                <button type="submit" class="btn-primary w-full py-2.5 text-sm justify-center">Continue as admin</button>
            </form>
        </li>
        <li class="auth-demo-card auth-demo-card--student">
            <span class="auth-demo-badge">Student</span>
            <dl class="auth-demo-creds">
                <div class="auth-demo-cred-row">
                    <dt>Email</dt>
                    <dd>jordan@studycircle.app</dd>
                </div>
                <div class="auth-demo-cred-row">
                    <dt>Password</dt>
                    <dd>password123</dd>
                </div>
            </dl>
            <form class="auth-demo-form" method="POST" action="<?= url('/login/demo-quick') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="_demo_return" value="<?= e($demoReturn) ?>">
                <input type="hidden" name="demo_role" value="student">
                <button type="submit" class="btn-primary w-full py-2.5 text-sm justify-center">Continue as student</button>
            </form>
        </li>
    </ul>
</div>
