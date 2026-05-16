<dialog id="achievement-share-dialog" class="achievement-share-dialog">
    <div class="achievement-share-dialog-panel">
        <div class="flex items-start justify-between gap-3 mb-4">
            <div>
                <h3 class="font-bold text-lg text-slate-900 dark:text-white">Share achievement</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Vertical image — great for Study Story or social.</p>
            </div>
            <button type="button" class="achievement-share-close" data-achievement-share-close aria-label="Close share dialog">
                <i data-lucide="x" class="w-5 h-5 achievement-share-close-icon"></i>
            </button>
        </div>
        <p id="achievement-share-loading" class="text-sm text-center text-indigo-500 dark:text-indigo-300 py-8 hidden" data-loading>Building your share image…</p>
        <img id="achievement-share-preview" alt="Achievement share preview" class="achievement-share-preview" width="360" height="640" />
        <div class="achievement-share-actions">
            <button type="button" class="btn-primary w-full justify-center" data-achievement-share-download>
                <i data-lucide="download" class="w-4 h-4"></i>
                Download image
            </button>
            <button type="button" class="btn-secondary w-full justify-center" data-achievement-share-copy>
                <i data-lucide="link" class="w-4 h-4"></i>
                Copy public profile link
            </button>
            <button type="button" class="btn-primary w-full justify-center achievement-share-story-btn" data-achievement-share-story>
                <i data-lucide="images" class="w-4 h-4"></i>
                Post to Study Story
            </button>
        </div>
    </div>
</dialog>
