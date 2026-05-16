<section class="mb-6">
    <a href="<?= url('/admin/users') ?>" class="inline-flex items-center gap-2 text-sm text-slate-500 hover:text-rose-500 transition-colors mb-4">
        <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to users
    </a>
    <h1 class="text-2xl font-bold">Create User</h1>
    <p class="text-sm text-slate-500 mt-1">Add a new user account</p>
</section>

<form method="post" action="<?= url('/admin/users/create') ?>" class="max-w-2xl">
    <?= csrf_field() ?>
    <div class="glass-card rounded-2xl p-6 space-y-5">
        <div class="grid sm:grid-cols-2 gap-5">
            <div>
                <label class="label" for="name">Full name</label>
                <input type="text" id="name" name="name" required class="input" placeholder="e.g. Alex Morgan">
            </div>
            <div>
                <label class="label" for="email">Email</label>
                <input type="email" id="email" name="email" required class="input" placeholder="you@university.edu">
            </div>
        </div>

        <div class="grid sm:grid-cols-2 gap-5">
            <div>
                <label class="label" for="password">Password</label>
                <div class="input-wrap">
                    <input type="password" id="password" name="password" required minlength="8" class="input" placeholder="At least 8 characters" autocomplete="new-password">
                </div>
            </div>
            <div>
                <label class="label" for="role">Role</label>
                <div class="select-wrap">
                    <select id="role" name="role" class="input select-input">
                        <option value="student" selected>Student</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3 pt-2 border-t border-slate-100 dark:border-white/10">
            <button type="submit" class="btn-primary">Create user</button>
            <a href="<?= url('/admin/users') ?>" class="px-4 py-2.5 rounded-xl text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-white/10 transition-colors">Cancel</a>
        </div>
    </div>
</form>
