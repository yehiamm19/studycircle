<?php
$gf = url('/groups/' . (int) $group['id']);
$moscowOrder = ['must' => 'M', 'should' => 'S', 'could' => 'C', 'wont' => 'W'];
$sf = $board_filters['sprint_id'] ?? null;
$sprintQ = '';
if ($sf === 'none') {
    $sprintQ = 'backlog';
} elseif ($sf !== null && $sf !== '') {
    $sprintQ = (string) $sf;
}
$mf = $board_filters['moscow_priority'] ?? '';
$filterActive = $sf !== null || $mf !== '';
?>
<?php partial('group-header', ['group' => $group]); ?>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>

<div x-data="kanban(<?= (int) $group['id'] ?>, <?= htmlspecialchars(json_encode($columns), ENT_QUOTES) ?>, {
    sortDisabled: <?= $filterActive ? 'true' : 'false' ?>,
    sprints: <?= htmlspecialchars(json_encode($sprints ?? []), ENT_QUOTES) ?>,
    requirements: <?= htmlspecialchars(json_encode($requirements ?? []), ENT_QUOTES) ?>,
    moscowByColumn: <?= htmlspecialchars(json_encode($moscow_by_column ?? []), ENT_QUOTES) ?>,
})" class="space-y-4">
    <div class="flex flex-col lg:flex-row justify-between gap-4">
        <div class="flex items-start gap-3 flex-1 min-w-0">
            <span class="kanban-page-mark shrink-0 inline-flex rounded-xl bg-indigo-500/14 dark:bg-indigo-500/20 border border-indigo-500/15 p-2.5 text-indigo-600 dark:text-indigo-300" aria-hidden="true">
                <i data-lucide="square-kanban" class="w-6 h-6 stroke-[2]"></i>
            </span>
            <div class="space-y-1 min-w-0">
            <p class="text-slate-700 dark:text-slate-200 text-sm font-semibold leading-snug">Kanban board</p>
            <p class="text-slate-500 text-sm">Drag tasks between columns · Click a card to edit</p>
            <p x-show="sortDisabled" x-cloak class="text-xs text-amber-600 dark:text-amber-400">
                Drag is paused while filters are on — clear filters to reorder every task safely.
            </p>
            </div>
        </div>
        <div class="flex flex-col sm:flex-row gap-3 sm:items-center">
            <a href="<?= e($gf . '/agile') ?>" class="btn-secondary text-sm inline-flex items-center justify-center gap-2 shrink-0">
                <i data-lucide="orbit" class="w-4 h-4 shrink-0 stroke-[2.25]" aria-hidden="true"></i> Agile workspace
            </a>
            <button @click="showCreate=true" class="btn-primary shrink-0"><i data-lucide="plus" class="w-4 h-4"></i> Add Task</button>
        </div>
    </div>

    <form method="get" class="glass-card rounded-2xl p-4 flex flex-col sm:flex-row flex-wrap gap-3 items-stretch sm:items-end" x-ref="boardFilter">
        <div class="flex-1 min-w-[160px]">
            <label class="text-xs font-semibold text-slate-500 block mb-1">Sprint</label>
            <div class="select-wrap">
                <select name="sprint" class="input text-sm" onchange="this.form.submit()">
                    <option value="">All tasks</option>
                    <option value="backlog" <?= $sprintQ === 'backlog' ? 'selected' : '' ?>>Backlog (no sprint)</option>
                    <?php foreach ($sprints ?? [] as $sp): ?>
                    <option value="<?= (int) $sp['id'] ?>" <?= $sprintQ !== '' && $sprintQ === (string) $sp['id'] ? 'selected' : '' ?>>
                        <?= e($sp['name']) ?> (<?= e($sp['status'] ?? '') ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="flex-1 min-w-[160px]">
            <label class="text-xs font-semibold text-slate-500 block mb-1">MoSCoW</label>
            <div class="select-wrap">
                <select name="moscow" class="input text-sm" onchange="this.form.submit()">
                    <option value="">All priorities</option>
                    <option value="must" <?= $mf === 'must' ? 'selected' : '' ?>>Must have</option>
                    <option value="should" <?= $mf === 'should' ? 'selected' : '' ?>>Should have</option>
                    <option value="could" <?= $mf === 'could' ? 'selected' : '' ?>>Could have</option>
                    <option value="wont" <?= $mf === 'wont' ? 'selected' : '' ?>>Won't have</option>
                </select>
            </div>
        </div>
        <?php if ($filterActive): ?>
        <a href="<?= e($gf . '/tasks') ?>" class="btn-secondary text-sm h-10 self-end">Clear filters</a>
        <?php endif; ?>
    </form>

    <div class="grid md:grid-cols-3 gap-4 overflow-x-auto pb-4">
        <?php
        $colMeta = [
            'todo' => ['To Do', 'slate'],
            'in_progress' => ['In Progress', 'amber'],
            'completed' => ['Completed', 'emerald'],
        ];
        foreach ($colMeta as $status => [$label, $color]):
            $mc = $moscow_by_column[$status] ?? ['must' => 0, 'should' => 0, 'could' => 0, 'wont' => 0];
        ?>
        <section class="glass-card rounded-2xl p-4 min-w-[280px] kanban-col-animate">
            <header class="flex items-center gap-2 mb-4 flex-wrap">
                <span class="w-2 h-2 rounded-full bg-<?= $color ?>-500"></span>
                <h3 class="font-semibold text-sm"><?= $label ?></h3>
                <span class="ml-auto text-xs text-slate-400" x-text="(columns['<?= $status ?>']||[]).length"></span>
            </header>
            <div class="flex flex-wrap gap-1 mb-3 min-h-[24px]">
                <?php foreach ($moscowOrder as $k => $short): $cn = (int) ($mc[$k] ?? 0); ?>
                <span class="moscow-count-pill moscow-<?= e($k) ?>" title="<?= e($k) ?>"><?= e($short) ?> <strong><?= $cn ?></strong></span>
                <?php endforeach; ?>
            </div>
            <div id="col-<?= $status ?>" class="kanban-column space-y-3 min-h-[120px]" data-status="<?= $status ?>">
                <template x-for="task in columns['<?= $status ?>']" :key="task.id">
                    <article class="task-card glass-card rounded-xl p-4 cursor-pointer border border-slate-200/50 dark:border-white/5 task-card-enter"
                        :data-id="task.id" @click="openTask(task)">
                        <div class="flex items-start justify-between gap-2 mb-2">
                            <h4 class="font-semibold text-sm leading-snug flex-1" x-text="task.title"></h4>
                            <div class="flex flex-col items-end gap-1 shrink-0">
                                <span class="moscow-pill" :class="'moscow-' + (task.moscow_priority || 'could')" x-text="moscowLabel(task.moscow_priority)"></span>
                                <span class="badge bg-rose-500/10 text-rose-600 text-[10px]" x-show="task.priority==='high'" x-text="task.priority"></span>
                            </div>
                        </div>
                        <p class="text-xs text-slate-500 line-clamp-2 mb-3" x-text="task.description"></p>
                        <div class="flex flex-wrap gap-1 mb-2" x-show="task.sprint_name || (task.requirement_refs && String(task.requirement_refs).length)">
                            <span class="text-[10px] px-2 py-0.5 rounded-lg bg-sky-500/15 text-sky-700 dark:text-sky-300 font-medium" x-show="task.sprint_name" x-text="task.sprint_name"></span>
                            <span class="text-[10px] px-2 py-0.5 rounded-lg bg-violet-500/15 text-violet-700 dark:text-violet-300 font-mono truncate max-w-full" x-show="task.requirement_refs" x-text="task.requirement_refs"></span>
                        </div>
                        <footer class="flex items-center justify-between text-xs gap-2">
                            <span class="badge bg-indigo-500/10 text-indigo-600 shrink-0" x-text="task.label"></span>
                            <span class="tabular-nums text-slate-500 font-medium" x-text="(task.story_points != null ? task.story_points : 1) + ' pt'"></span>
                            <span x-show="task.due_date" class="text-slate-400 shrink-0" x-text="task.due_date"></span>
                            <img x-show="task.assignee_avatar" :src="'<?= url('uploads/avatars/') ?>'+task.assignee_avatar" class="w-6 h-6 rounded-full shrink-0" alt="">
                        </footer>
                    </article>
                </template>
            </div>
        </section>
        <?php endforeach; ?>
    </div>

    <!-- Create modal -->
    <div x-show="showCreate" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 modal-backdrop bg-black/50" @keydown.escape.window="showCreate=false">
        <div @click.away="showCreate=false" class="modal-panel glass-card rounded-2xl p-6 w-full max-w-lg max-h-[90vh] overflow-y-auto">
            <h3 class="text-lg font-bold mb-4">New Task</h3>
            <form @submit.prevent="createTask()" class="space-y-3">
                <input x-model="form.title" required class="input" placeholder="Task title">
                <textarea x-model="form.description" class="input" rows="2" placeholder="Description"></textarea>
                <div class="grid grid-cols-2 gap-3">
                    <div class="select-wrap"><select x-model="form.priority" class="input">
                        <option value="low">Low</option><option value="medium">Medium</option><option value="high">High</option>
                    </select></div>
                    <div class="select-wrap"><select x-model="form.moscow_priority" class="input">
                        <option value="must">Must have</option>
                        <option value="should">Should have</option>
                        <option value="could">Could have</option>
                        <option value="wont">Won't have</option>
                    </select></div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs text-slate-500">Story points</label>
                        <input type="number" step="0.5" min="0" x-model.number="form.story_points" class="input">
                    </div>
                    <div class="select-wrap"><select x-model="form.sprint_id" class="input">
                        <option value="">No sprint</option>
                        <?php foreach ($sprints ?? [] as $sp): ?>
                        <option value="<?= (int) $sp['id'] ?>"><?= e($sp['name']) ?></option>
                        <?php endforeach; ?>
                    </select></div>
                </div>
                <div class="select-wrap"><select x-model="form.label" class="input">
                    <option value="homework">Homework</option><option value="exam">Exam</option><option value="reading">Reading</option><option value="project">Project</option>
                </select></div>
                <input type="date" x-model="form.due_date" class="input">
                <div class="select-wrap"><select x-model="form.assignee_id" class="input">
                    <option value="">Unassigned</option>
                    <?php foreach ($members as $m): ?>
                    <option value="<?= $m['id'] ?>"><?= e($m['name']) ?></option>
                    <?php endforeach; ?>
                </select></div>
                <div class="border border-slate-200/60 dark:border-white/10 rounded-xl p-3 max-h-36 overflow-y-auto">
                    <p class="text-xs font-semibold text-slate-500 mb-2">Link requirements</p>
                    <?php foreach ($requirements ?? [] as $r): ?>
                    <label class="flex items-center gap-2 text-xs py-1 cursor-pointer">
                        <input type="checkbox" value="<?= (int) $r['id'] ?>" x-model="form.requirement_ids" class="rounded border-slate-300">
                        <span class="font-mono text-brand-600 dark:text-brand-400"><?= e($r['requirement_ref']) ?></span>
                        <span class="truncate"><?= e($r['title']) ?></span>
                    </label>
                    <?php endforeach; ?>
                    <?php if (empty($requirements)): ?>
                    <p class="text-xs text-slate-400">Create requirements in Agile workspace.</p>
                    <?php endif; ?>
                </div>
                <div class="flex gap-2 pt-2">
                    <button type="submit" class="btn-primary flex-1">Create</button>
                    <button type="button" @click="showCreate=false" class="btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit modal -->
    <div x-show="activeTask" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 modal-backdrop bg-black/50">
        <div @click.away="activeTask=null" class="modal-panel glass-card rounded-2xl p-6 w-full max-w-lg max-h-[90vh] overflow-y-auto">
            <template x-if="activeTask">
                <div>
                    <input x-model="activeTask.title" class="input text-lg font-bold mb-3">
                    <textarea x-model="activeTask.description" class="input mb-3" rows="3"></textarea>
                    <div class="grid grid-cols-2 gap-3 mb-4">
                        <div class="select-wrap"><select x-model="activeTask.priority" class="input"><option value="low">Low</option><option value="medium">Medium</option><option value="high">High</option></select></div>
                        <div class="select-wrap"><select x-model="activeTask.moscow_priority" class="input">
                            <option value="must">Must</option><option value="should">Should</option><option value="could">Could</option><option value="wont">Won't</option>
                        </select></div>
                        <div>
                            <label class="text-xs text-slate-500">Points</label>
                            <input type="number" step="0.5" min="0" x-model.number="activeTask.story_points" class="input">
                        </div>
                        <div class="select-wrap"><select x-model="activeTask.sprint_id" class="input">
                            <option value="">No sprint</option>
                            <?php foreach ($sprints ?? [] as $sp): ?>
                            <option value="<?= (int) $sp['id'] ?>"><?= e($sp['name']) ?></option>
                            <?php endforeach; ?>
                        </select></div>
                        <div class="select-wrap col-span-2"><select x-model="activeTask.label" class="input"><option value="homework">Homework</option><option value="exam">Exam</option><option value="reading">Reading</option><option value="project">Project</option></select></div>
                        <input type="date" x-model="activeTask.due_date" class="input col-span-2">
                        <div class="select-wrap col-span-2"><select x-model="activeTask.assignee_id" class="input">
                            <option value="">Unassigned</option>
                            <?php foreach ($members as $m): ?>
                            <option value="<?= $m['id'] ?>"><?= e($m['name']) ?></option>
                            <?php endforeach; ?>
                        </select></div>
                    </div>
                    <div class="border border-slate-200/60 dark:border-white/10 rounded-xl p-3 max-h-32 overflow-y-auto mb-4">
                        <p class="text-xs font-semibold text-slate-500 mb-2">Requirements</p>
                        <?php foreach ($requirements ?? [] as $r): ?>
                        <label class="flex items-center gap-2 text-xs py-1 cursor-pointer">
                            <input type="checkbox" value="<?= (int) $r['id'] ?>" :checked="isReqChecked(<?= (int) $r['id'] ?>)" @change="toggleReq(<?= (int) $r['id'] ?>, $event.target.checked)" class="rounded border-slate-300">
                            <span class="font-mono text-brand-600"><?= e($r['requirement_ref']) ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <section class="mb-4">
                        <h4 class="text-sm font-semibold mb-2">Comments</h4>
                        <div class="space-y-2 max-h-32 overflow-y-auto mb-2" id="task-comments"></div>
                        <form @submit.prevent="addComment()" class="flex gap-2">
                            <input x-model="commentBody" class="input flex-1" placeholder="Add a comment...">
                            <button type="submit" class="btn-secondary">Post</button>
                        </form>
                    </section>
                    <div class="flex gap-2">
                        <button @click="saveTask()" class="btn-primary flex-1">Save</button>
                        <button @click="deleteTask()" class="text-rose-500 text-sm px-3">Delete</button>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>

<script src="<?= asset('js/kanban.js') ?>"></script>
