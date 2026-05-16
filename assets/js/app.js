const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';

async function api(url, options = {}) {
    const headers = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN':
            typeof CSRF !== 'undefined' && CSRF
                ? CSRF
                : document.querySelector('meta[name="csrf-token"]')?.content?.trim() || '',
        ...(options.headers || {}),
    };
    if (options.body && !(options.body instanceof FormData) && typeof options.body === 'string') {
        headers['Content-Type'] = 'application/json';
    }
    const res = await fetch(url, { credentials: 'same-origin', ...options, headers });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(data.error || 'Request failed');
    return data;
}

function showToast(message, type = 'success') {
    const el = document.createElement('div');
    el.className = `toast-enter pointer-events-auto px-4 py-3 rounded-xl shadow-lg text-sm font-medium text-white ${
        type === 'error' ? 'bg-rose-600' : type === 'info' ? 'bg-sky-600' : 'bg-emerald-600'
    }`;
    el.textContent = message;
    document.getElementById('toast-container')?.appendChild(el);
    setTimeout(() => el.remove(), 4000);
}

function showAchievement(ach) {
    const overlay = document.getElementById('achievement-overlay');
    if (!overlay || !ach) return;
    document.getElementById('achievement-name').textContent = ach.name;
    document.getElementById('achievement-desc').textContent = ach.description;
    const icon = document.getElementById('achievement-icon');
    icon.setAttribute('data-lucide', ach.icon || 'trophy');
    overlay.style.display = 'flex';
    lucide.createIcons();
}

function closeAchievement() {
    const overlay = document.getElementById('achievement-overlay');
    if (overlay) overlay.style.display = 'none';
}

function handleAchievements(data) {
    if (data.achievements?.length) {
        data.achievements.forEach((a, i) => setTimeout(() => showAchievement(a), i * 800));
    }
}

function toggleTheme() {
    const html = document.documentElement;
    const dark = !html.classList.contains('dark');
    html.classList.toggle('dark', dark);
    localStorage.setItem('theme', dark ? 'dark' : 'light');
}

function topbar() {
    return {
        query: '',
        results: [],
        notifications: [],
        unread: 0,
        async search() {
            if (this.query.length < 2) { this.results = []; return; }
            try {
                const data = await api(`${window.APP_URL}/api/search?q=${encodeURIComponent(this.query)}`);
                this.results = data.results || [];
            } catch { this.results = []; }
        },
        async loadNotifications() {
            try {
                const [n, u] = await Promise.all([
                    api(`${window.APP_URL}/api/notifications`),
                    api(`${window.APP_URL}/api/notifications/unread`),
                ]);
                this.notifications = n.notifications || [];
                this.unread = u.count || 0;
            } catch {}
        },
        async markRead(id) {
            const fd = new FormData();
            fd.append('_csrf', CSRF);
            await api(`${window.APP_URL}/api/notifications/${id}/read`, { method: 'POST', body: fd });
            this.loadNotifications();
        },
        async markAllRead() {
            const fd = new FormData();
            fd.append('_csrf', CSRF);
            await api(`${window.APP_URL}/api/notifications/read-all`, { method: 'POST', body: fd });
            this.unread = 0;
            this.loadNotifications();
        },
        init() {
            this.loadNotifications();
            setInterval(() => this.loadNotifications(), 60000);
        },
    };
}

function syncAppUrl() {
    if (typeof document === 'undefined' || !document.body) return;
    window.APP_URL = document.body.dataset.baseUrl || '';
}

window.syncAppUrl = syncAppUrl;

syncAppUrl();

var _pwdIcons = {
  eye: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>',
  'eye-off': '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/></svg>'
};

function _seedPwdIcons() {
    document.querySelectorAll('[data-pwd-eye]').forEach(function(el) {
        if (!el.querySelector('svg')) {
            el.innerHTML = _pwdIcons.eye;
        }
    });
}

_seedPwdIcons();

function togglePasswordVisibility(inputId, btn) {
    var input = document.getElementById(inputId);
    if (!input || !btn) return;
    var isPassword = input.type === 'password';
    input.type = isPassword ? 'text' : 'password';
    var container = btn.querySelector('[data-pwd-eye]');
    if (container) {
        container.innerHTML = _pwdIcons[isPassword ? 'eye-off' : 'eye'];
    }
}

document.addEventListener('DOMContentLoaded', function() {
    syncAppUrl();
    _seedPwdIcons();
});

/** Study Story — 24h photo reel (loads config from sibling <script type="application/json">) */
function studyStoryRails(domId) {
    const SLIDE_MS = 8500;
    /** One server bump per story id per full page load (avoid rapid replays). */
    const viewedStoryIdsThisSession = new Set();

    const defaults = {
        buckets: [],
        viewerUserId: 0,
        composerRedirect: '/dashboard',
        userGroups: [],
        defaultGroupId: null,
        variant: 'public',
        appBase: '',
    };

    let cfg = { ...defaults };
    if (typeof domId === 'string' && domId) {
        const el = document.getElementById(domId);
        if (el?.textContent?.trim()) {
            try {
                cfg = { ...defaults, ...JSON.parse(el.textContent.trim()) };
            } catch (e) {
                console.warn('Study Story: bad JSON tag', domId, e);
            }
        }
    }

    return {
        buckets: cfg.buckets || [],
        composerRedirect: cfg.composerRedirect || '/dashboard',
        userGroups: cfg.userGroups || [],
        composerOpen: false,
        composeGroupId: '',
        viewerOpen: false,
        viewerUserId: Number(cfg.viewerUserId) || 0,
        variant: cfg.variant || 'public',
        bucketIdx: -1,
        slideIdx: 0,
        progress: 0,
        moodLabels: {
            achievement: 'Achievement',
            task: 'Task win',
            focus: 'Focus streak',
            general: 'Moment',
        },

        _progIv: null,
        _storyAppBase: '',

        storyPath(rel) {
            let base = typeof this._storyAppBase === 'string' ? this._storyAppBase : String(this._storyAppBase ?? '');
            base = base.trim().replace(/\/+$/, '');
            if (base === '' && typeof document !== 'undefined' && document.body) {
                syncAppUrl();
                base = (document.body.dataset?.baseUrl || window.APP_URL || '')
                    .trim()
                    .replace(/\/+$/, '');
            }
            const segment = '/' + String(rel ?? '').replace(/^\/+/, '');
            return base !== '' ? base + segment : segment;
        },

        init() {
            const d = cfg.defaultGroupId;
            this.composeGroupId = d !== null && d !== undefined && String(d) !== '' ? String(d) : '';
            let ab = cfg.appBase;
            if (ab === undefined || ab === null) {
                ab = '';
            }
            this._storyAppBase = String(ab);
        },

        onEscape() {
            if (this.viewerOpen) {
                return this.closeViewer();
            }
            if (this.composerOpen) {
                this.composerOpen = false;
            }
        },

        openComposer() {
            this.composerOpen = true;
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        activeBucket() {
            return this.bucketIdx >= 0 ? this.buckets[this.bucketIdx] : null;
        },

        activeStory() {
            const b = this.activeBucket();
            return b?.stories?.[this.slideIdx] || null;
        },

        /** Safe for Alpine expressions (numbers only). */
        activeStoryViewCount() {
            const st = this.activeStory();
            if (!st) return 0;
            const n = Number(st.view_count);
            return Number.isFinite(n) ? Math.max(0, Math.round(n)) : 0;
        },

        activeMoodLabel() {
            const mk = this.activeStory()?.mood_key || 'general';
            return this.moodLabels[mk] || this.moodLabels.general;
        },

        timeLeftLabel(st) {
            if (!st?.expires_at) return '';
            try {
                const end = new Date(String(st.expires_at).replace(' ', 'T'));
                const msLeft = end.getTime() - Date.now();
                const hrs = Math.max(0, msLeft / 3600000);
                if (hrs < 1) {
                    const mins = Math.max(0, Math.ceil(msLeft / 60000));
                    return `${mins}m left`;
                }
                return `${hrs < 10 ? hrs.toFixed(1) : Math.round(hrs)}h left`;
            } catch {
                return '';
            }
        },

        canDelete(st) {
            return st && Number(st.user_id) === Number(this.viewerUserId);
        },

        clearTimers() {
            if (this._progIv) clearInterval(this._progIv);
            this._progIv = null;
        },

        startSlideAdvance() {
            this.clearTimers();
            this.progress = 0;
            const start = Date.now();
            this._progIv = setInterval(() => {
                const t = Date.now() - start;
                this.progress = Math.min(99.5, (t / SLIDE_MS) * 100);
                if (t >= SLIDE_MS) {
                    this.clearTimers();
                    this.nextSlide();
                }
            }, 40);
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
                this.recordViewOnce();
            });
        },

        tapPrevStory() {
            this.prevSlide();
        },

        tapNextStory() {
            this.skipSlideForward();
        },

        navigateStoryByStrip(event) {
            if (!this.viewerOpen) return;
            const t = event.target;
            if (!t || typeof t.closest !== 'function') return;
            if (t.closest('.story-edge-nav')) return;
            const el = this.$refs.storyStageEl;
            if (!el || typeof el.getBoundingClientRect !== 'function') return;
            if (!el.contains(t)) return;
            const rect = el.getBoundingClientRect();
            const x = event.clientX - rect.left;
            const w = rect.width;
            if (w < 2) return;
            if (x < w * 0.34) {
                this.tapPrevStory();
            } else if (x > w * 0.66) {
                this.tapNextStory();
            }
        },

        async recordViewOnce() {
            const st = this.activeStory();
            if (!st?.id) return;
            const idStr = String(st.id);
            if (viewedStoryIdsThisSession.has(idStr)) return;
            viewedStoryIdsThisSession.add(idStr);
            try {
                syncAppUrl();
                const csrf =
                    document.querySelector('meta[name="csrf-token"]')?.content?.trim() ||
                    (typeof CSRF !== 'undefined' ? CSRF : '');
                const fd = new FormData();
                fd.append('_csrf', csrf);
                const data = await api(this.storyPath(`stories/${encodeURIComponent(String(st.id))}/view`), {
                    method: 'POST',
                    body: fd,
                });
                const vc = data.view_count;
                const total = typeof vc === 'number' ? vc : parseInt(String(vc ?? '0'), 10) || 0;
                const storyIdNum = Number(st.id);
                for (let bi = 0; bi < this.buckets.length; bi++) {
                    const sts = this.buckets[bi].stories || [];
                    for (let si = 0; si < sts.length; si++) {
                        if (Number(sts[si].id) !== storyIdNum) continue;
                        this.buckets[bi].stories[si] = { ...sts[si], view_count: total };
                        return;
                    }
                }
            } catch {
                viewedStoryIdsThisSession.delete(idStr);
            }
        },

        openViewer(bi) {
            const bucket = this.buckets[Number(bi)];
            if (!bucket?.stories?.length) return;
            document.documentElement.style.overflow = 'hidden';
            this.viewerOpen = true;
            this.bucketIdx = Number(bi);
            this.slideIdx = 0;
            this.$nextTick(() => {
                this.startSlideAdvance();
            });
        },

        closeViewer() {
            this.clearTimers();
            document.documentElement.style.overflow = '';
            this.viewerOpen = false;
            this.bucketIdx = -1;
            this.progress = 0;
        },

        skipSlideForward() {
            this.clearTimers();
            this.nextSlide();
        },

        nextSlide() {
            const b = this.activeBucket();
            if (!b) return this.closeViewer();
            if (this.slideIdx + 1 < b.stories.length) {
                this.slideIdx += 1;
                return this.startSlideAdvance();
            }
            if (this.bucketIdx + 1 < this.buckets.length) {
                this.bucketIdx += 1;
                this.slideIdx = 0;
                return this.startSlideAdvance();
            }
            return this.closeViewer();
        },

        prevSlide() {
            if (this.slideIdx > 0) {
                this.slideIdx -= 1;
                return this.startSlideAdvance();
            }
            if (this.bucketIdx > 0) {
                this.bucketIdx -= 1;
                const prevBucket = this.buckets[this.bucketIdx];
                this.slideIdx = Math.max(0, (prevBucket?.stories?.length || 1) - 1);
                return this.startSlideAdvance();
            }
        },

        async deleteStory(st) {
            if (!this.canDelete(st)) return;
            if (!confirm('Delete this Study Story for everyone now?')) return;
            try {
                syncAppUrl();
                await api(this.storyPath(`stories/${encodeURIComponent(String(st.id))}/delete`), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                    body: JSON.stringify({ _csrf: CSRF }),
                });
                window.location.reload();
            } catch (e) {
                showToast(e.message || 'Could not delete', 'error');
            }
        },
    };
}

window.studyStoryRails = studyStoryRails;
