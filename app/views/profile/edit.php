<h1 class="text-2xl font-bold mb-6">Edit Profile</h1>
<form method="POST" action="<?= url('/profile') ?>" enctype="multipart/form-data" class="max-w-lg space-y-4 glass-card rounded-2xl p-6">
    <?= csrf_field() ?>
    <section class="flex items-center gap-4">
        <img src="<?= avatar_url($user['avatar'] ?? null) ?>" class="w-20 h-20 rounded-2xl object-cover" alt="">
        <div><label class="label">Avatar</label><input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" class="input text-sm"></div>
    </section>
    <section><label class="label">Name</label><input type="text" name="name" value="<?= e($user['name']) ?>" required class="input"></section>
    <section><label class="label">Bio</label><textarea name="bio" rows="3" class="input"><?= e($user['bio'] ?? '') ?></textarea></section>
    <button type="submit" class="btn-primary">Save Profile</button>
</form>
