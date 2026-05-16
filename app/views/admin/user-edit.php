<section class="mb-6">
    <a href="<?= url('/admin/users') ?>" class="inline-flex items-center gap-2 text-sm text-slate-500 hover:text-rose-500 transition-colors mb-4">
        <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to users
    </a>
    <h1 class="text-2xl font-bold">Edit User</h1>
    <p class="text-sm text-slate-500 mt-1"><?= e($editUser['email']) ?></p>
</section>

<form method="post" action="<?= url('/admin/users/' . $editUser['id']) ?>" class="max-w-2xl">
    <?= csrf_field() ?>
    <div class="glass-card rounded-2xl p-6 space-y-5">
        <div class="grid sm:grid-cols-2 gap-5">
            <div>
                <label class="label" for="name">Full name</label>
                <input type="text" id="name" name="name" value="<?= e($editUser['name']) ?>" required class="input">
            </div>
            <div>
                <label class="label" for="email">Email</label>
                <input type="email" id="email" name="email" value="<?= e($editUser['email']) ?>" required class="input">
            </div>
        </div>

        <div class="grid sm:grid-cols-3 gap-5">
            <div>
                <label class="label" for="role">Role</label>
                <div class="select-wrap">
                    <select id="role" name="role" class="input select-input">
                        <option value="student" <?= $editUser['role'] === 'student' ? 'selected' : '' ?>>Student</option>
                        <option value="admin" <?= $editUser['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="label" for="xp">XP</label>
                <input type="number" id="xp" name="xp" min="0" value="<?= (int) $editUser['xp'] ?>" class="input">
            </div>
            <div>
                <label class="label" for="streak">Streak (days)</label>
                <input type="number" id="streak" name="streak" min="0" value="<?= (int) $editUser['streak'] ?>" class="input">
            </div>
        </div>

        <div>
            <label class="label" for="bio">Bio</label>
            <textarea id="bio" name="bio" rows="3" class="input resize-none"><?= e($editUser['bio'] ?? '') ?></textarea>
        </div>

        <div class="pt-4 border-t border-slate-100 dark:border-white/10">
            <h3 class="text-sm font-semibold text-slate-600 dark:text-slate-300 mb-1">Change password</h3>
            <p class="text-xs text-slate-400 mb-3">Leave blank to keep current password.</p>
            <div class="max-w-xs">
                <div class="input-wrap">
                    <input type="password" id="password" name="password" minlength="8" class="input" placeholder="New password" autocomplete="new-password">
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3 pt-4 border-t border-slate-100 dark:border-white/10">
            <button type="submit" class="btn-primary">Save changes</button>
            <a href="<?= url('/admin/users') ?>" class="px-4 py-2.5 rounded-xl text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-white/10 transition-colors">Cancel</a>
        </div>
    </div>
</form>
