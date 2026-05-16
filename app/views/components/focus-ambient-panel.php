<?php
/** @var bool $compact - smaller layout for fullscreen drawer */
$compact = $compact ?? false;
$trackIcons = $trackIcons ?? \App\Models\AmbientTrack::ALLOWED_ICONS;
?>
<section class="ambient-panel glass-card rounded-2xl overflow-hidden" :class="<?= $compact ? "'ambient-panel-compact'" : "''" ?>">
    <header class="ambient-panel-header">
        <div class="flex items-center gap-2">
            <span class="ambient-panel-icon"><i data-lucide="headphones" class="w-5 h-5"></i></span>
            <div>
                <h2 class="font-bold text-sm sm:text-base">Ambient Sounds</h2>
                <p class="text-xs text-slate-400">Relaxation & deep focus</p>
            </div>
        </div>
        <button type="button" @click="ambientPanelOpen = false" x-show="<?= $compact ? 'true' : 'false' ?>" class="p-2 rounded-lg hover:bg-white/10">
            <i data-lucide="x" class="w-4 h-4"></i>
        </button>
    </header>

    <div class="ambient-panel-body">
        <div class="ambient-volume" :style="'--vol-pct:' + Math.round(ambientVolume * 100) + '%'" :class="{ 'is-dragging': volumeDragging }">
            <div class="ambient-vol-row">
                <i :data-lucide="volumeIcon" class="ambient-vol-ico w-4 h-4"></i>
                <span class="ambient-vol-label">Volume</span>
                <button type="button" @click="nudgeAmbientVolume(-0.05)" class="ambient-vol-ghost" aria-label="Quieter">
                    <i data-lucide="minus" class="w-3.5 h-3.5"></i>
                </button>
                <div class="ambient-vol-track">
                    <input
                        type="range"
                        min="0"
                        max="100"
                        step="1"
                        :value="Math.round(ambientVolume * 100)"
                        @input="setAmbientVolume($event.target.value / 100)"
                        @pointerdown="volumeDragging = true"
                        @pointerup="volumeDragging = false"
                        @pointercancel="volumeDragging = false"
                        class="ambient-vol-range"
                        aria-label="Volume"
                    >
                </div>
                <button type="button" @click="nudgeAmbientVolume(0.05)" class="ambient-vol-ghost" aria-label="Louder">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                </button>
                <span class="ambient-vol-pct" x-text="Math.round(ambientVolume * 100)"></span>
            </div>
        </div>

        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Relaxation</p>
        <div class="grid grid-cols-3 gap-2 mb-4">
            <template x-for="s in ambientSounds.filter(x => x.category === 'relax')" :key="s.id">
                <button type="button" @click="toggleAmbient(s.id)" class="ambient-sound-btn" :class="activeAmbient === s.id && ambientPlaying ? 'ambient-sound-active' : ''">
                    <i :data-lucide="s.icon" class="w-5 h-5 mx-auto mb-1"></i>
                    <span class="block text-[10px] sm:text-xs font-medium truncate" x-text="s.name"></span>
                </button>
            </template>
        </div>

        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Deep Focus</p>
        <div class="grid grid-cols-4 gap-2 mb-4">
            <template x-for="s in ambientSounds.filter(x => x.category === 'focus')" :key="s.id">
                <button type="button" @click="toggleAmbient(s.id)" class="ambient-sound-btn" :class="activeAmbient === s.id && ambientPlaying ? 'ambient-sound-active' : ''">
                    <i :data-lucide="s.icon" class="w-5 h-5 mx-auto mb-1"></i>
                    <span class="block text-[10px] sm:text-xs font-medium truncate" x-text="s.name"></span>
                </button>
            </template>
        </div>

        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">My YouTube Library</p>
        <div class="ambient-library-add mb-3">
            <input type="text" x-model="trackForm.name" placeholder="Track name" class="input text-sm mb-2 w-full">
            <div class="flex gap-2 mb-2">
                <div class="select-wrap flex-1">
                    <select x-model="trackForm.icon" class="input text-sm w-full">
                        <?php foreach ($trackIcons as $ic): ?>
                        <option value="<?= e($ic) ?>"><?= e(ucwords(str_replace('-', ' ', $ic))) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <span class="flex items-center justify-center w-10 h-10 rounded-lg bg-white/5 border border-white/10 shrink-0">
                    <i :data-lucide="trackForm.icon" class="w-5 h-5"></i>
                </span>
            </div>
            <input type="url" x-model="trackForm.url" placeholder="YouTube link" class="input text-sm mb-2 w-full">
            <button type="button" @click="saveCustomTrack()" class="btn-primary w-full text-sm justify-center">
                <i data-lucide="plus" class="w-4 h-4"></i> Add to library
            </button>
        </div>

        <template x-if="!customTracks.length">
            <p class="text-xs text-slate-500 mb-3">Save your favorite streams here — name, icon, and link.</p>
        </template>
        <div class="grid grid-cols-2 gap-2 mb-4" x-show="customTracks.length">
            <template x-for="s in ambientSounds.filter(x => x.category === 'custom')" :key="s.id">
                <div class="relative group">
                    <button type="button" @click="toggleAmbient(s.id)" class="ambient-sound-btn w-full" :class="activeAmbient === s.id && ambientPlaying ? 'ambient-sound-active' : ''">
                        <i :data-lucide="s.icon" class="w-5 h-5 mx-auto mb-1"></i>
                        <span class="block text-[10px] sm:text-xs font-medium truncate px-1" x-text="s.name"></span>
                    </button>
                    <button type="button" @click.stop="deleteCustomTrack(s.id)" class="absolute -top-1 -right-1 w-5 h-5 rounded-full bg-rose-500/90 text-white text-xs opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center" title="Remove">×</button>
                </div>
            </template>
        </div>

        <div class="flex gap-2 mt-2" x-show="ambientPlaying">
            <button type="button" @click="stopAmbient()" class="btn-secondary flex-1 text-sm justify-center">
                <i data-lucide="pause" class="w-4 h-4"></i> Pause
            </button>
        </div>
    </div>
</section>
