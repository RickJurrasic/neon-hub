<script setup>
import { onMounted, ref } from 'vue';
import { Cpu, Activity } from '@lucide/vue';

// Simulace náhodných grafů
const bars = ref(Array(15).fill(40));
onMounted(() => {
    setInterval(() => {
        bars.value = bars.value.map(() => Math.floor(Math.random() * 80) + 20);
    }, 2000);
});
</script>

<template>
    <aside class="fixed right-8 top-1/2 -translate-y-1/2 z-50">
        <!-- MASTER WRAPPER (Větší šířka pro data) -->
        <div class="neon-panel-wrapper h-[80vh] w-72 rounded-[3rem]">

            <div class="neon-border-active"></div>

            <div class="neon-glass-core rounded-[3rem] py-16 flex flex-col items-center justify-between h-full">

                <!-- HEADER SEKTCE -->
                <div class="flex items-center gap-3 border-b border-white/5 pb-4">
                    <Cpu class="text-sky-400 animate-pulse" :size="18" />
                    <h3 class="text-[10px] font-black uppercase tracking-[0.3em] text-white/70">
                        Core_Engine_v.13
                    </h3>
                </div>

                <!-- 1. SEKCE: AI OBSERVER LOGS -->
                <div class="flex-1 overflow-hidden">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-[8px] text-slate-500 uppercase tracking-widest font-bold">Observer_Logs</span>
                        <span class="text-[8px] text-emerald-500 animate-pulse font-mono">LIVE</span>
                    </div>
                    <div class="space-y-3 font-mono text-[9px] bg-black/20 p-3 rounded-xl border border-white/5">
                        <div class="flex gap-2">
                            <span class="text-emerald-500/50">01</span>
                            <span class="text-slate-300">[OK] Injecting feed_v2.0</span>
                        </div>
                        <div class="flex gap-2">
                            <span class="text-sky-500/50">02</span>
                            <span class="text-slate-300">[OK] Auth_Gateway_Secure</span>
                        </div>
                        <div class="flex gap-2">
                            <span class="text-purple-500/50">03</span>
                            <span class="text-purple-400 animate-pulse">Analyzing_Sentiment...</span>
                        </div>
                        <div class="flex gap-2">
                            <span class="text-slate-700">04</span>
                            <span class="text-slate-600">> Waiting for uplink...</span>
                        </div>
                    </div>
                </div>

                <!-- 2. SEKCE: ACTIVITY GRAPH -->
                <div class="h-32">
                    <div class="flex items-center gap-2 mb-3">
                        <Activity class="text-fuchsia-500" :size="14" />
                        <span class="text-[8px] text-slate-500 uppercase tracking-widest font-bold">Neural_Load</span>
                    </div>
                    <div class="h-16 flex items-end gap-1.5 px-2">
                        <div v-for="(height, i) in bars" :key="i"
                            class="flex-1 bg-gradient-to-t from-fuchsia-600/40 to-sky-400/60 rounded-full transition-all duration-[2000ms] ease-in-out"
                            :style="{ height: height + '%' }">
                        </div>
                    </div>
                    <div class="flex justify-between mt-2 font-mono text-[7px] text-slate-600">
                        <span>0.0ms</span>
                        <span>4.2ghz</span>
                        <span>128-bit</span>
                    </div>
                </div>

                <!-- 3. SEKCE: TECH STACK STATUS -->
                <div class="pt-4 border-t border-white/5">
                    <div class="grid grid-cols-2 gap-2">
                        <div class="bg-white/5 p-2 rounded-lg border border-white/5 flex flex-col">
                            <span class="text-[7px] text-slate-500 uppercase">Laravel</span>
                            <span class="text-[9px] text-sky-400 font-bold tracking-tighter italic">V.13.0.2</span>
                        </div>
                        <div class="bg-white/5 p-2 rounded-lg border border-white/5 flex flex-col">
                            <span class="text-[7px] text-slate-500 uppercase">Vue_Core</span>
                            <span class="text-[9px] text-fuchsia-400 font-bold tracking-tighter italic">V.3.4.0</span>
                        </div>
                    </div>
                </div>

                <!-- BOTTOM STATUS -->
                <div class="mt-2 flex justify-between items-center opacity-40">
                    <div class="flex gap-1">
                        <div class="w-1 h-1 bg-sky-500 rounded-full"></div>
                        <div class="w-1 h-1 bg-sky-500 rounded-full"></div>
                        <div class="w-1 h-1 bg-slate-700 rounded-full"></div>
                    </div>
                    <span class="text-[8px] font-mono text-slate-500">SYS_TEMP: 32°C</span>
                </div>

            </div>
        </div>
    </aside>
</template>

<style scoped>
/* Pokud chceš ještě nějaký extra detail, tak třeba jemný scanline efekt přes logy */
.font-mono {
    text-shadow: 0 0 5px rgba(56, 189, 248, 0.3);
}
</style>
