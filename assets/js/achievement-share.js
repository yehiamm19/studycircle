/**
 * Profile — Achievement share card → PNG + Study Story upload.
 * Expects sibling JSON in #achievement-share-boot.
 */
(() => {
    const BOOT_ID = 'achievement-share-boot';

    /** App runs under subdirectory (e.g. /studycircle) — same as api() URLs in app.js */
    function appHref(pathname) {
        if (typeof window.syncAppUrl === 'function') {
            window.syncAppUrl();
        }
        let raw =
            typeof window.APP_URL !== 'undefined' && window.APP_URL !== null
                ? String(window.APP_URL)
                : '';
        if (raw === '') {
            raw = String(document.body?.dataset?.baseUrl || '');
        }
        const base = raw.replace(/\/$/, '');
        const p = pathname.startsWith('/') ? pathname : `/${pathname}`;
        return `${base}${p}`;
    }

    /** Map Lucide icon names to emoji for offline canvas rendering */
    const ICON_EMOJI = {
        trophy: '🏆',
        star: '⭐',
        zap: '⚡',
        flame: '🔥',
        target: '🎯',
        book: '📚',
        'book-open': '📖',
        clock: '⏰',
        award: '🏅',
        medal: '🥇',
        badge: '🎖️',
        crown: '👑',
        heart: '💜',
        sparkles: '✨',
        sun: '🌟',
        moon: '🌙',
        shield: '🛡️',
        'check-circle': '✅',
        check: '✔️',
        thumbs: '👍',
        'graduation-cap': '🎓',
        brain: '🧠',
        users: '👥',
        'message-circle': '💬',
    };

    const W = 1080;
    const H = 1920;

    function readBoot() {
        const el = document.getElementById(BOOT_ID);
        if (!el?.textContent?.trim()) return null;
        try {
            return JSON.parse(el.textContent.trim());
        } catch {
            return null;
        }
    }

    const boot = readBoot();
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';

    function roundRectPath(ctx, x, y, w, h, r) {
        const rr = Math.min(r, w / 2, h / 2);
        ctx.beginPath();
        ctx.moveTo(x + rr, y);
        ctx.lineTo(x + w - rr, y);
        ctx.quadraticCurveTo(x + w, y, x + w, y + rr);
        ctx.lineTo(x + w, y + h - rr);
        ctx.quadraticCurveTo(x + w, y + h, x + w - rr, y + h);
        ctx.lineTo(x + rr, y + h);
        ctx.quadraticCurveTo(x, y + h, x, y + h - rr);
        ctx.lineTo(x, y + rr);
        ctx.quadraticCurveTo(x, y, x + rr, y);
        ctx.closePath();
    }

    function wrapTextLines(ctx, text, maxWidth) {
        const words = String(text || '')
            .replace(/\s+/g, ' ')
            .trim()
            .split(' ');
        if (!words[0]) return [];
        const lines = [];
        let line = '';
        for (const w of words) {
            const test = line ? `${line} ${w}` : w;
            if (ctx.measureText(test).width <= maxWidth) {
                line = test;
            } else {
                if (line) lines.push(line);
                line = w;
            }
        }
        if (line) lines.push(line);
        return lines;
    }

    function loadImage(src) {
        return new Promise((resolve) => {
            const img = new Image();
            try {
                const cur = new URL(window.location.href);
                const u = new URL(src, window.location.href);
                if (u.origin !== cur.origin) {
                    img.crossOrigin = 'anonymous';
                }
            } catch {
                /* ignore URL parse */
            }
            img.onload = () => resolve(img);
            img.onerror = () => resolve(null);
            img.src = src;
        });
    }

    function iconEmoji(icon) {
        const k = String(icon || '').toLowerCase();
        return ICON_EMOJI[k] || '🏆';
    }

    function formatXp(n) {
        const x = Number(n) || 0;
        return x.toLocaleString('en-US');
    }

    async function renderAchievementPng(bootCfg, ach) {
        const canvas = document.createElement('canvas');
        canvas.width = W;
        canvas.height = H;
        const ctx = canvas.getContext('2d');
        if (!ctx) throw new Error('Canvas not supported');

        document.fonts?.ready?.catch?.(() => {});

        const g0 = ctx.createLinearGradient(0, 0, W, H);
        g0.addColorStop(0, '#1e1b4b');
        g0.addColorStop(0.45, '#312e81');
        g0.addColorStop(0.78, '#4c1d95');
        g0.addColorStop(1, '#0f172a');
        ctx.fillStyle = g0;
        ctx.fillRect(0, 0, W, H);

        const orb = ctx.createRadialGradient(W * 0.85, H * 0.12, 0, W * 0.85, H * 0.12, W * 0.55);
        orb.addColorStop(0, 'rgba(167, 139, 250, 0.35)');
        orb.addColorStop(1, 'rgba(15, 23, 42, 0)');
        ctx.fillStyle = orb;
        ctx.fillRect(0, 0, W, H);

        const orb2 = ctx.createRadialGradient(W * 0.1, H * 0.55, 0, W * 0.1, H * 0.55, W * 0.5);
        orb2.addColorStop(0, 'rgba(99, 102, 241, 0.22)');
        orb2.addColorStop(1, 'rgba(15, 23, 42, 0)');
        ctx.fillStyle = orb2;
        ctx.fillRect(0, 0, W, H);

        const [logoImg, avatarImg] = await Promise.all([loadImage(bootCfg.logoUrl), loadImage(bootCfg.avatarUrl)]);

        const pad = 64;
        let y = pad + 20;

        if (logoImg) {
            const lw = 420;
            const lh = (logoImg.height / logoImg.width) * lw;
            ctx.drawImage(logoImg, (W - lw) / 2, y, lw, lh);
            y += lh + 36;
        } else {
            ctx.font = '800 38px "Plus Jakarta Sans", system-ui, sans-serif';
            ctx.fillStyle = '#c4b5fd';
            ctx.textAlign = 'center';
            ctx.fillText(bootCfg.appName || 'StudyCircle', W / 2, y + 40);
            y += 88;
        }

        const rank = Number(bootCfg.rank) || 0;
        const total = Number(bootCfg.totalCampusUsers) || 0;
        const rankLabel =
            rank > 0 && total > 0
                ? `Rank #${rank} of ${total} on campus`
                : rank > 0
                  ? `Rank #${rank} on campus`
                  : '';

        if (rankLabel) {
            ctx.save();
            ctx.font = '600 28px "Plus Jakarta Sans", system-ui, sans-serif';
            const tw = Math.min(ctx.measureText(rankLabel).width + 56, W - pad * 2);
            const th = 52;
            const rx = (W - tw) / 2;
            const ry = y;
            roundRectPath(ctx, rx, ry, tw, th, 26);
            ctx.fillStyle = 'rgba(255,255,255,0.1)';
            ctx.fill();
            ctx.strokeStyle = 'rgba(196, 181, 253, 0.45)';
            ctx.lineWidth = 1.5;
            ctx.stroke();
            ctx.fillStyle = '#e9d5ff';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText(rankLabel, W / 2, ry + th / 2);
            ctx.restore();
            y += th + 32;
        } else {
            y += 8;
        }

        const avSize = 200;
        const avX = (W - avSize) / 2;
        ctx.save();
        ctx.beginPath();
        ctx.arc(avX + avSize / 2, y + avSize / 2, avSize / 2 + 6, 0, Math.PI * 2);
        const ringG = ctx.createLinearGradient(avX, y, avX + avSize, y + avSize);
        ringG.addColorStop(0, '#c4b5fd');
        ringG.addColorStop(1, '#6366f1');
        ctx.strokeStyle = ringG;
        ctx.lineWidth = 6;
        ctx.stroke();
        ctx.restore();

        ctx.save();
        ctx.beginPath();
        ctx.arc(avX + avSize / 2, y + avSize / 2, avSize / 2, 0, Math.PI * 2);
        ctx.clip();
        if (avatarImg) {
            ctx.drawImage(avatarImg, avX, y, avSize, avSize);
        } else {
            ctx.fillStyle = '#334155';
            ctx.fillRect(avX, y, avSize, avSize);
            ctx.font = '700 72px "Plus Jakarta Sans", system-ui, sans-serif';
            ctx.fillStyle = '#cbd5f5';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText(
                String(bootCfg.userName || '?')
                    .split(/\s+/)
                    .map((w) => w[0])
                    .join('')
                    .slice(0, 2)
                    .toUpperCase(),
                avX + avSize / 2,
                y + avSize / 2
            );
        }
        ctx.restore();

        y += avSize + 28;

        ctx.textAlign = 'center';
        ctx.textBaseline = 'top';
        ctx.fillStyle = '#f8fafc';
        ctx.font = '800 46px "Plus Jakarta Sans", system-ui, sans-serif';
        const name = String(bootCfg.userName || 'Student');
        let displayName = name;
        if (ctx.measureText(displayName).width > W - pad * 2) {
            while (displayName.length > 2 && ctx.measureText(`${displayName}…`).width > W - pad * 2) {
                displayName = displayName.slice(0, -1);
            }
            displayName += '…';
        }
        ctx.fillText(displayName, W / 2, y);
        y += 58;

        ctx.font = '600 32px "Plus Jakarta Sans", system-ui, sans-serif';
        ctx.fillStyle = '#a5b4fc';
        ctx.fillText(`${formatXp(bootCfg.xp)} XP`, W / 2, y);
        y += 56;

        const barY = y;
        const barG = ctx.createLinearGradient(pad, barY, W - pad, barY);
        barG.addColorStop(0, 'rgba(99,102,241,0)');
        barG.addColorStop(0.5, 'rgba(196,181,253,0.85)');
        barG.addColorStop(1, 'rgba(99,102,241,0)');
        ctx.strokeStyle = barG;
        ctx.lineWidth = 2;
        ctx.beginPath();
        ctx.moveTo(pad, barY);
        ctx.lineTo(W - pad, barY);
        ctx.stroke();
        y += 48;

        const emoji = iconEmoji(ach.icon);
        ctx.font = '140px system-ui, sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText(emoji, W / 2, y);
        y += 160;

        ctx.fillStyle = '#fde68a';
        ctx.font = '800 22px "Plus Jakarta Sans", system-ui, sans-serif';
        ctx.fillText('ACHIEVEMENT', W / 2, y);
        y += 44;

        ctx.fillStyle = '#fefce8';
        ctx.font = '800 48px "Plus Jakarta Sans", system-ui, sans-serif';
        const title = String(ach.name || 'Achievement');
        const titleLines = wrapTextLines(ctx, title, W - pad * 2).slice(0, 2);
        for (const tl of titleLines) {
            ctx.fillText(tl, W / 2, y);
            y += 54;
        }
        y += 10;

        ctx.font = '500 28px "Plus Jakarta Sans", system-ui, sans-serif';
        ctx.fillStyle = 'rgba(226, 232, 240, 0.88)';
        const desc = String(ach.description || '');
        const descLines = wrapTextLines(ctx, desc, W - pad * 2).slice(0, 4);
        const lineH = 36;
        for (const dl of descLines) {
            ctx.fillText(dl, W / 2, y);
            y += lineH;
        }

        y = H - pad - 120;
        roundRectPath(ctx, pad, y, W - pad * 2, 88, 20);
        ctx.fillStyle = 'rgba(15, 23, 42, 0.55)';
        ctx.fill();
        ctx.strokeStyle = 'rgba(148, 163, 184, 0.35)';
        ctx.lineWidth = 1;
        ctx.stroke();

        ctx.font = '600 24px ui-monospace, monospace';
        ctx.fillStyle = '#cbd5e1';
        let urlText = String(bootCfg.profileUrl || '').replace(/^https?:\/\//, '');
        if (urlText.length > 48) urlText = `${urlText.slice(0, 22)}…${urlText.slice(-20)}`;
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(urlText, W / 2, y + 44);

        ctx.font = '500 20px "Plus Jakarta Sans", system-ui, sans-serif';
        ctx.fillStyle = 'rgba(148, 163, 184, 0.75)';
        ctx.fillText(`Shared from ${bootCfg.appName || 'StudyCircle'}`, W / 2, y - 28);

        return new Promise((resolve, reject) => {
            canvas.toBlob(
                (blob) => {
                    if (blob) resolve(blob);
                    else reject(new Error('Could not create image'));
                },
                'image/png',
                0.92
            );
        });
    }

    /** @type {Blob | null} */
    let lastBlob = null;
    /** @type {Record<string,string>|null} */
    let lastAch = null;

    async function ensureDialog() {
        const dlg = document.getElementById('achievement-share-dialog');
        const preview = document.getElementById('achievement-share-preview');
        if (!dlg || !preview || !boot) return null;
        return { dlg, preview };
    }

    async function openForAchievement(ach) {
        lastAch = ach;
        const ui = await ensureDialog();
        if (!ui || !boot) return;
        const { dlg, preview } = ui;

        const loadingEl = document.getElementById('achievement-share-loading');
        loadingEl?.classList?.remove('hidden');
        preview.classList.add('hidden');
        preview.alt = '';

        await document.fonts?.ready?.catch(() => {});

        try {
            lastBlob = await renderAchievementPng(boot, ach);
            if (preview.src && preview.src.startsWith('blob:')) {
                URL.revokeObjectURL(preview.src);
            }
            preview.src = URL.createObjectURL(lastBlob);
            preview.classList.remove('hidden');
        } catch {
            preview.removeAttribute('src');
            preview.alt = '';
            showToastInline('Could not build share image.', 'error');
            loadingEl?.classList?.add('hidden');
            return;
        }

        loadingEl?.classList?.add('hidden');

        if (!dlg.open) dlg.showModal();
        try {
            if (typeof lucide !== 'undefined') lucide.createIcons();
        } catch {
            /* ignore */
        }
    }

    function showToastInline(message, type) {
        if (typeof showToast === 'function') {
            showToast(message, type);
        } else {
            window.alert(message);
        }
    }

    function closeDialog() {
        const dlg = document.getElementById('achievement-share-dialog');
        if (dlg?.open) dlg.close();
    }

    document.addEventListener('click', (e) => {
        const t = e.target.closest('.achievement-share-trigger');
        if (!t || !boot) return;
        let ach;
        try {
            ach = JSON.parse(t.getAttribute('data-achievement') || '{}');
        } catch {
            return;
        }
        openForAchievement(ach);
    });

    /** Close when clicking X or child icon (Lucide renders SVG inside button) */
    document.getElementById('achievement-share-dialog')?.addEventListener('click', (e) => {
        if (e.target.closest('[data-achievement-share-close]')) {
            e.preventDefault();
            closeDialog();
        }
    });

    document.querySelector('[data-achievement-share-download]')?.addEventListener('click', () => {
        if (!lastBlob || !lastAch) return;
        const a = document.createElement('a');
        const slug = String(lastAch.slug || 'achievement').replace(/[^a-z0-9-_]+/gi, '-');
        a.download = `studycircle-achievement-${slug}.png`;
        a.href = URL.createObjectURL(lastBlob);
        a.click();
        setTimeout(() => URL.revokeObjectURL(a.href), 2000);
        showToastInline('Download started.', 'success');
    });

    document.querySelector('[data-achievement-share-copy]')?.addEventListener('click', async () => {
        const link = boot?.profileUrl || '';
        if (!link) return;
        try {
            await navigator.clipboard.writeText(link);
            showToastInline('Profile link copied.', 'success');
        } catch {
            showToastInline('Could not copy link.', 'error');
        }
    });

    document.querySelector('[data-achievement-share-story]')?.addEventListener('click', async () => {
        if (!lastBlob || !lastAch || !boot) return;
        const btn = document.querySelector('[data-achievement-share-story]');
        if (btn?.disabled) return;
        const cap = `${lastAch.name} — shared from my profile (${formatXp(boot.xp)} XP)`;
        const note = lastAch.name;
        btn.disabled = true;
        const prevHtml = btn.innerHTML;
        btn.innerHTML = 'Posting…';

        try {
            const fd = new FormData();
            fd.append('_csrf', CSRF);
            fd.append('photo', new File([lastBlob], 'achievement-story.png', { type: 'image/png' }));
            fd.append('caption', cap.slice(0, 220));
            fd.append('mood', 'achievement');
            fd.append('context_note', note.slice(0, 120));
            fd.append('_redirect', '/stories');

            const res = await fetch(appHref('/stories'), {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
                body: fd,
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) {
                const hint =
                    res.status === 404
                        ? ` Check you are using the real app path (e.g. ${appHref('/stories')}).`
                        : '';
                throw new Error((data.error || data.message || `Upload failed (${res.status}).`) + hint);
            }

            window.location.href = appHref(data.redirect || '/stories');
        } catch (err) {
            showToastInline(err.message || 'Could not post to Study Story', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = prevHtml;
            try {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            } catch {
                /* ignore */
            }
        }
    });


})();
