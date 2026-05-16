<div id="achievement-overlay" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/60 backdrop-blur-sm" style="display:none">
    <div class="achievement-popup glass-card rounded-3xl p-8 max-w-sm mx-4 text-center shadow-2xl border border-amber-500/30">
        <div class="w-20 h-20 mx-auto mb-4 rounded-2xl bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center achievement-glow">
            <i id="achievement-icon" data-lucide="trophy" class="w-10 h-10 text-white"></i>
        </div>
        <p class="text-xs font-bold uppercase tracking-widest text-amber-500 mb-1">Achievement Unlocked</p>
        <h3 id="achievement-name" class="text-xl font-bold mb-2"></h3>
        <p id="achievement-desc" class="text-sm text-slate-400 mb-4"></p>
        <button onclick="closeAchievement()" class="btn-primary w-full">Awesome!</button>
    </div>
</div>

