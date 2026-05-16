<section class="mb-6">
    <h1 class="text-2xl font-bold">Activity Log</h1>
    <p class="text-sm text-slate-500 mt-1">Last 100 platform events</p>
</section>

<div class="glass-card rounded-2xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="admin-table w-full text-sm">
            <thead>
                <tr>
                    <th>Action</th>
                    <th>User</th>
                    <th>Details</th>
                    <th>When</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($activities)): ?>
                <tr><td colspan="4" class="text-center py-12 text-slate-500">No activity yet.</td></tr>
                <?php else: foreach ($activities as $a):
                    $meta = json_decode($a['meta'] ?? '{}', true) ?: [];
                ?>
                <tr>
                    <td>
                        <span class="inline-flex items-center gap-2 font-medium capitalize">
                            <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                            <?= e(str_replace('_', ' ', $a['action'])) ?>
                        </span>
                    </td>
                    <td>
                        <p class="font-medium"><?= e($a['user_name'] ?? '—') ?></p>
                        <p class="text-xs text-slate-400"><?= e($a['user_email'] ?? '') ?></p>
                    </td>
                    <td class="text-slate-500 max-w-xs truncate">
                        <?php if ($meta): ?>
                        <code class="text-xs"><?= e(json_encode($meta, JSON_UNESCAPED_UNICODE)) ?></code>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td class="text-slate-400 whitespace-nowrap"><?= time_ago($a['created_at']) ?></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
