<template>
    <aside class="fixed right-6 top-1/2 -translate-y-1/2 z-[150] w-64 flex flex-col gap-4">
        <div class="bg-[#050914]/40 backdrop-blur-md border border-white/5 p-4 rounded-2xl">
            <h3 class="text-[10px] font-mono text-slate-500 uppercase tracking-widest mb-4">AI_Observer_Logs</h3>

            <!-- Simulace logů -->
            <div class="space-y-2 font-mono text-[9px]">
                <div class="text-emerald-500/80">[OK] Injecting feed_v2.0</div>
                <div class="text-sky-500/80">[OK] User_Auth_Verified</div>
                <div class="text-purple-500/80 animate-pulse">[WAIT] Analyzing sentiment...</div>
                <div class="text-slate-600">> Waiting for uplink_request</div>
            </div>
        </div>

        <!-- Graf vytížení (jen vizuální fake) -->
        <div class="bg-[#050914]/40 backdrop-blur-md border border-white/5 p-4 rounded-2xl h-32 flex items-end gap-1">
            <div v-for="i in 12" :key="i" class="flex-1 bg-sky-500/20 rounded-t-sm transition-all duration-1000"
                :style="{ height: Math.random() * 100 + '%' }">
            </div>
        </div>
    </aside>
</template>
