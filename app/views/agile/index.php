<?php
/**
 * Agile workspace — sprints, requirements, traceability, analytics, team roles.
 *
 * @var array $group
 * @var array<int, array<string, mixed>> $members
 * @var array<int, array<string, mixed>> $sprints
 * @var array<int, array<string, mixed>> $useCases
 * @var array<int, array<string, mixed>> $requirements
 * @var array{requirements: array, links: array} $matrix
 * @var array{labels: array, ideal: array, actual: array, scope: float|int} $burndown
 * @var float $velocityAvg
 * @var array<string, mixed>|null $activeSprint
 * @var int $chartSprintId
 * @var float $sprintProgress
 * @var array<string, mixed> $moscowTotals
 * @var bool $canManage
 * @var bool $canRoles
 * @var list<string> $trendLabels
 * @var list<int|float> $trendValues
 */
$gf = url('/groups/' . (int) $group['id']);
$scrums = ['product_owner' => 'Product Owner', 'scrum_master' => 'Scrum Master', 'developer' => 'Team Member'];
$moscowOrder = ['must' => 'Must', 'should' => 'Should', 'could' => 'Could', 'wont' => "Won't"];
$_gid = (int) $group['id'];
?>

<?php partial('group-header', ['group' => $group]); ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<div
    class="space-y-6 agile-workspace-enter"
    x-data="agileWorkspace(<?= (int) $group['id'] ?>, <?= (int) ($canManage ? 1 : 0) ?>, <?= (int) ($canRoles ? 1 : 0) ?>)"
    x-init="initCharts(); $nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); })"
>
    <section class="agile-hero glass-card rounded-3xl p-6 sm:p-8 relative overflow-hidden">
        <div class="absolute inset-0 agile-hero-bg pointer-events-none" aria-hidden="true"></div>
        <div class="relative flex flex-col lg:flex-row lg:items-end gap-6">
            <div class="flex-1 space-y-3">
                <div class="flex flex-col sm:flex-row sm:items-start gap-4">
                    <div class="agile-cockpit-mark shrink-0 inline-flex items-center justify-center w-14 h-14 sm:w-16 sm:h-16 rounded-2xl bg-gradient-to-br from-brand-500/25 to-fuchsia-500/20 border border-brand-500/20 text-brand-600 dark:text-brand-300 shadow-inner" aria-hidden="true">
                        <i data-lucide="orbit" class="w-8 h-8 sm:w-9 sm:h-9"></i>
                    </div>
                    <div class="flex-1 min-w-0 space-y-2">
                        <p class="text-xs font-semibold uppercase tracking-widest text-brand-600 dark:text-brand-400">Agile workspace</p>
                        <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight flex items-center gap-2 flex-wrap">
                            <span>Scrum cockpit</span>
                        </h1>
                        <p class="text-sm text-slate-600 dark:text-slate-400 max-w-xl">
                            Sprints, MoSCoW traceability, velocity, and team roles — wired to your Kanban tasks.
                        </p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2 pt-1">
                    <a href="<?= e($gf . '/tasks') ?>" class="btn-secondary text-sm inline-flex items-center gap-2">
                        <i data-lucide="square-kanban" class="w-4 h-4 shrink-0 stroke-[2.25]"></i> Kanban
                    </a>
                    <a href="<?= e($gf) ?>" class="btn-secondary text-sm inline-flex items-center gap-2">
                        <i data-lucide="home" class="w-4 h-4 shrink-0 stroke-[2.25]"></i> Group home
                    </a>
                </div>
            </div>
            <?php if ($activeSprint): ?>
            <div class="w-full lg:max-w-sm glass-inner rounded-2xl p-4 border border-white/40 dark:border-white/10 shadow-lg">
                <div class="flex items-center gap-2 mb-2">
                    <span class="agile-dot-active"></span>
                    <span class="text-xs font-bold uppercase tracking-wide text-emerald-600 dark:text-emerald-400">Active sprint</span>
                </div>
                <h2 class="font-bold text-lg leading-tight"><?= e($activeSprint['name'] ?? '') ?></h2>
                <p class="text-xs text-slate-500 mt-1"><?= e($activeSprint['start_date'] ?? '') ?> → <?= e($activeSprint['end_date'] ?? '') ?></p>
                <div class="mt-4">
                    <div class="flex justify-between text-xs mb-1">
                        <span>Progress</span>
                        <span class="font-semibold tabular-nums"><?= number_format((float) $sprintProgress, 1) ?>%</span>
                    </div>
                    <div class="agile-progress-track h-3 rounded-full overflow-hidden">
                        <div class="agile-progress-fill h-full rounded-full" style="width: <?= min(100, max(0, (float) $sprintProgress)) ?>%;"></div>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="w-full lg:max-w-sm glass-inner rounded-2xl p-4 border border-dashed border-slate-300/80 dark:border-white/15 text-sm text-slate-500">
                No active sprint yet. <?= $canManage ? 'Activate one from the Sprints tab.' : 'Ask a Scrum Master or Product Owner to activate a sprint.' ?>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <nav class="flex flex-wrap gap-2 agile-tabs" role="tablist">
        <template x-for="t in tabs" :key="t.id">
            <button type="button" role="tab" @click="tab = t.id"
                :class="tab === t.id ? 'agile-tab-active' : 'agile-tab-idle'"
                class="px-4 py-2 rounded-xl text-sm font-semibold transition-all duration-200"
                x-text="t.label"></button>
        </template>
    </nav>

    <!-- Overview -->
    <section x-show="tab === 'overview'" x-cloak class="space-y-4">
        <div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4">
            <div class="agile-metric-card glass-card rounded-2xl p-5">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Avg velocity</p>
                <p class="text-3xl font-extrabold mt-1 tabular-nums text-brand-600 dark:text-brand-400"><?= e((string) $velocityAvg) ?></p>
                <p class="text-xs text-slate-500 mt-2">Story points / last 3 completed sprints</p>
            </div>
            <div class="agile-metric-card glass-card rounded-2xl p-5 sm:col-span-2">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-3">MoSCoW mix (all tasks)</p>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <?php foreach ($moscowOrder as $k => $lab):
                        $v = (int) ($moscowTotals[$k] ?? 0);
                        ?>
                    <div class="text-center p-3 rounded-xl bg-slate-50/80 dark:bg-white/5 border border-slate-200/60 dark:border-white/10">
                        <span class="moscow-pill moscow-<?= e($k) ?> text-[10px] mx-auto mb-1"><?= e($lab) ?></span>
                        <p class="text-2xl font-bold tabular-nums"><?= $v ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="agile-metric-card glass-card rounded-2xl p-5">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Traceability</p>
                <p class="text-3xl font-extrabold mt-1 tabular-nums"><?= count($requirements) ?></p>
                <p class="text-xs text-slate-500 mt-2"><?= count($matrix['links'] ?? []) ?> task links</p>
            </div>
        </div>
    </section>

    <!-- Sprints -->
    <section x-show="tab === 'sprints'" x-cloak class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <h2 class="text-lg font-bold">Sprints</h2>
            <button type="button" x-show="canManage" @click="showSprintModal = true" class="btn-primary text-sm shrink-0">
                <i data-lucide="plus" class="w-4 h-4 inline"></i> New sprint
            </button>
        </div>
        <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-4">
            <?php foreach ($sprints as $sp):
                $pid = (int) $sp['id'];
                $pct = \App\Models\Sprint::progressPercent($pid);
                $st = $sp['status'] ?? 'planned';
                ?>
            <article class="agile-sprint-card glass-card rounded-2xl p-5 border border-slate-200/50 dark:border-white/10 hover:border-brand-400/40 transition-colors duration-300">
                <header class="flex items-start gap-3 mb-3">
                    <span class="sprint-status sprint-status-<?= e($st) ?>"><?= e(ucfirst($st)) ?></span>
                    <div class="flex-1 min-w-0">
                        <h3 class="font-bold leading-snug"><?= e($sp['name']) ?></h3>
                        <p class="text-xs text-slate-500 mt-1"><?= e($sp['start_date']) ?> → <?= e($sp['end_date']) ?></p>
                    </div>
                </header>
                <?php if (trim((string) ($sp['goal'] ?? '')) !== ''): ?>
                <p class="text-sm text-slate-600 dark:text-slate-300 line-clamp-2 mb-3"><?= e($sp['goal']) ?></p>
                <?php endif; ?>
                <div class="space-y-1 mb-4">
                    <div class="flex justify-between text-xs">
                        <span>Completion</span>
                        <span class="tabular-nums font-semibold"><?= number_format($pct, 1) ?>%</span>
                    </div>
                    <div class="agile-progress-track h-2 rounded-full overflow-hidden">
                        <div class="agile-progress-fill h-full rounded-full" style="width: <?= min(100, max(0, $pct)) ?>%;"></div>
                    </div>
                </div>
                <?php if ($canManage): ?>
                <div class="flex flex-wrap gap-2">
                    <?php if ($st !== 'active'): ?>
                    <button type="button" class="btn-primary text-xs py-2 px-3" @click="activateSprint(<?= $pid ?>)">Activate</button>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </article>
            <?php endforeach; ?>
        </div>
        <?php if (count($sprints) === 0): ?>
        <p class="text-sm text-slate-500">No sprints yet. Create your first Scrum iteration.</p>
        <?php endif; ?>

        <div x-show="showSprintModal && canManage" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 modal-backdrop">
            <div @click.away="showSprintModal=false" class="modal-panel glass-card rounded-2xl p-6 w-full max-w-md max-h-[90vh] overflow-y-auto border border-white/20">
                <h3 class="text-lg font-bold mb-4">Create sprint</h3>
                <form @submit.prevent="createSprint()" class="space-y-3">
                    <input x-model="sprintForm.name" required class="input" placeholder="Sprint name">
                    <textarea x-model="sprintForm.goal" class="input" rows="2" placeholder="Sprint goal"></textarea>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-xs text-slate-500">Duration (days)</label>
                            <input type="number" min="1" max="90" x-model.number="sprintForm.duration_days" class="input">
                        </div>
                        <div>
                            <label class="text-xs text-slate-500">Start date</label>
                            <input type="date" x-model="sprintForm.start_date" required class="input">
                        </div>
                    </div>
                    <div class="flex gap-2 pt-2">
                        <button type="submit" class="btn-primary flex-1">Create</button>
                        <button type="button" class="btn-secondary" @click="showSprintModal=false">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- Requirements -->
    <section x-show="tab === 'requirements'" x-cloak class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <h2 class="text-lg font-bold">Requirements</h2>
            <button type="button" x-show="canManage" @click="showReqModal = true" class="btn-primary text-sm">Add requirement</button>
        </div>
        <div class="grid md:grid-cols-2 gap-4">
            <?php foreach ($requirements as $r): ?>
            <article class="req-card glass-card rounded-2xl p-5 border border-slate-200/50 dark:border-white/10">
                <div class="flex gap-3">
                    <div class="font-mono text-xs font-bold text-brand-600 dark:text-brand-400 shrink-0 px-2 py-1 rounded-lg bg-brand-500/10">
                        <?= e($r['requirement_ref']) ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="font-bold text-sm leading-snug"><?= e($r['title']) ?></h3>
                        <p class="text-xs text-slate-500 mt-1">
                            <?= e($r['use_case_title'] ?? 'No use case') ?>
                            <?php if (!empty($r['status'])): ?>
                                · <span class="capitalize"><?= e($r['status']) ?></span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
                <?php $cp = (float) ($r['completion_pct'] ?? 0); ?>
                <div class="mt-4">
                    <div class="flex justify-between text-xs mb-1">
                        <span>Linked tasks completion</span>
                        <span class="tabular-nums font-semibold"><?= number_format($cp, 1) ?>%</span>
                    </div>
                    <div class="agile-progress-track h-2 rounded-full overflow-hidden">
                        <div class="agile-progress-fill h-full rounded-full" style="width: <?= min(100, max(0, $cp)) ?>%;"></div>
                    </div>
                </div>
                <?php if (trim((string) ($r['description'] ?? '')) !== ''): ?>
                <p class="text-xs text-slate-600 dark:text-slate-400 mt-3 line-clamp-3"><?= e($r['description']) ?></p>
                <?php endif; ?>
            </article>
            <?php endforeach; ?>
        </div>
        <?php if (count($requirements) === 0): ?>
            <p class="text-sm text-slate-500">No requirements. Capture engineered scope and tie tasks to IDs.</p>
        <?php endif; ?>

        <div x-show="showReqModal && canManage" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
            <div @click.away="showReqModal=false" class="modal-panel glass-card rounded-2xl p-6 w-full max-w-lg max-h-[90vh] overflow-y-auto">
                <h3 class="text-lg font-bold mb-4">New requirement</h3>
                <form @submit.prevent="createRequirement()" class="space-y-3">
                    <input x-model="reqForm.requirement_ref" required class="input font-mono" placeholder="REQ-101">
                    <input x-model="reqForm.title" required class="input" placeholder="Title">
                    <textarea x-model="reqForm.description" class="input" rows="3" placeholder="Description"></textarea>
                    <div class="select-wrap">
                        <select x-model="reqForm.use_case_id" class="input">
                            <option value="">— Use case —</option>
                            <?php foreach ($useCases as $uc): ?>
                            <option value="<?= (int) $uc['id'] ?>"><?= e($uc['code'] . ' — ' . $uc['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="select-wrap">
                        <select x-model="reqForm.status" class="input">
                            <option value="draft">Draft</option>
                            <option value="active">Active</option>
                            <option value="done">Done</option>
                            <option value="archived">Archived</option>
                        </select>
                    </div>
                    <div class="flex gap-2 pt-2">
                        <button type="submit" class="btn-primary flex-1">Save</button>
                        <button type="button" class="btn-secondary" @click="showReqModal=false">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- Traceability -->
    <section x-show="tab === 'trace'" x-cloak class="space-y-4 overflow-x-auto">
        <h2 class="text-lg font-bold">Traceability matrix</h2>
        <div class="glass-card rounded-2xl border border-slate-200/50 dark:border-white/10 overflow-hidden min-w-[640px]">
            <table class="w-full text-sm agile-trace-table">
                <thead>
                    <tr>
                        <th class="text-left">Requirement</th>
                        <th class="text-left">Use case</th>
                        <th class="text-left">Linked tasks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $reqById = [];
                    foreach ($requirements as $rq) {
                        $reqById[(int) $rq['id']] = $rq;
                    }
                    $linksByReq = [];
                    foreach ($matrix['links'] ?? [] as $ln) {
                        $rid = (int) ($ln['requirement_id'] ?? 0);
                        $linksByReq[$rid][] = $ln;
                    }
                    ?>
                    <?php foreach ($requirements as $rq): $rid = (int) $rq['id']; $links = $linksByReq[$rid] ?? []; ?>
                    <tr class="trace-row-enter">
                        <td class="align-top py-4 px-4">
                            <span class="font-mono font-bold text-brand-600 dark:text-brand-400"><?= e($rq['requirement_ref']) ?></span>
                            <p class="text-slate-600 dark:text-slate-300 mt-1"><?= e($rq['title']) ?></p>
                        </td>
                        <td class="align-top py-4 px-4 text-slate-600 dark:text-slate-400">
                            <?= !empty($rq['use_case_code']) ? e($rq['use_case_code'] . ' · ' . $rq['use_case_title']) : '—' ?>
                        </td>
                        <td class="align-top py-4 px-4">
                            <?php if (!$links): ?>
                                <span class="text-xs text-slate-400">No tasks linked · link from Kanban</span>
                            <?php else: ?>
                                <ul class="space-y-1">
                                    <?php foreach ($links as $ln): ?>
                                    <li>
                                        <a class="text-indigo-600 dark:text-indigo-400 hover:underline" href="<?= e($gf . '/tasks') ?>">
                                            <?= e('#' . (int) $ln['task_id'] . ' ' . ($ln['task_title'] ?? '')) ?>
                                        </a>
                                        <span class="text-xs text-slate-400 capitalize"> · <?= e($ln['task_status'] ?? '') ?></span>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if (count($requirements) === 0): ?>
                <p class="p-6 text-sm text-slate-500">Define requirements first, then associate tasks.</p>
            <?php endif; ?>
        </div>
    </section>

    <!-- Analytics -->
    <section x-show="tab === 'analytics'" x-cloak class="space-y-6">
        <div class="flex flex-col lg:flex-row gap-4 lg:items-end lg:justify-between">
            <div>
                <h2 class="text-lg font-bold">Analytics</h2>
                <p class="text-xs text-slate-500 mt-1">Burndown & throughput (last 28 days)</p>
            </div>
            <div class="select-wrap max-w-xs">
                <label class="text-xs text-slate-500 block mb-1">Sprint for burndown</label>
                <?php if (count($sprints)): ?>
                <select class="input" x-model.number="chartSprintId" @change="reloadBurndown()">
                    <?php foreach ($sprints as $sp): ?>
                    <option value="<?= (int) $sp['id'] ?>"><?= e($sp['name']) ?> (<?= e($sp['status']) ?>)</option>
                    <?php endforeach; ?>
                </select>
                <?php else: ?>
                <p class="text-xs text-slate-500">Create a sprint to plot burndown.</p>
                <?php endif; ?>
            </div>
        </div>
        <div class="grid xl:grid-cols-5 gap-4">
            <div class="xl:col-span-3 glass-card rounded-2xl p-5 border border-slate-200/50 dark:border-white/10">
                <h3 class="text-sm font-bold mb-4">Sprint burndown <span class="text-slate-400 font-normal" x-show="liveProgress>=0"><span class="tabular-nums" x-text="liveProgress.toFixed(1)"></span>% done</span></h3>
                <div class="h-72">
                    <canvas id="chart-burndown"></canvas>
                </div>
            </div>
            <div class="xl:col-span-2 glass-card rounded-2xl p-5 border border-slate-200/50 dark:border-white/10">
                <h3 class="text-sm font-bold mb-4">Daily completions</h3>
                <div class="h-72">
                    <canvas id="chart-trend"></canvas>
                </div>
            </div>
        </div>
    </section>

    <!-- Team roles -->
    <section x-show="tab === 'team'" x-cloak class="space-y-6">
        <h2 class="text-lg font-bold">Scrum roles</h2>
        <p class="text-sm text-slate-500">Product Owner, Scrum Master, and developers. <?= $canRoles ? '' : 'Only owners and admins assign roles.' ?></p>
        <ul class="space-y-3">
            <?php foreach ($members as $m): $sr = \App\Models\Group::sanitizeScrumRole((string) ($m['scrum_role'] ?? 'developer')); ?>
            <li class="flex flex-col sm:flex-row sm:items-center gap-4 glass-card rounded-2xl p-4 border border-slate-200/50 dark:border-white/10">
                <img src="<?= avatar_url($m['avatar'] ?? null) ?>" class="w-12 h-12 rounded-full" alt="">
                <div class="flex-1 min-w-0">
                    <p class="font-semibold"><?= e($m['name']) ?> <span class="text-xs text-slate-400 capitalize font-normal">(<?= e($m['role'] ?? '') ?>)</span></p>
                    <span class="scrum-role-badge scrum-role-<?= e($sr) ?>"><?= e($scrums[$sr] ?? $sr) ?></span>
                </div>
                <?php if ($canRoles && ($m['role'] ?? '') !== 'owner'): ?>
                <div class="select-wrap shrink-0 w-full sm:w-48">
                    <label class="text-xs text-slate-500">Scrum role</label>
                    <select class="input text-sm" data-user="<?= (int) $m['id'] ?>" @change="setScrumRole($event.target.dataset.user, $event.target.value)">
                        <?php foreach ($scrums as $val => $lab): ?>
                        <option value="<?= e($val) ?>" <?= $sr === $val ? 'selected' : '' ?>><?= e($lab) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ul>

        <?php if ($canManage): ?>
        <div class="glass-card rounded-2xl p-5 border border-slate-200/50 dark:border-white/10">
            <h3 class="text-sm font-bold mb-3">Use case catalog</h3>
            <form @submit.prevent="createUseCase()" class="flex flex-col sm:flex-row gap-3 items-end flex-wrap">
                <input x-model="ucForm.code" required class="input sm:w-32 font-mono uppercase" placeholder="UC-01">
                <input x-model="ucForm.title" required class="input flex-1 min-w-[200px]" placeholder="Title">
                <button type="submit" class="btn-primary text-sm">Add use case</button>
            </form>
        </div>
        <?php endif; ?>
    </section>
</div>

<script>
(function() {
    const burndownLabels = <?= json_encode($burndown['labels'] ?? [], JSON_THROW_ON_ERROR) ?>;
    const burndownIdeal = <?= json_encode($burndown['ideal'] ?? [], JSON_THROW_ON_ERROR) ?>;
    const burndownActual = <?= json_encode($burndown['actual'] ?? [], JSON_THROW_ON_ERROR) ?>;
    const trendLabels = <?= json_encode($trendLabels, JSON_THROW_ON_ERROR) ?>;
    const trendValues = <?= json_encode($trendValues, JSON_THROW_ON_ERROR) ?>;

    window.agileWorkspace = function(groupId, canManage, canRoles) {
        return {
            groupId,
            canManage: !!canManage,
            canRoles: !!canRoles,
            tab: 'overview',
            tabs: [
                { id: 'overview', label: 'Overview' },
                { id: 'sprints', label: 'Sprints' },
                { id: 'requirements', label: 'Requirements' },
                { id: 'trace', label: 'Traceability' },
                { id: 'analytics', label: 'Analytics' },
                { id: 'team', label: 'Team' },
            ],
            showSprintModal: false,
            showReqModal: false,
            sprintForm: { name: '', goal: '', duration_days: 14, start_date: new Date().toISOString().slice(0, 10) },
            reqForm: { requirement_ref: '', title: '', description: '', use_case_id: '', status: 'active' },
            ucForm: { code: '', title: '', description: '' },
            chartSprintId: <?= (int) $chartSprintId ?>,
            liveProgress: <?= (float) $sprintProgress ?>,
            _bdChart: null,
            _trChart: null,

            initCharts() {
                this.$nextTick(() => {
                    this.renderBurndown(burndownLabels, burndownIdeal, burndownActual);
                    this.renderTrend(trendLabels, trendValues);
                });
            },

            renderBurndown(labels, ideal, actual) {
                const el = document.getElementById('chart-burndown');
                if (!el || typeof Chart === 'undefined') return;
                if (this._bdChart) { this._bdChart.destroy(); this._bdChart = null; }
                const ctx = el.getContext('2d');
                const g1 = ctx.createLinearGradient(0, 0, 0, 280);
                g1.addColorStop(0, 'rgba(99, 102, 241, 0.35)');
                g1.addColorStop(1, 'rgba(99, 102, 241, 0)');
                const g2 = ctx.createLinearGradient(0, 0, 0, 280);
                g2.addColorStop(0, 'rgba(16, 185, 129, 0.4)');
                g2.addColorStop(1, 'rgba(16, 185, 129, 0)');
                this._bdChart = new Chart(el, {
                    type: 'line',
                    data: {
                        labels,
                        datasets: [
                            {
                                label: 'Ideal remaining',
                                data: ideal,
                                tension: 0.35,
                                borderWidth: 2,
                                borderColor: '#818cf8',
                                fill: true,
                                backgroundColor: g1,
                                pointRadius: 0,
                            },
                            {
                                label: 'Actual remaining',
                                data: actual,
                                tension: 0.35,
                                borderWidth: 2,
                                borderColor: '#34d399',
                                fill: true,
                                backgroundColor: g2,
                                pointRadius: 2,
                            },
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { labels: { color: '#94a3b8' } },
                            tooltip: {
                                intersect: false,
                                mode: 'index',
                                animation: { duration: 180 },
                            },
                        },
                        animation: { duration: 900, easing: 'easeOutQuart' },
                        scales: {
                            x: { ticks: { color: '#64748b', maxRotation: 0 }, grid: { color: 'rgba(148,163,184,.12)' } },
                            y: { beginAtZero: true, ticks: { color: '#64748b' }, grid: { color: 'rgba(148,163,184,.12)' } },
                        },
                    },
                });
            },

            renderTrend(labels, values) {
                const el = document.getElementById('chart-trend');
                if (!el || typeof Chart === 'undefined') return;
                if (this._trChart) { this._trChart.destroy(); this._trChart = null; }
                const g = el.getContext('2d').createLinearGradient(0, 280, 0, 0);
                g.addColorStop(0, 'rgba(14,165,233,0.08)');
                g.addColorStop(1, 'rgba(99,102,241,0.45)');
                this._trChart = new Chart(el, {
                    type: 'bar',
                    data: {
                        labels,
                        datasets: [{
                            label: 'Tasks completed',
                            data: values,
                            borderRadius: 8,
                            backgroundColor: g,
                            borderColor: 'rgba(99,102,241,0.6)',
                            borderWidth: { top: 0, bottom: 0, left: 0, right: 0 },
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        animation: { duration: 800, easing: 'easeOutExpo' },
                        scales: {
                            x: { ticks: { color: '#64748b', maxRotation: 45, autoSkip: true, maxTicksLimit: 14 }, grid: { display: false } },
                            y: { beginAtZero: true, ticks: { stepSize: 1, color: '#64748b' }, grid: { color: 'rgba(148,163,184,.12)' } },
                        },
                    },
                });
            },

            async reloadBurndown() {
                if (!this.chartSprintId) return;
                    const base = window.APP_URL || '';
                    const r = await fetch(`${base}/groups/${this.groupId}/agile/burndown?sprint_id=${this.chartSprintId}`, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    const d = await r.json().catch(() => ({}));
                    if (!r.ok) throw new Error(d.error || 'Failed');
                    this.liveProgress = d.progress ?? 0;
                    this.renderBurndown(d.labels || [], d.ideal || [], d.actual || []);
                } catch (e) { showToast(e.message, 'error'); }
            },

            async createSprint() {
                if (!this.canManage) return;
                const fd = new FormData();
                fd.append('_csrf', typeof CSRF !== 'undefined' ? CSRF : '');
                fd.append('name', this.sprintForm.name);
                fd.append('goal', this.sprintForm.goal);
                fd.append('duration_days', String(this.sprintForm.duration_days));
                fd.append('start_date', this.sprintForm.start_date);
                try {
                    await api(`${window.APP_URL}/groups/${this.groupId}/sprints`, { method: 'POST', body: fd });
                    this.showSprintModal = false;
                    showToast('Sprint created');
                    location.reload();
                } catch (e) { showToast(e.message, 'error'); }
            },

            async activateSprint(id) {
                if (!this.canManage) return;
                const fd = new FormData();
                fd.append('_csrf', typeof CSRF !== 'undefined' ? CSRF : '');
                try {
                    await api(`${window.APP_URL}/sprints/${id}/activate`, { method: 'POST', body: fd });
                    showToast('Sprint activated');
                    location.reload();
                } catch (e) { showToast(e.message, 'error'); }
            },

            async createRequirement() {
                if (!this.canManage) return;
                const fd = new FormData();
                fd.append('_csrf', typeof CSRF !== 'undefined' ? CSRF : '');
                fd.append('requirement_ref', this.reqForm.requirement_ref);
                fd.append('title', this.reqForm.title);
                fd.append('description', this.reqForm.description || '');
                if (this.reqForm.use_case_id) fd.append('use_case_id', this.reqForm.use_case_id);
                fd.append('status', this.reqForm.status);
                try {
                    await api(`${window.APP_URL}/groups/${this.groupId}/requirements`, { method: 'POST', body: fd });
                    this.showReqModal = false;
                    showToast('Requirement saved');
                    location.reload();
                } catch (e) { showToast(e.message, 'error'); }
            },

            async createUseCase() {
                if (!this.canManage) return;
                const fd = new FormData();
                fd.append('_csrf', typeof CSRF !== 'undefined' ? CSRF : '');
                fd.append('code', this.ucForm.code);
                fd.append('title', this.ucForm.title);
                fd.append('description', this.ucForm.description || '');
                try {
                    await api(`${window.APP_URL}/groups/${this.groupId}/use-cases`, { method: 'POST', body: fd });
                    this.ucForm = { code: '', title: '', description: '' };
                    showToast('Use case added');
                    location.reload();
                } catch (e) { showToast(e.message, 'error'); }
            },

            async setScrumRole(userId, scrumRole) {
                if (!this.canRoles) return;
                const fd = new FormData();
                fd.append('_csrf', typeof CSRF !== 'undefined' ? CSRF : '');
                fd.append('user_id', String(userId));
                fd.append('scrum_role', scrumRole);
                try {
                    await api(`${window.APP_URL}/groups/${this.groupId}/members/scrum-role`, { method: 'POST', body: fd });
                    showToast('Role updated');
                } catch (e) { showToast(e.message, 'error'); }
            },
        };
    };
})();
</script>
<script>lucide.createIcons();</script>
