<?php
/** @var array<string,mixed>|null $studyStoryPayload */
$studyStoryPayload = array_merge([
    'buckets' => [],
    'viewerUserId' => (int) (\App\Auth::id() ?? 0),
    'userGroups' => [],
    'defaultGroupId' => null,
    'variant' => 'public',
    'composerRedirect' => '/dashboard',
    /** Frontend fetch base (`/subdir` or "" at domain root); avoids broken `/stories/...` URLs when nested in a folder */
    'appBase' => app_base_path(),
], $studyStoryPayload ?? []);

$storyAudienceLocked = ($studyStoryPayload['variant'] ?? '') === 'group'
    && (int) ($studyStoryPayload['defaultGroupId'] ?? 0) > 0;
$storyLockedGroupId = $storyAudienceLocked ? (int) $studyStoryPayload['defaultGroupId'] : null;
$storyLockedGroupName = '';
if ($storyLockedGroupId !== null) {
    foreach (($studyStoryPayload['userGroups'] ?? []) as $g) {
        if ((int) ($g['id'] ?? 0) === $storyLockedGroupId) {
            $storyLockedGroupName = (string) ($g['name'] ?? '');
            break;
        }
    }
}

$studyStoryBootId = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($studyStoryBootId ?? ''));
if ($studyStoryBootId === '') {
    $studyStoryBootId = 'study-story-' . bin2hex(random_bytes(5));
}

$studyStoryJson = json_encode($studyStoryPayload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE);
if ($studyStoryJson === false) {
    $studyStoryJson = '{"buckets":[],"viewerUserId":0,"userGroups":[]}';
}
?>
<script type="application/json" id="<?= e($studyStoryBootId) ?>" class="study-story-data"><?= $studyStoryJson ?></script>

<section
    class="story-rail-section mb-8 relative z-0"
    x-data="studyStoryRails('<?= e($studyStoryBootId) ?>')"
    @keydown.escape.window="onEscape()"
>
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-4 relative z-10">
        <div>
            <h2 class="text-lg font-bold flex items-center gap-2">
                <span class="story-rail-live-dot"></span>
                <?= isset($storyRailTitle) ? e((string) $storyRailTitle) : 'Study Story' ?>
            </h2>
            <?php if (!empty($storyRailSubtitle)): ?>
            <p class="text-sm text-slate-500 mt-1"><?= e((string) $storyRailSubtitle) ?></p>
            <?php endif; ?>
        </div>
        <div class="flex flex-wrap items-center gap-2 relative z-10">
            <?php if (!empty($storyRailBrowse)): ?>
            <a href="<?= e((string) $storyRailBrowse) ?>" class="text-xs font-semibold text-indigo-500 hover:text-indigo-400 whitespace-nowrap">Explore →</a>
            <?php endif; ?>
            <button type="button" @click.prevent.stop="openComposer()" class="story-share-btn shrink-0">
                <i data-lucide="camera" class="w-4 h-4"></i>
                New Study Story
            </button>
        </div>
    </div>

    <div class="relative z-0">
        <div class="flex gap-4 overflow-x-auto pb-4 story-rail-scrollbar snap-x px-1 -mx-1">
            <button type="button" @click.prevent.stop="openComposer()" class="story-ring-add flex-shrink-0 snap-start">
                <div class="story-ring-add-inner"><i data-lucide="plus" class="w-6 h-6"></i></div>
                <span class="story-ring-label">Add</span>
            </button>
            <template x-for="(bucket, bi) in buckets" :key="bucket.user_id">
                <button type="button"
                    @click.prevent.stop="openViewer(+bi)"
                    class="story-ring-slot flex-shrink-0 snap-start text-left">
                    <div class="story-ring-photo">
                        <img :src="bucket.avatar_url" alt="" width="96" height="96" decoding="async" class="rounded-full">
                    </div>
                    <span class="story-ring-label truncate w-[4.5rem]" x-text="bucket.user_name"></span>
                </button>
            </template>
        </div>

        <p class="story-rail-empty" x-show="buckets.length === 0">
            No Study Stories yet — start with the + button above.
        </p>
    </div>

    <?php /* Composer — teleport + x-show (single teleported root) */ ?>
    <template x-teleport="body">
        <div
            class="story-modal-backdrop"
            x-show="composerOpen"
            x-transition.opacity.duration.200ms
            @click.self="composerOpen = false"
        >
                <div class="story-modal-panel" @click.stop role="document">
                    <div class="story-modal-inner">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-bold">New Study Story</h3>
                            <button type="button" class="story-icon-btn" @click="composerOpen=false" aria-label="Close"><i data-lucide="x"></i></button>
                        </div>
                        <form method="post" action="<?= url('/stories') ?>" enctype="multipart/form-data" class="space-y-4">
                            <?= csrf_field() ?>
                            <input type="hidden" name="_redirect" value="<?= e($storyComposerRedirect ?? '/stories') ?>">

                            <div>
                                <label class="story-field-label">Photo</label>
                                <input required type="file" name="photo" accept="image/jpeg,image/png,image/webp" class="input text-sm py-3 file:mr-3 file:text-sm">
                            </div>

                            <div>
                                <label class="story-field-label">Who can see it</label>
                                <?php if ($storyAudienceLocked && $storyLockedGroupId !== null): ?>
                                    <input type="hidden" name="group_id" value="<?= (int) $storyLockedGroupId ?>">
                                    <p class="text-sm text-slate-600 dark:text-slate-400 rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-800/60 px-3 py-2.5">
                                        <strong class="text-slate-800 dark:text-slate-100">This group only</strong>
                                        <?php if ($storyLockedGroupName !== ''): ?>
                                            <span class="text-slate-500 dark:text-slate-400"> — <?= e($storyLockedGroupName) ?></span>
                                        <?php endif; ?>
                                    </p>
                                <?php else: ?>
                                <select name="group_id" x-model="composeGroupId" class="input">
                                    <option value="">Everyone (campus feed)</option>
                                    <template x-for="g in userGroups" :key="g.id">
                                        <option :value="String(g.id)" x-text="g.name"></option>
                                    </template>
                                </select>
                                <?php endif; ?>
                            </div>

                            <div class="grid sm:grid-cols-2 gap-4">
                                <div class="sm:col-span-1">
                                    <label class="story-field-label">Mood</label>
                                    <div class="select-wrap">
                                        <select name="mood" class="input select-input">
                                            <option value="achievement">Achievement</option>
                                            <option value="task">Task win</option>
                                            <option value="focus">Focus</option>
                                            <option value="general">Just vibes</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="sm:col-span-1">
                                    <label class="story-field-label">Quick line (optional)</label>
                                    <input type="text" name="context_note" maxlength="120" class="input" placeholder="e.g. Done with lab 4">
                                </div>
                            </div>

                            <div>
                                <label class="story-field-label">Caption</label>
                                <textarea name="caption" rows="3" maxlength="220" class="input resize-none" placeholder="Goes away after ~24 hours…"></textarea>
                            </div>

                            <p class="text-xs text-slate-400 flex items-start gap-2 story-modal-hint">
                                <i data-lucide="clock-3" class="w-3.5 h-3.5 mt-0.5 shrink-0 opacity-70"></i>
                                <?php $sm = config('story_upload_max_size'); ?>
                                Max <?= is_numeric($sm) ? round((int) $sm / 1048576, 1) : 5 ?> MB · JPG / PNG / WebP · disappears after ~24h
                            </p>

                            <button type="submit" class="btn-primary w-full py-3">Post Study Story</button>
                        </form>
                    </div>
                </div>
        </div>
    </template>

    <?php /* Full-screen viewer */ ?>
    <template x-teleport="body">
        <div
            class="story-viewer-fixed"
            x-show="viewerOpen"
        >
                <div class="story-viewer-overlay" @click.self="closeViewer()"></div>
                <div class="story-viewer-sheet" role="dialog" aria-modal="true">
                    <div class="story-viewer-toolbar">
                        <div class="flex items-center gap-3 min-w-0 flex-1">
                            <img class="w-11 h-11 rounded-full object-cover ring-2 ring-white/20 shrink-0" :src="activeBucket()?.avatar_url" alt="">
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-bold truncate text-white" x-text="activeBucket()?.user_name"></p>
                                <div class="flex items-center gap-2 text-[11px] text-white/60 flex-wrap">
                                    <span class="story-mood-mini" x-text="activeMoodLabel()"></span>
                                    <span aria-hidden="true">·</span>
                                    <span x-text="timeLeftLabel(activeStory())"></span>
                                </div>
                            </div>
                        </div>
                        <template x-if="canDelete(activeStory())">
                            <button type="button" class="story-viewer-delete" @click.stop="deleteStory(activeStory())" title="Delete"><i data-lucide="trash-2"></i></button>
                        </template>
                        <button type="button" class="story-viewer-close" @click.stop="closeViewer()" aria-label="Close viewer"><i data-lucide="x"></i></button>
                    </div>

                    <div class="story-viewer-progress flex gap-1 px-6 pt-2 pb-4">
                        <template x-for="(_, i) in (activeBucket()?.stories || [])" :key="i">
                            <span class="story-prog-cell" :class="{ 'active': +i === slideIdx, 'done': +i < slideIdx }"><span class="story-prog-fill" :style="+i===slideIdx ? `width:${progress}%` : ''"></span></span>
                        </template>
                    </div>

                    <div class="story-viewer-stage-wrap">
                        <div class="story-viewer-stage" x-ref="storyStageEl" @click="navigateStoryByStrip($event)">
                            <template x-if="activeStory()?.media_url">
                                <img :src="activeStory().media_url" alt="" class="story-view-img" decoding="async">
                            </template>
                            <div class="story-view-gradient"></div>
                            <template x-if="activeStory()?.context_note">
                                <div class="story-context-pill"><span class="truncate" x-text="activeStory().context_note"></span></div>
                            </template>
                            <template x-if="activeStory()?.caption">
                                <p class="story-caption" x-text="activeStory().caption"></p>
                            </template>
                            <button
                                type="button"
                                class="story-edge-nav story-edge-nav--prev"
                                @click.prevent.stop="tapPrevStory()"
                                aria-label="Previous story"
                            >
                                <svg class="story-edge-nav-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" aria-hidden="true">
                                    <path d="m15 6-6 6 6 6" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </button>
                            <button
                                type="button"
                                class="story-edge-nav story-edge-nav--next"
                                @click.prevent.stop="tapNextStory()"
                                aria-label="Next story"
                            >
                                <svg class="story-edge-nav-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" aria-hidden="true">
                                    <path d="m9 6 6 6-6 6" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <p class="story-viewer-views-row" x-show="activeStory()" x-cloak>
                        <svg class="story-viewer-views-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                        <span x-text="activeStoryViewCount()"></span>
                        <span class="story-viewer-views-label">views</span>
                    </p>
                </div>
        </div>
    </template>
</section>
