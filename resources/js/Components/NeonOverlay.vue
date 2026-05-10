<script setup>
defineProps({
    isOpened: Boolean
});

defineEmits(['open']);
</script>

<template>
    <div class="absolute inset-0 flex flex-col pointer-events-none transition-all duration-[1000ms] ease-in-out"
        :class="[isOpened ? 'opacity-0 translate-y-8 pointer-events-none' : 'opacity-100 translate-y-0']">
        <div class="h-[35vh]"></div>

        <!-- KARTA S MANIFESTEM -->
        <div class="flex-1 flex items-center justify-center w-full px-6"
            :class="isOpened ? 'pointer-events-none' : 'pointer-events-auto'">
            <div
                class="relative group p-[2px] rounded-[2.5rem] overflow-hidden max-w-lg w-full shadow-2xl shadow-purple-900/20 scale-[0.92]">
                <div
                    class="rotating-border absolute inset-[-100%] opacity-70 group-hover:opacity-100 transition-opacity">
                </div>

                <div
                    class="relative bg-[#050914]/85 backdrop-blur-[40px] p-10 md:p-12 rounded-[2.4rem] border border-white/5">
                    <h2
                        class="text-purple-400 font-bold text-[9px] uppercase tracking-[1em] mb-10 text-center opacity-80">
                        Fullstack_Core_Manifesto</h2>

                    <div class="space-y-8">
                        <div class="flex items-center gap-6">
                            <span class="text-sky-500/50 font-mono text-sm mt-1">01</span>
                            <p class="text-slate-100 text-xl md:text-2xl font-light tracking-tight">
                                <span class="text-white font-bold border-b border-sky-500/30">Bleeding Edge:</span>
                                Laravel 13 Core
                            </p>
                        </div>
                        <div class="flex items-center gap-6 border-t border-white/5 pt-8">
                            <span class="text-sky-500/50 font-mono text-sm mt-1">02</span>
                            <p class="text-slate-100 text-xl md:text-2xl font-light tracking-tight">
                                <span class="text-white font-bold border-b border-sky-500/30">Interface:</span> Vue 3 &
                                Tailwind CSS
                            </p>
                        </div>
                        <div class="flex items-center gap-6 border-t border-white/5 pt-8">
                            <span class="text-sky-500/50 font-mono text-sm mt-1">03</span>
                            <p class="text-slate-100 text-xl md:text-2xl font-light tracking-tight">
                                <span class="text-white font-bold border-b border-sky-500/30">Architecture:</span> Clean
                                & Scalable
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TLAČÍTKO ENTER -->
        <div class="h-[28vh] flex flex-col items-center justify-center"
            :class="isOpened ? 'pointer-events-none' : 'pointer-events-auto'">
            <button @click="$emit('open')"
                class="group relative px-16 py-6 bg-sky-500 text-black font-black uppercase tracking-[0.6em] text-xs hover:bg-white transition-all duration-500 shadow-[0_0_50px_rgba(56,189,248,0.4)] active:scale-95">
                ENTER SYSTEM
                <div
                    class="absolute -inset-2 bg-sky-400/20 blur-2xl opacity-0 group-hover:opacity-100 transition-opacity">
                </div>
            </button>
            <p
                class="mt-6 text-slate-600 text-[9px] uppercase tracking-[0.8em] animate-pulse font-bold tracking-widest">
                Identity Verified // Waiting for Input
            </p>
        </div>
    </div>
</template>

<style scoped>
.rotating-border {
    background: conic-gradient(from 0deg,
            transparent 0%,
            #c231a2 25%,
            #38bdf8 50%,
            #c02b9b 75%,
            transparent 100%);
    animation: rotate 8s linear infinite;
}

@keyframes rotate {
    from {
        transform: rotate(0deg);
    }

    to {
        transform: rotate(360deg);
    }
}
</style>
