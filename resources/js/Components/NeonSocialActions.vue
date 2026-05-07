<template>
    <aside class="fixed left-6 top-1/2 -translate-y-1/2 z-[150] flex flex-col gap-6">
        <!-- PŘÁTELÉ / NODES -->
        <div class="action-group group">
            <div class="glow-bg group-hover:bg-sky-500/20"></div>
            <div class="relative flex flex-col items-center">
                <span class="text-[10px] font-mono text-sky-400/50 mb-1">NODES</span>
                <div class="text-xl">👥</div> <!-- Sem pak přijde ikona -->
                <div class="badge bg-sky-500 shadow-[0_0_10px_#0ea5e9]">12</div>
            </div>
        </div>

        <!-- ZPRÁVY / UPLINK -->
        <div class="action-group group">
            <div class="glow-bg group-hover:bg-purple-500/20"></div>
            <div class="relative flex flex-col items-center">
                <span class="text-[10px] font-mono text-purple-400/50 mb-1">UPLINK</span>
                <div class="text-xl">💬</div>
                <div class="badge bg-purple-500 shadow-[0_0_10px_#a855f7]">5</div>
            </div>
        </div>

        <!-- NOTIFIKACE / ALERTS -->
        <div class="action-group group">
            <div class="glow-bg group-hover:bg-amber-500/20"></div>
            <div class="relative flex flex-col items-center">
                <span class="text-[10px] font-mono text-amber-400/50 mb-1">ALERTS</span>
                <div class="text-xl">🔔</div>
            </div>
        </div>
    </aside>
</template>

<style scoped>
.action-group {
    @apply relative p-4 bg-[#050914]/60 backdrop-blur-md border border-white/5 rounded-2xl cursor-pointer transition-all duration-500 hover:scale-110;
}

.glow-bg {
    @apply absolute inset-0 blur-xl opacity-0 transition-opacity duration-500 rounded-2xl;
}

.badge {
    @apply absolute -top-1 -right-1 w-5 h-5 flex items-center justify-center rounded-full text-[10px] font-bold text-white;
}
</style>
