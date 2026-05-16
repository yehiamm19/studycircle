<h1 class="text-2xl font-bold mb-6">Edit Group</h1>
<form method="POST" action="<?= url('/groups/' . $group['id']) ?>" class="max-w-lg space-y-4 glass-card rounded-2xl p-6">
    <?= csrf_field() ?>
    <section><label class="label">Group name</label><input type="text" name="name" value="<?= e($group['name']) ?>" required class="input"></section>
    <section><label class="label">Description</label><textarea name="description" rows="3" class="input"><?= e($group['description']) ?></textarea></section>
    <section><label class="label">Color</label><input type="text" name="color" value="<?= e($group['color']) ?>" class="input"></section>
    <section><label class="label">Icon</label><input type="text" name="icon" value="<?= e($group['icon']) ?>" class="input"></section>
    <div class="flex gap-3">
        <button type="submit" class="btn-primary">Save Changes</button>
        <a href="<?= url('/groups/' . $group['id']) ?>" class="btn-secondary">Cancel</a>
    </div>
</form>
<form method="POST" action="<?= url('/groups/' . $group['id'] . '/delete') ?>" class="mt-4" onsubmit="return confirm('Delete this group permanently?')">
    <?= csrf_field() ?>
    <button type="submit" class="text-sm text-rose-500 hover:text-rose-400">Delete group</button>
</form>
