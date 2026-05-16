/**
 * StudyCircle — Focus ambient sound manager
 */
window.FocusAmbient = {
    volume: 0.5,
    activeId: null,
    playing: false,
    youtubeUrl: '',
    audio: null,
    brownNode: null,
    brownGain: null,
    audioCtx: null,
    ytPlayer: null,
    ytIframeMode: false,
    ytIframeEl: null,
    ytVideoId: null,
    ytNocookie: false,
    _ytApiAttached: false,
    ytReady: false,
    ytApiLoading: false,
    _ytLoadPromise: null,
    customSounds: [],

    SOUNDS: [
        { id: 'rain', name: 'Rain', icon: 'cloud-rain', category: 'relax', src: 'https://assets.mixkit.co/active_storage/sfx/2390/2390-preview.mp3' },
        { id: 'birds', name: 'Morning Birds', icon: 'bird', category: 'relax', src: 'https://assets.mixkit.co/active_storage/sfx/2432/2432-preview.mp3' },
        { id: 'forest', name: 'Forest', icon: 'trees', category: 'relax', src: 'https://assets.mixkit.co/active_storage/sfx/1186/1186-preview.mp3' },
        { id: 'ocean', name: 'Ocean Waves', icon: 'waves', category: 'focus', src: 'https://assets.mixkit.co/active_storage/sfx/2168/2168-preview.mp3' },
        { id: 'wind', name: 'Soft Wind', icon: 'wind', category: 'focus', src: 'https://assets.mixkit.co/active_storage/sfx/1362/1362-preview.mp3' },
        { id: 'calm', name: 'Deep Calm', icon: 'sparkles', category: 'focus', src: 'https://assets.mixkit.co/active_storage/sfx/2523/2523-preview.mp3' },
        { id: 'brown', name: 'Brown Noise', icon: 'audio-lines', category: 'focus', type: 'brown' },
    ],

    setCustomTracks(tracks) {
        this.customSounds = (tracks || []).map((t) => ({
            id: 'yt-' + t.id,
            name: t.name,
            icon: t.icon || 'youtube',
            category: 'custom',
            type: 'youtube',
            youtube_url: t.youtube_url,
        }));
    },

    allSounds() {
        return [...this.SOUNDS, ...this.customSounds];
    },

    init(settings = {}) {
        this.volume = parseFloat(settings.volume ?? localStorage.getItem('sc_ambient_vol') ?? 0.5);
        this.youtubeUrl = settings.youtube_url || localStorage.getItem('sc_youtube_url') || '';
        const saved = settings.active_sound || localStorage.getItem('sc_active_sound') || '';
        this.loadYouTubeApi().catch(() => {});
        if (saved && saved !== 'youtube' && !String(saved).startsWith('yt-')) {
            setTimeout(() => this.play(saved).catch(() => {}), 500);
        }
    },

    getSound(id) {
        return this.SOUNDS.find((s) => s.id === id) || this.customSounds.find((s) => s.id === id) || null;
    },

    parseYoutubeId(url) {
        if (!url || typeof url !== 'string') return null;
        const raw = url.trim();
        if (/^[a-zA-Z0-9_-]{11}$/.test(raw)) return raw;

        try {
            const u = new URL(raw);
            const host = u.hostname.replace(/^www\./, '');
            if (host === 'youtu.be') {
                const id = u.pathname.replace(/^\//, '').split('/')[0];
                return id && id.length === 11 ? id : null;
            }
            if (host === 'youtube.com' || host === 'm.youtube.com' || host === 'music.youtube.com') {
                const v = u.searchParams.get('v');
                if (v && v.length === 11) return v;
                const parts = u.pathname.split('/').filter(Boolean);
                if (parts[0] === 'embed' || parts[0] === 'shorts' || parts[0] === 'live') {
                    return parts[1] && parts[1].length === 11 ? parts[1] : null;
                }
            }
        } catch (_) {}

        const m = raw.match(/(?:[?&]v=|\/embed\/|youtu\.be\/|\/shorts\/|\/live\/)([a-zA-Z0-9_-]{11})/);
        return m ? m[1] : null;
    },

    loadYouTubeApi() {
        if (window.YT?.Player) {
            this.ytReady = true;
            return Promise.resolve();
        }
        if (this._ytLoadPromise) return this._ytLoadPromise;

        this._ytLoadPromise = new Promise((resolve, reject) => {
            let settled = false;
            const finish = () => {
                if (settled) return;
                if (!window.YT?.Player) return;
                settled = true;
                clearInterval(poll);
                clearTimeout(timer);
                this.ytReady = true;
                resolve();
            };

            const prev = window.onYouTubeIframeAPIReady;
            window.onYouTubeIframeAPIReady = () => {
                if (typeof prev === 'function') prev();
                finish();
            };

            if (!this.ytApiLoading) {
                this.ytApiLoading = true;
                const tag = document.createElement('script');
                tag.src = 'https://www.youtube.com/iframe_api';
                tag.async = true;
                tag.onerror = () => {
                    if (!settled) {
                        settled = true;
                        clearInterval(poll);
                        clearTimeout(timer);
                        reject(new Error('Could not load YouTube.'));
                    }
                };
                document.head.appendChild(tag);
            }

            const poll = setInterval(finish, 150);
            const timer = setTimeout(() => {
                if (!settled) {
                    settled = true;
                    clearInterval(poll);
                    reject(new Error('YouTube API timed out.'));
                }
            }, 15000);
        });

        return this._ytLoadPromise;
    },

    ytErrorMessage(code) {
        const messages = {
            2: 'Invalid YouTube video ID.',
            5: 'YouTube player error. Try another video.',
            100: 'Video not found or removed.',
            101: 'This video blocks embedding. Try a lofi live stream.',
            150: 'This video blocks embedding.',
            152: 'YouTube player config error.',
            153: 'Refresh the page and try again.',
        };
        return messages[code] || `YouTube error (${code}). Try another embeddable video.`;
    },

    embedOrigin() {
        const o = window.location.origin;
        return o && o !== 'null' ? o : '';
    },

    buildYoutubeEmbedUrl(videoId, nocookie = false, startMuted = false) {
        const base = nocookie
            ? 'https://www.youtube-nocookie.com/embed/'
            : 'https://www.youtube.com/embed/';
        const params = new URLSearchParams({
            autoplay: '1',
            loop: '1',
            playlist: videoId,
            controls: '0',
            playsinline: '1',
            rel: '0',
            enablejsapi: '1',
            mute: startMuted ? '1' : '0',
        });
        const origin = this.embedOrigin();
        if (origin) params.set('origin', origin);
        return `${base}${videoId}?${params.toString()}`;
    },

    applyYtAudio(player) {
        if (!player?.setVolume) return;
        const pct = Math.max(0, Math.min(100, Math.round(this.volume * 100)));
        try {
            if (pct > 0) {
                if (player.isMuted?.() && player.unMute) player.unMute();
                else if (player.unMute) player.unMute();
            } else if (player.mute) {
                player.mute();
            }
            player.setVolume(pct);
        } catch (_) {}
    },

    destroyYtPlayer() {
        this.ytIframeMode = false;
        this.ytIframeEl = null;
        this.ytVideoId = null;
        this._ytApiAttached = false;
        if (this.ytPlayer) {
            try {
                if (typeof this.ytPlayer.stopVideo === 'function') this.ytPlayer.stopVideo();
                if (typeof this.ytPlayer.destroy === 'function') this.ytPlayer.destroy();
            } catch (_) {}
            this.ytPlayer = null;
        }
        document.getElementById('yt-ambient-player')?.remove();
    },

    mountYoutubeIframe(videoId, nocookie = false) {
        this.destroyYtPlayer();
        this.ytVideoId = videoId;
        this.ytNocookie = nocookie;

        const wrap = document.createElement('div');
        wrap.id = 'yt-ambient-player';
        wrap.setAttribute('aria-hidden', 'true');
        wrap.style.cssText = 'position:fixed;bottom:0;right:0;width:280px;height:158px;z-index:9990;opacity:0.01;pointer-events:none;overflow:hidden;border:0;';

        const iframe = document.createElement('iframe');
        iframe.id = 'yt-ambient-iframe';
        iframe.title = 'YouTube ambient audio';
        iframe.width = '280';
        iframe.height = '158';
        iframe.setAttribute('frameborder', '0');
        iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture';
        iframe.referrerPolicy = 'strict-origin-when-cross-origin';
        iframe.src = this.buildYoutubeEmbedUrl(videoId, nocookie, this.volume <= 0.02);

        wrap.appendChild(iframe);
        document.body.appendChild(wrap);
        this.ytIframeEl = iframe;
        this.ytIframeMode = true;
    },

    attachYoutubeApiWhenReady() {
        if (this._ytApiAttached || !this.ytIframeEl) return;
        this.loadYouTubeApi().then(() => {
            if (this._ytApiAttached || !this.ytIframeEl || !window.YT?.Player) return;
            if (!document.getElementById('yt-ambient-iframe')) return;
            try {
                this._ytApiAttached = true;
                this.ytPlayer = new YT.Player('yt-ambient-iframe', {
                    events: {
                        onReady: (ev) => {
                            this.ytIframeMode = false;
                            this.applyYtAudio(ev.target);
                            setTimeout(() => this.applyYtAudio(ev.target), 500);
                        },
                        onStateChange: (ev) => {
                            if (ev.data === window.YT.PlayerState.ENDED) {
                                try { ev.target.playVideo(); } catch (_) {}
                            }
                        },
                        onError: (ev) => {
                            if (this.isYtConfigError(ev.data) && !this.ytNocookie && this.ytVideoId) {
                                this._ytApiAttached = false;
                                this.mountYoutubeIframe(this.ytVideoId, true);
                                this.attachYoutubeApiWhenReady();
                            }
                        },
                    },
                });
            } catch (_) {
                this._ytApiAttached = false;
            }
        }).catch(() => {});
    },

    refreshIframeAudio() {
        if (!this.ytIframeMode || !this.ytVideoId || !this.ytIframeEl) return;
        this.ytIframeEl.src = this.buildYoutubeEmbedUrl(this.ytVideoId, this.ytNocookie, this.volume <= 0.02);
    },

    stop() {
        this.playing = false;
        if (this.audio) {
            this.audio.pause();
            this.audio.currentTime = 0;
            this.audio = null;
        }
        this.stopBrown();
        if (this.ytPlayer?.pauseVideo) {
            try { this.ytPlayer.pauseVideo(); } catch (_) {}
        }
    },

    stopBrown() {
        if (this.brownNode) {
            try { this.brownNode.stop(); this.brownNode.disconnect(); } catch (_) {}
            this.brownNode = null;
            this.brownGain = null;
        }
    },

    startBrown() {
        this.stopBrown();
        const ctx = this.audioCtx || new (window.AudioContext || window.webkitAudioContext)();
        this.audioCtx = ctx;
        if (ctx.state === 'suspended') ctx.resume();

        const bufferSize = 2 * ctx.sampleRate;
        const buffer = ctx.createBuffer(1, bufferSize, ctx.sampleRate);
        const data = buffer.getChannelData(0);
        let last = 0;
        for (let i = 0; i < bufferSize; i++) {
            const white = Math.random() * 2 - 1;
            data[i] = (last + 0.02 * white) / 1.02;
            last = data[i];
            data[i] *= 3.5;
        }

        const source = ctx.createBufferSource();
        source.buffer = buffer;
        source.loop = true;
        const gain = ctx.createGain();
        gain.gain.value = this.volume * 0.35;
        source.connect(gain);
        gain.connect(ctx.destination);
        source.start(0);
        this.brownNode = source;
        this.brownGain = gain;
    },

    setVolume(v) {
        this.volume = Math.max(0, Math.min(1, v));
        localStorage.setItem('sc_ambient_vol', String(this.volume));
        if (this.audio) this.audio.volume = this.volume;
        if (this.brownGain) this.brownGain.gain.value = this.volume * 0.35;
        if (this.ytIframeMode) {
            this.refreshIframeAudio();
            return;
        }
        if (this.ytPlayer) {
            this.applyYtAudio(this.ytPlayer);
        }
    },

    async play(soundId) {
        if (soundId === this.activeId && this.playing) {
            this.stop();
            this.activeId = null;
            this.persist('', false);
            return false;
        }

        this.stop();
        this.activeId = soundId;

        const sound = this.getSound(soundId);
        if (!sound) return false;

        if (sound.type === 'youtube') {
            this.persist(soundId, true);
            return this.playYouTube(sound.youtube_url, soundId);
        }

        this.persist(soundId, true);

        if (sound.type === 'brown') {
            this.startBrown();
            this.playing = true;
            return true;
        }

        this.audio = new Audio(sound.src);
        this.audio.loop = true;
        this.audio.volume = this.volume;
        try {
            await this.audio.play();
            this.playing = true;
            return true;
        } catch (e) {
            this.audio = null;
            this.playing = false;
            throw new Error('Could not play sound. Check your connection.');
        }
    },

    isYtConfigError(code) {
        return code === 152 || code === 153 || code === 150;
    },

    /**
     * Play YouTube immediately inside the click handler (no await before iframe).
     * Browsers block unmuted autoplay if the user-gesture chain is broken by async gaps.
     */
    playYouTube(url, activeId = 'youtube') {
        const id = this.parseYoutubeId(url);
        if (!id) throw new Error('Invalid YouTube link.');

        this.stop();
        this.youtubeUrl = url.trim();
        localStorage.setItem('sc_youtube_url', this.youtubeUrl);
        this.activeId = activeId;

        this.mountYoutubeIframe(id, false);
        this.playing = true;
        this.persist(activeId, true);

        this.attachYoutubeApiWhenReady();

        return Promise.resolve(true);
    },

    async saveYoutube(url) {
        const trimmed = url.trim();
        if (!this.parseYoutubeId(trimmed)) {
            throw new Error('Invalid YouTube link.');
        }
        this.youtubeUrl = trimmed;
        localStorage.setItem('sc_youtube_url', this.youtubeUrl);
        const fd = new FormData();
        fd.append('_csrf', typeof CSRF !== 'undefined' ? CSRF : '');
        fd.append('youtube_url', this.youtubeUrl);
        fd.append('volume', this.volume);
        fd.append('active_sound', this.activeId || '');
        const base = window.APP_URL || document.body?.dataset?.baseUrl || '';
        if (base) {
            await api(`${base}/api/focus/ambient`, { method: 'POST', body: fd });
        }
    },

    persist(activeSound, playing) {
        localStorage.setItem('sc_active_sound', activeSound || '');
        this.playing = playing;
        const fd = new FormData();
        fd.append('_csrf', typeof CSRF !== 'undefined' ? CSRF : '');
        fd.append('youtube_url', this.youtubeUrl);
        fd.append('volume', this.volume);
        fd.append('active_sound', activeSound || '');
        const base = window.APP_URL || document.body?.dataset?.baseUrl || '';
        if (base) {
            api(`${base}/api/focus/ambient`, { method: 'POST', body: fd }).catch(() => {});
        }
    },
};
