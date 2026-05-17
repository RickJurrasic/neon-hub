<script setup>
import { Cpu, Activity, Wifi, Terminal, Zap } from 'lucide-vue-next';
</script>

<template>
    <header class="fixed top-0 left-0 w-full z-[60]">
        <div class="neon-panel-wrapper nav-wrapper h-16 border-b border-white/5">

            <div class="nav-light-scanner"></div>

            <div
                class="neon-glass-core h-full !flex-row items-center justify-between md:justify-start px-4 md:px-12 bg-[#050914]/95">

                <div class="flex items-center gap-2 md:gap-3 md:w-64">
                    <div class="relative group cursor-pointer">
                        <div
                            class="absolute -inset-2 bg-sky-500/20 blur-md rounded-full opacity-0 group-hover:opacity-100 transition-opacity">
                        </div>
                        <Cpu :size="20" class="text-sky-400 relative md:scale-110" />
                    </div>
                    <span class="font-black text-lg md:text-xl uppercase tracking-tighter text-white">
                        Neon<span class="text-sky-400">Hub</span>
                    </span>
                </div>

                <div class="hidden md:flex flex-1 justify-around items-center px-10 lg:px-20">

                    <div class="flex flex-col items-center group cursor-default">
                        <span class="text-[7px] text-slate-500 uppercase font-bold tracking-[0.3em] mb-1">Latency</span>
                        <div class="flex items-center gap-2">
                            <Activity :size="12" class="text-sky-500/50 group-hover:text-sky-400" />
                            <span class="text-xs font-mono text-white tracking-widest">12<span
                                    class="text-[9px] ml-0.5 opacity-50">ms</span></span>
                        </div>
                    </div>

                    <div class="flex flex-col items-center group cursor-default">
                        <span
                            class="text-[7px] text-slate-500 uppercase font-bold tracking-[0.3em] mb-1">System_Uptime</span>
                        <div class="flex items-center gap-2">
                            <Zap :size="12" class="text-fuchsia-500/50 group-hover:text-fuchsia-400" />
                            <span class="text-xs font-mono text-white tracking-widest">99.98<span
                                    class="text-[9px] ml-0.5 opacity-50">%</span></span>
                        </div>
                    </div>

                    <div class="flex flex-col items-center group cursor-default">
                        <span
                            class="text-[7px] text-slate-500 uppercase font-bold tracking-[0.3em] mb-1">Bandwidth</span>
                        <div class="flex items-center gap-2">
                            <Wifi :size="12" class="text-emerald-500/50 group-hover:text-emerald-400" />
                            <span class="text-xs font-mono text-white tracking-widest">850<span
                                    class="text-[9px] ml-0.5 opacity-50">mb/s</span></span>
                        </div>
                    </div>

                    <div class="flex flex-col items-center group cursor-default">
                        <span
                            class="text-[7px] text-slate-500 uppercase font-bold tracking-[0.3em] mb-1">Active_Nodes</span>
                        <div class="flex items-center gap-2">
                            <div class="w-1.5 h-1.5 bg-sky-400 rounded-full animate-ping"></div>
                            <span class="text-xs font-mono text-white tracking-widest">1,204</span>
                        </div>
                    </div>

                </div>

                <div class="flex items-center justify-end gap-3 md:gap-6 md:w-64">
                    <div class="hidden sm:flex flex-col items-end">
                        <span class="text-[9px] font-black text-white/90 tracking-tighter uppercase">Admin_Root</span>
                        <span class="text-[7px] font-mono text-sky-500/50 tracking-widest italic">0x882_ALPHA</span>
                    </div>

                    <button
                        class="p-2 rounded-lg bg-white/5 border border-white/10 hover:border-sky-500/50 transition-all">
                        <Terminal :size="14" class="text-slate-400 hover:text-sky-400" />
                    </button>

                    <div
                        class="w-8 h-8 md:w-9 md:h-9 rounded-full bg-gradient-to-tr from-fuchsia-600 to-sky-400 p-[1px] shadow-[0_0_15px_rgba(56,189,248,0.2)] shrink-0">
                        <div class="w-full h-full rounded-full bg-[#050914] flex items-center justify-center">
                            <span class="text-[9px] md:text-[10px] font-bold text-white">RH</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </header>
</template>

<style scoped>
.nav-wrapper {
    padding: 0;
    position: relative;
    background: transparent;
}

.nav-light-scanner {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    height: 1px;
    transform: translateY(-50%);
    background: linear-gradient(90deg,
            rgba(56, 189, 248, 1) 0%,
            rgba(192, 43, 155, 1) 25%,
            rgba(56, 189, 248, 1) 50%,
            rgba(192, 43, 155, 1) 75%,
            rgba(56, 189, 248, 1) 100%);
    background-size: 200% 100%;
    animation: liquid-line 8s linear infinite;
    pointer-events: none;
    box-shadow: 0 0 12px rgba(56, 189, 248, 0.5);
    z-index: 70;
}

@keyframes liquid-line {
    from {
        background-position: 0% 0;
    }

    to {
        background-position: 200% 0;
    }
}

.neon-glass-core {
    @apply border-b border-white/5;
}
</style>
