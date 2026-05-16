<?php partial('group-header', ['group' => $group]); ?>

<div x-data="groupChat(<?= (int)$group['id'] ?>)" class="flex flex-col h-[calc(100vh-12rem)] glass-card rounded-2xl overflow-hidden">
    <header class="px-4 py-3 border-b border-slate-200/50 dark:border-white/10 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            <span class="text-sm font-medium"><?= count($members) ?> members online</span>
        </div>
        <span x-show="typing" class="text-xs text-slate-400 italic">Someone is typing...</span>
    </header>

    <div id="chat-messages" class="flex-1 overflow-y-auto p-4 space-y-4">
        <?php foreach ($messages as $m): $isMe = (int)$m['user_id'] === \App\Auth::id(); ?>
        <div class="chat-message flex gap-3 <?= $isMe ? 'flex-row-reverse' : '' ?>">
            <img src="<?= avatar_url($m['avatar'] ?? null) ?>" class="w-8 h-8 rounded-full shrink-0" alt="">
            <div class="<?= $isMe ? 'items-end' : '' ?> flex flex-col max-w-[75%]">
                <span class="text-xs text-slate-400 mb-1 <?= $isMe ? 'text-right' : '' ?>"><?= e($m['name']) ?> · <?= time_ago($m['created_at']) ?></span>
                <p class="px-4 py-2.5 rounded-2xl text-sm <?= $isMe ? 'bg-indigo-600 text-white rounded-br-md' : 'bg-slate-100 dark:bg-white/10 rounded-bl-md' ?>"><?= e($m['body']) ?></p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <form @submit.prevent="send()" class="p-4 border-t border-slate-200/50 dark:border-white/10 flex gap-2">
        <input x-model="body" @input="typing=true; clearTimeout(typingTimer); typingTimer=setTimeout(()=>typing=false,1500)"
            type="text" placeholder="Message #<?= e($group['name']) ?>..." class="input flex-1" autocomplete="off">
        <button type="submit" class="btn-primary px-4"><i data-lucide="send" class="w-4 h-4"></i></button>
    </form>
</div>

<script>
function groupChat(groupId) {
    return {
        groupId, body: '', lastId: <?= !empty($messages) ? (int)end($messages)['id'] : 0 ?>,
        typing: false, typingTimer: null, pollTimer: null,
        init() {
            this.scrollBottom();
            this.pollTimer = setInterval(() => this.poll(), 3000);
        },
        scrollBottom() {
            const el = document.getElementById('chat-messages');
            if (el) el.scrollTop = el.scrollHeight;
        },
        async poll() {
            try {
                const data = await api(`${window.APP_URL}/groups/${this.groupId}/chat/messages?after=${this.lastId}`);
                (data.messages || []).forEach(m => this.appendMessage(m));
            } catch {}
        },
        appendMessage(m) {
            if (m.id <= this.lastId) return;
            this.lastId = m.id;
            const el = document.getElementById('chat-messages');
            const row = document.createElement('div');
            row.className = 'chat-message flex gap-3';
            row.innerHTML = '<img src="' + window.APP_URL + '/assets/img/default-avatar.svg" class="w-8 h-8 rounded-full"><section><span class="text-xs text-slate-400">' + m.name + '</span><p class="px-4 py-2 rounded-2xl text-sm bg-slate-100 dark:bg-white/10 mt-1">' + m.body + '</p></section>';
            el.appendChild(row);
            this.scrollBottom();
        },
        async send() {
            if (!this.body.trim()) return;
            const fd = new FormData();
            fd.append('_csrf', CSRF);
            fd.append('body', this.body);
            const data = await api(`${window.APP_URL}/groups/${this.groupId}/chat`, { method: 'POST', body: fd });
            this.body = '';
            this.appendMessage(data.message);
        },
    };
}
</script>
