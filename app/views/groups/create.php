<h1 class="text-2xl font-bold mb-6">Create Study Group</h1>
<form method="POST" action="<?= url('/groups') ?>" class="max-w-lg space-y-4 glass-card rounded-2xl p-6">
    <?= csrf_field() ?>
    <section><label class="label">Group name</label><input type="text" name="name" required class="input" placeholder="CS 301 Study Squad"></section>
    <section><label class="label">Description</label><textarea name="description" rows="3" class="input" placeholder="What will you study together?"></textarea></section>
    <section><label class="label">Color theme</label>
        <div class="select-wrap">
            <select name="color" class="input">
                <?php foreach (['indigo','violet','emerald','sky','rose','amber'] as $c): ?>
                <option value="<?= $c ?>"><?= ucfirst($c) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </section>
    <section><label class="label">Icon</label>
        <div class="select-wrap">
            <select name="icon" class="input">
                <?php foreach (['book-open','code','flask-conical','calculator','palette','globe'] as $i): ?>
                <option value="<?= $i ?>"><?= $i ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </section>
    <button type="submit" class="btn-primary">Create Group</button>
</form>
