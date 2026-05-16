<header class="sticky top-0 z-30 glass-topbar border-b border-slate-200/50 dark:border-white/10 px-4 sm:px-6 h-16 flex items-center justify-between gap-6 w-full" x-data="topbar()">
    <!-- Search — far left -->
    <div class="relative w-full max-w-xs sm:max-w-sm md:max-w-md lg:max-w-xl flex-shrink-0">
        <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"></i>
        <input type="search" x-model="query" @input.debounce.300ms="search()" placeholder="Search groups, tasks..."
            class="w-full pl-10 pr-4 py-2 rounded-xl bg-slate-100/80 dark:bg-white/5 border border-transparent dark:border-white/10 text-slate-900 dark:text-slate-100 text-sm placeholder:text-slate-400 focus:ring-2 focus:ring-indigo-500/50">
        <div x-show="results.length" @click.away="results=[]" x-cloak class="dropdown-panel glass-card absolute top-full left-0 mt-2 w-full min-w-[280px] rounded-2xl overflow-hidden z-50">
            <template x-for="r in results" :key="r.id + r.type">
                <a :href="r.type === 'group' ? '<?= url('/groups/') ?>'+r.id : '<?= url('/groups/') ?>'+r.group_id+'/tasks'" class="dropdown-item" x-text="r.title || r.name"></a>
            </template>
        </div>
    </div>

    <!-- Actions — far right -->
    <div class="flex items-center gap-1 sm:gap-2 flex-shrink-0 ml-auto">
        <button @click="toggleTheme()" class="p-2.5 rounded-xl hover:bg-slate-100 dark:hover:bg-white/10 transition-colors" type="button" title="Toggle theme">
            <i data-lucide="sun" class="w-5 h-5 hidden dark:block"></i>
            <i data-lucide="moon" class="w-5 h-5 block dark:hidden"></i>
        </button>
        <div class="relative" x-data="{ open: false }">
            <button @click="open=!open; loadNotifications()" class="relative p-2.5 rounded-xl hover:bg-slate-100 dark:hover:bg-white/10 transition-colors" type="button" title="Notifications">
                <i data-lucide="bell" class="w-5 h-5"></i>
                <span x-show="unread>0" x-text="unread" class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] flex items-center justify-center text-[10px] font-bold bg-rose-500 text-white rounded-full px-1"></span>
            </button>
            <div x-show="open" @click.away="open=false" x-cloak class="dropdown-panel glass-card absolute right-0 mt-2 w-80 rounded-2xl overflow-hidden">
                <div class="dropdown-header">
                    <span>Notifications</span>
                    <button type="button" @click="markAllRead()" class="text-xs text-indigo-500 hover:text-indigo-400 font-medium">Mark all read</button>
                </div>
                <div class="max-h-72 overflow-y-auto">
                    <template x-if="!notifications.length"><p class="dropdown-empty">No notifications</p></template>
                    <template x-for="n in notifications" :key="n.id">
                        <a :href="n.link||'#'" class="dropdown-item border-b border-slate-100 dark:border-white/5 last:border-0">
                            <p class="font-medium" x-text="n.title"></p>
                            <p class="dropdown-item-sub mt-0.5" x-text="n.body"></p>
                        </a>
                    </template>
                </div>
            </div>
        </div>
        <a href="<?= url('/logout') ?>" class="p-2.5 rounded-xl hover:bg-slate-100 dark:hover:bg-white/10 text-slate-500 hover:text-rose-500 dark:text-slate-400 transition-colors" title="Sign out">
            <i data-lucide="log-out" class="w-5 h-5"></i>
        </a>
    </div>
</header>
