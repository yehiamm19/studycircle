<?php
$focusData = [
    'stats' => $stats,
    'history' => array_slice($history, 0, 8),
    'ambient' => $ambient ?? ['youtube_url' => '', 'volume' => 0.5, 'active_sound' => ''],
    'customTracks' => $customTracks ?? [],
];
?>
<div x-data="focusTimer(<?= htmlspecialchars(json_encode($focusData), ENT_QUOTES, 'UTF-8') ?>)" @keydown.escape.window="fullscreen && exitFullscreen()" @pointerup.window="volumeDragging = false" class="max-w-6xl mx-auto">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-bold mb-2">Focus Timer</h1>
            <p class="text-slate-500 text-sm">Pomodoro technique for deep work sessions</p>
        </div>
        <button type="button" @click="enterFullscreen()" class="btn-secondary shrink-0" title="Fullscreen focus mode">
            <i data-lucide="maximize-2" class="w-4 h-4"></i>
            Fullscreen
        </button>
    </div>

    <div class="grid lg:grid-cols-2 gap-8">
        <section class="glass-card rounded-3xl p-8 flex flex-col items-center">
            <svg class="w-56 h-56 -rotate-90" viewBox="0 0 100 100">
                <circle cx="50" cy="50" r="45" fill="none" stroke="currentColor" class="text-slate-200 dark:text-slate-800" stroke-width="6"/>
                <circle cx="50" cy="50" r="45" fill="none" stroke="url(#gradFocus)" stroke-width="6" stroke-linecap="round"
                    class="focus-ring" :stroke-dasharray="283" :stroke-dashoffset="283 - (283 * progress / 100)"/>
                <defs>
                    <linearGradient id="gradFocus" x1="0%" y1="0%" x2="100%">
                        <stop offset="0%" stop-color="#6366f1"/>
                        <stop offset="100%" stop-color="#8b5cf6"/>
                    </linearGradient>
                </defs>
            </svg>
            <p class="text-5xl font-bold tabular-nums mt-4" x-text="displayTime"></p>
            <p class="text-sm text-slate-400 mt-1" x-text="statusLabel"></p>
            <div class="flex gap-3 mt-8">
                <button type="button" @click="toggle()" class="btn-primary px-8" x-text="running && !paused ? 'Pause' : (running ? 'Resume' : 'Start')"></button>
                <button type="button" @click="reset()" class="btn-secondary">Reset</button>
            </div>
            <div class="flex gap-4 mt-6 text-sm text-slate-500 dark:text-slate-400">
                <label class="flex items-center gap-1">Work <input type="number" x-model.number="workMin" min="1" max="90" class="w-14 input text-center" @change="onWorkMinChange()"> min</label>
                <label class="flex items-center gap-1">Break <input type="number" x-model.number="breakMin" min="1" max="30" class="w-16 input text-center"> min</label>
            </div>
            <div class="w-full mt-6 space-y-2">
                <div class="select-wrap">
                    <select x-model="groupId" class="input">
                        <option value="">No group</option>
                        <?php foreach ($groups as $g): ?><option value="<?= $g['id'] ?>"><?= e($g['name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="select-wrap">
                    <select x-model="taskId" class="input" :disabled="!groupId">
                        <option value="">Link to task (optional)</option>
                        <template x-for="t in tasks" :key="t.id"><option :value="t.id" x-text="t.title"></option></template>
                    </select>
                </div>
            </div>
        </section>

        <section class="space-y-4">
            <div class="grid grid-cols-3 gap-3">
                <div class="stat-card text-center">
                    <p class="text-2xl font-bold" x-text="stats.total_sessions">0</p>
                    <p class="text-xs text-slate-400">Sessions</p>
                </div>
                <div class="stat-card text-center">
                    <p class="text-2xl font-bold" x-text="formatDuration(stats.total_minutes)">0m</p>
                    <p class="text-xs text-slate-400">Total</p>
                </div>
                <div class="stat-card text-center">
                    <p class="text-2xl font-bold" x-text="formatDuration(stats.today_minutes)">0m</p>
                    <p class="text-xs text-slate-400">Today</p>
                </div>
            </div>
            <div class="glass-card rounded-2xl p-5">
                <h2 class="font-bold mb-3">Recent Sessions</h2>
                <template x-if="!history.length">
                    <p class="text-sm text-slate-400">Complete a focus session to build your history.</p>
                </template>
                <template x-for="(s, i) in history" :key="s.id || i">
                    <div class="flex justify-between py-2 border-b border-slate-100 dark:border-white/5 text-sm last:border-0">
                        <span>
                            <span x-text="s.duration_minutes"></span> min
                            <template x-if="s.group_name"><span x-text="' · ' + s.group_name"></span></template>
                        </span>
                        <span class="text-slate-400" x-text="timeAgo(s.created_at)"></span>
                    </div>
                </template>
            </div>
            <?php partial('focus-ambient-panel'); ?>
        </section>
    </div>

    <div x-show="fullscreen" x-cloak x-transition.opacity class="focus-fullscreen">
        <button type="button" @click="exitFullscreen()" class="focus-fullscreen-exit">
            <i data-lucide="minimize-2" class="w-4 h-4"></i>
            Exit
        </button>
        <img src="<?= logo_url() ?>" alt="" class="focus-fullscreen-brand" aria-hidden="true">
        <div class="focus-fullscreen-inner">
            <svg class="focus-fullscreen-ring -rotate-90" viewBox="0 0 100 100">
                <circle cx="50" cy="50" r="45" fill="none" stroke="rgba(255,255,255,0.08)" stroke-width="4"/>
                <circle cx="50" cy="50" r="45" fill="none" stroke="url(#gradFs)" stroke-width="4" stroke-linecap="round"
                    class="focus-ring" :stroke-dasharray="283" :stroke-dashoffset="283 - (283 * progress / 100)"/>
                <defs>
                    <linearGradient id="gradFs" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" stop-color="#818cf8"/>
                        <stop offset="100%" stop-color="#c084fc"/>
                    </linearGradient>
                </defs>
            </svg>
            <p class="focus-fullscreen-time" x-text="displayTime"></p>
            <p class="focus-fullscreen-status" x-text="statusLabel"></p>
        </div>
        <div class="focus-fullscreen-controls">
            <button type="button" @click="toggle()" class="btn-primary px-10 py-3 text-base" x-text="running && !paused ? 'Pause' : (running ? 'Resume' : 'Start')"></button>
        </div>
        <button type="button" @click="ambientPanelOpen = !ambientPanelOpen" class="focus-ambient-fab" :class="ambientPlaying ? 'focus-ambient-fab-active' : ''" title="Ambient sounds">
            <i data-lucide="headphones" class="w-5 h-5"></i>
            <span class="sr-only">Sounds</span>
        </button>
        <div x-show="ambientPanelOpen" x-cloak @click.outside="ambientPanelOpen = false" x-transition class="focus-ambient-drawer">
            <?php partial('focus-ambient-panel', ['compact' => true]); ?>
        </div>
    </div>
</div>

<script src="https://www.youtube.com/iframe_api" async></script>
<script src="<?= asset('js/focus-ambient.js') ?>"></script>
<script>
function focusTimer(initial = {}) {
    const ambientCfg = initial.ambient || {};
    return {
        workMin: 25,
        breakMin: 5,
        seconds: 25 * 60,
        running: false,
        paused: false,
        isBreak: false,
        groupId: '',
        taskId: '',
        tasks: [],
        progress: 0,
        startedAt: null,
        fullscreen: false,
        tickTimer: null,
        stats: initial.stats || { total_sessions: 0, total_minutes: 0, today_minutes: 0 },
        history: initial.history || [],
        ambientSounds: [],
        customTracks: initial.customTracks || [],
        ambientVolume: parseFloat(ambientCfg.volume ?? 0.5),
        activeAmbient: ambientCfg.active_sound || '',
        ambientPlaying: false,
        ambientPanelOpen: false,
        volumeDragging: false,
        trackForm: { name: '', icon: 'youtube', url: '' },

        get volumeIcon() {
            if (this.ambientVolume <= 0) return 'volume-x';
            if (this.ambientVolume < 0.35) return 'volume';
            if (this.ambientVolume < 0.7) return 'volume-1';
            return 'volume-2';
        },

        get displayTime() {
            const m = Math.floor(this.seconds / 60);
            const s = this.seconds % 60;
            return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
        },
        get statusLabel() {
            if (!this.running) return 'Ready';
            if (this.paused) return 'Paused';
            return this.isBreak ? 'Break' : 'Focus';
        },

        init() {
            this.seconds = this.workMin * 60;
            this.$watch('groupId', async (id) => {
                this.taskId = '';
                if (!id) { this.tasks = []; return; }
                try {
                    const d = await api(`${window.APP_URL}/focus/groups/${id}/tasks`);
                    this.tasks = d.tasks || [];
                } catch { this.tasks = []; }
            });
            document.addEventListener('fullscreenchange', () => {
                if (!document.fullscreenElement && this.fullscreen) {
                    this.fullscreen = false;
                    document.documentElement.classList.remove('focus-fs-active');
                }
            });
            if (window.FocusAmbient) {
                FocusAmbient.setCustomTracks(this.customTracks);
                this.rebuildAmbientSounds();
                FocusAmbient.init(ambientCfg);
                this.ambientVolume = FocusAmbient.volume;
                this.activeAmbient = FocusAmbient.activeId || '';
                this.ambientPlaying = FocusAmbient.playing;
            }
            this.$watch('ambientPanelOpen', () => this.$nextTick(() => this.refreshIcons()));
            this.$watch('trackForm.icon', () => this.$nextTick(() => this.refreshIcons()));
            setTimeout(() => this.syncAmbientState(), 600);
        },

        refreshIcons() {
            if (typeof lucide !== 'undefined') lucide.createIcons();
        },

        rebuildAmbientSounds() {
            if (!window.FocusAmbient) return;
            FocusAmbient.setCustomTracks(this.customTracks);
            this.ambientSounds = FocusAmbient.allSounds();
        },

        syncAmbientState() {
            if (!window.FocusAmbient) return;
            this.activeAmbient = FocusAmbient.activeId || '';
            this.ambientPlaying = FocusAmbient.playing;
            this.ambientVolume = FocusAmbient.volume;
            this.$nextTick(() => this.refreshIcons());
        },

        setAmbientVolume(v) {
            if (!window.FocusAmbient) return;
            FocusAmbient.setVolume(v);
            this.ambientVolume = FocusAmbient.volume;
            this.$nextTick(() => this.refreshIcons());
        },

        nudgeAmbientVolume(delta) {
            this.setAmbientVolume(Math.max(0, Math.min(1, this.ambientVolume + delta)));
        },

        toggleAmbient(id) {
            if (!window.FocusAmbient) return;
            const sound = FocusAmbient.getSound(id);
            try {
                if (sound?.type === 'youtube') {
                    if (FocusAmbient.activeId === id && FocusAmbient.playing) {
                        this.stopAmbient();
                        return;
                    }
                    FocusAmbient.playYouTube(sound.youtube_url, id);
                } else {
                    FocusAmbient.play(id).then(() => this.syncAmbientState()).catch((e) => {
                        showToast(e.message || 'Could not play sound', 'error');
                    });
                    return;
                }
                this.syncAmbientState();
            } catch (e) {
                showToast(e.message || 'Could not play sound', 'error');
            }
        },

        stopAmbient() {
            if (!window.FocusAmbient) return;
            FocusAmbient.stop();
            FocusAmbient.activeId = null;
            FocusAmbient.persist('', false);
            this.syncAmbientState();
        },

        async saveCustomTrack() {
            const name = (this.trackForm.name || '').trim();
            const url = (this.trackForm.url || '').trim();
            const icon = this.trackForm.icon || 'youtube';
            if (!name || !url) {
                showToast('Enter a name and YouTube link', 'info');
                return;
            }
            if (!window.FocusAmbient?.parseYoutubeId(url)) {
                showToast('Invalid YouTube link', 'error');
                return;
            }
            const fd = new FormData();
            fd.append('_csrf', CSRF);
            fd.append('name', name);
            fd.append('icon', icon);
            fd.append('youtube_url', url);
            try {
                const data = await api(`${window.APP_URL}/api/focus/tracks`, { method: 'POST', body: fd });
                this.customTracks = data.tracks || [];
                this.rebuildAmbientSounds();
                this.trackForm = { name: '', icon: 'youtube', url: '' };
                this.$nextTick(() => this.refreshIcons());
                showToast('Added to your library');
            } catch (e) {
                showToast(e.message || 'Could not save track', 'error');
            }
        },

        async deleteCustomTrack(soundId) {
            const id = String(soundId).replace(/^yt-/, '');
            if (!id) return;
            if (this.activeAmbient === soundId) this.stopAmbient();
            const fd = new FormData();
            fd.append('_csrf', CSRF);
            try {
                const data = await api(`${window.APP_URL}/api/focus/tracks/${id}/delete`, { method: 'POST', body: fd });
                this.customTracks = data.tracks || [];
                this.rebuildAmbientSounds();
                this.$nextTick(() => this.refreshIcons());
                showToast('Track removed', 'info');
            } catch (e) {
                showToast(e.message || 'Could not remove track', 'error');
            }
        },

        formatDuration(minutes) {
            let n = Math.max(0, Math.round(Number(minutes) || 0));
            const h = Math.floor(n / 60);
            const m = n % 60;
            if (h > 0 && m > 0) return `${h}h ${m}m`;
            if (h > 0) return `${h}h`;
            return `${m}m`;
        },

        /** @deprecated alias */
        formatHours(minutes) {
            return this.formatDuration(minutes);
        },

        timeAgo(datetime) {
            if (!datetime) return '';
            const diff = Math.floor((Date.now() - new Date(datetime.replace(' ', 'T')).getTime()) / 1000);
            if (diff < 60) return 'just now';
            if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
            if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
            return Math.floor(diff / 86400) + 'd ago';
        },

        applySessionResult(data) {
            if (data.stats) this.stats = data.stats;
            if (data.session) {
                this.history.unshift(data.session);
                if (this.history.length > 8) this.history.pop();
            }
        },

        onWorkMinChange() {
            if (!this.running) this.reset();
        },

        async enterFullscreen() {
            this.fullscreen = true;
            document.documentElement.classList.add('focus-fs-active');
            this.$nextTick(() => this.refreshIcons());
            try { await document.documentElement.requestFullscreen(); } catch (_) {}
        },

        async exitFullscreen() {
            this.fullscreen = false;
            this.ambientPanelOpen = false;
            document.documentElement.classList.remove('focus-fs-active');
            if (document.fullscreenElement) {
                try { await document.exitFullscreen(); } catch (_) {}
            }
        },

        toggle() {
            if (!this.running) {
                this.running = true;
                this.paused = false;
                this.startedAt = new Date().toISOString();
                this.tick();
            } else if (this.paused) {
                this.paused = false;
                this.tick();
            } else {
                this.paused = true;
                if (this.tickTimer) clearTimeout(this.tickTimer);
            }
        },

        tick() {
            if (!this.running || this.paused) return;
            const total = (this.isBreak ? this.breakMin : this.workMin) * 60;
            this.progress = total > 0 ? ((total - this.seconds) / total) * 100 : 0;
            if (this.seconds <= 0) {
                this.onComplete();
                return;
            }
            this.tickTimer = setTimeout(() => {
                this.seconds--;
                this.tick();
            }, 1000);
        },

        reset() {
            this.running = false;
            this.paused = false;
            this.isBreak = false;
            this.seconds = this.workMin * 60;
            this.progress = 0;
            this.startedAt = null;
            if (this.tickTimer) clearTimeout(this.tickTimer);
        },

        async saveSession(duration, completed, isBreak = false) {
            const fd = new FormData();
            fd.append('_csrf', CSRF);
            fd.append('duration_minutes', duration);
            fd.append('completed', completed ? '1' : '0');
            fd.append('is_break', isBreak ? '1' : '0');
            if (this.groupId) fd.append('group_id', this.groupId);
            if (this.taskId) fd.append('task_id', this.taskId);
            if (this.startedAt) fd.append('started_at', this.startedAt);
            return api(`${window.APP_URL}/focus/complete`, { method: 'POST', body: fd });
        },

        async onComplete() {
            if (this.tickTimer) clearTimeout(this.tickTimer);

            if (!this.isBreak) {
                const duration = this.workMin;
                try {
                    const data = await this.saveSession(duration, true, false);
                    this.applySessionResult(data);
                    handleAchievements(data);
                    showToast(`Focus complete! +${<?= (int) config('xp_per_focus', 10) ?>} XP`);
                } catch (e) {
                    showToast(e.message || 'Could not save session', 'error');
                }
                this.isBreak = true;
                this.seconds = this.breakMin * 60;
                this.progress = 0;
                this.running = true;
                this.paused = false;
                this.tick();
            } else {
                try {
                    await this.saveSession(this.breakMin, false, true);
                } catch (_) {}
                this.reset();
                showToast('Break over — ready for another round?', 'info');
            }
        },
    };
}
</script>
