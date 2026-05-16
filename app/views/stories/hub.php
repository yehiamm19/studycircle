<section class="mb-10">
    <div class="story-hub-hero glass-card rounded-3xl overflow-hidden relative">
        <div class="absolute inset-0 bg-gradient-to-br from-fuchsia-500/20 via-violet-600/25 to-indigo-600/20 pointer-events-none"></div>
        <div class="relative p-6 sm:p-10">
            <p class="text-xs font-bold uppercase tracking-widest text-fuchsia-500 dark:text-fuchsia-400 mb-2">Study Circle</p>
            <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight mb-3">Study Story</h1>
            <p class="text-slate-600 dark:text-slate-300 max-w-2xl text-sm sm:text-base leading-relaxed">
                Share a photo for the next 24 hours — a win, a task, or a focus session — either <strong>for everyone</strong> or inside a <strong>group</strong> only.
            </p>
        </div>
    </div>
</section>

<?php partial('story-rail', [
    'studyStoryPayload' => $studyStoryPayload,
    'storyRailTitle' => 'Explore Study Stories',
    'storyRailSubtitle' => 'Tap a ring to watch the campus feed',
    'storyComposerRedirect' => '/stories',
]); ?>
