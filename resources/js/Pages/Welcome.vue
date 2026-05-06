<script setup>
import { ref } from 'vue';

const isOpened = ref(false);

const openSystem = () => {
    isOpened.value = true;
};
</script>

<template>
    <div class="relative min-h-screen bg-[#02040a] overflow-hidden font-sans text-slate-200 selection:bg-purple-900/40 selection:text-white">

        <div
            class="absolute inset-0 transition-all duration-[1200ms] delay-700 ease-out"
            :class="[isOpened ? 'opacity-100 scale-100 blur-0' : 'opacity-0 scale-95 blur-md']"
        >
            <nav class="flex items-center justify-between px-8 py-6 border-b border-white/5 bg-[#02040a]/80 backdrop-blur-lg">
                <div class="flex items-center gap-3 font-bold tracking-tighter text-xl uppercase leading-none">
                    NEON<span class="text-sky-400">HUB</span>
                </div>
                <div class="text-[10px] font-mono text-purple-400/50 tracking-[0.2em]">CORE_AUTH_SUCCESS</div>
            </nav>

            <main class="max-w-6xl mx-auto px-6 pt-40 text-center">
                <h2 class="text-7xl font-black text-white mb-6 tracking-tighter uppercase">System Fully Operational</h2>
                <p class="text-slate-500 text-xl font-light italic leading-relaxed max-w-2xl mx-auto uppercase tracking-widest opacity-80">
                    "Precision engineering with a touch of neon violet."
                </p>
            </main>
        </div>

        <div class="absolute inset-0 z-[100] flex pointer-events-none">
            <div
                class="w-1/2 h-full bg-[#03060f] border-r-[1px] border-sky-500/10 transition-transform duration-[1800ms] ease-in-out pointer-events-auto flex items-start justify-end"
                :style="isOpened ? 'transform: translateX(-100%)' : 'transform: translateX(0)'"
            >
                <div class="pr-12 mt-[15vh]">
                    <h1 class="text-7xl md:text-9xl font-black text-white tracking-tighter select-none leading-none opacity-90">NEON</h1>
                </div>
            </div>

            <div
                class="w-1/2 h-full bg-[#03060f] border-l-[1px] border-sky-500/10 transition-transform duration-[1800ms] ease-in-out pointer-events-auto flex items-start justify-start"
                :style="isOpened ? 'transform: translateX(100%)' : 'transform: translateX(0)'"
            >
                <div class="pl-12 mt-[15vh]">
                    <h1 class="text-7xl md:text-9xl font-black text-sky-400 tracking-tighter select-none leading-none opacity-90 uppercase shadow-sky-500/10">HUB</h1>
                </div>
            </div>
        </div>

        <div
            class="absolute inset-0 z-[120] flex flex-col pointer-events-none transition-all duration-[1000ms] ease-in-out"
            :class="[isOpened ? 'opacity-0 translate-y-8 pointer-events-none' : 'opacity-100 translate-y-0']"
        >
            <div class="h-[35vh]"></div>

            <div class="flex-1 flex items-center justify-center w-full pointer-events-auto px-6">
                <div class="relative group p-[2px] rounded-[2.5rem] overflow-hidden max-w-lg w-full shadow-2xl shadow-purple-900/20 scale-[0.92]">

                    <div class="rotating-border absolute inset-[-100%] opacity-70 group-hover:opacity-100 transition-opacity"></div>

                    <div class="relative bg-[#050914]/85 backdrop-blur-[40px] p-10 md:p-12 rounded-[2.4rem] border border-white/5">
                        <h2 class="text-purple-400 font-bold text-[9px] uppercase tracking-[1em] mb-10 text-center opacity-80">Fullstack_Core_Manifesto</h2>

                        <div class="space-y-8">
                            <div class="flex items-center gap-6">
                                <span class="text-sky-500/50 font-mono text-sm mt-1">01</span>
                                <p class="text-slate-100 text-xl md:text-2xl font-light tracking-tight">
                                    <span class="text-white font-bold border-b border-sky-500/30">Bleeding Edge:</span> Laravel 13 Core
                                </p>
                            </div>
                            <div class="flex items-center gap-6 border-t border-white/5 pt-8">
                                <span class="text-sky-500/50 font-mono text-sm mt-1">02</span>
                                <p class="text-slate-100 text-xl md:text-2xl font-light tracking-tight">
                                    <span class="text-white font-bold border-b border-sky-500/30">Interface:</span> Vue 3 & Tailwind CSS
                                </p>
                            </div>
                            <div class="flex items-center gap-6 border-t border-white/5 pt-8">
                                <span class="text-sky-500/50 font-mono text-sm mt-1">03</span>
                                <p class="text-slate-100 text-xl md:text-2xl font-light tracking-tight">
                                    <span class="text-white font-bold border-b border-sky-500/30">Architecture:</span> Clean & Scalable
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="h-[28vh] flex flex-col items-center justify-center pointer-events-auto">
                <button
                    @click="openSystem"
                    class="group relative px-16 py-6 bg-sky-500 text-black font-black uppercase tracking-[0.6em] text-xs hover:bg-white transition-all duration-500 shadow-[0_0_50px_rgba(56,189,248,0.4)] active:scale-95"
                >
                    ENTER SYSTEM
                    <div class="absolute -inset-2 bg-sky-400/20 blur-2xl opacity-0 group-hover:opacity-100 transition-opacity"></div>
                </button>
                <p class="mt-6 text-slate-600 text-[9px] uppercase tracking-[0.8em] animate-pulse font-bold tracking-widest">
                    Identity Verified // Waiting for Input
                </p>
            </div>
        </div>

    </div>
</template>

<style scoped>
.rotating-border {
    background: conic-gradient(
        from 0deg,
        transparent 0%,
        rgba(194, 49, 162, 0.44) 25%,
        rgba(56, 189, 248, 0.4) 50%,
        rgba(192, 43, 155, 0.4) 75%,
        transparent 100%
    );
    animation: rotate 8s linear infinite;
}

@keyframes rotate {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

h1 {
    text-shadow: 0 0 60px rgba(56, 189, 248, 0.1);
}
</style>
