<script setup>
// Data vytažená bokem - čistý kód
const MANIFEST_ITEMS = [
    { t: 'Bleeding Edge', v: 'Laravel 13 Core' },
    { t: 'Interface', v: 'Vue 3 & Tailwind' },
    { t: 'Architecture', v: 'Clean & Scalable' }
];

defineProps({
    isOpened: Boolean
});

defineEmits(['open']);
</script>

<template>
    <div v-if="!isOpened"
        class="fixed inset-0 flex flex-col z-[110] overflow-hidden items-center justify-between bg-black/20">

        <div class="h-[12vh] md:h-[30vh] shrink-0"></div>

        <div class="flex-1 flex items-center justify-center w-full px-5 md:px-6 py-4 shrink-0"
            :class="isOpened ? 'pointer-events-none' : 'pointer-events-auto'">

            <div
                class="relative group p-[1px] md:p-[2px] rounded-[1.8rem] md:rounded-[2.5rem] overflow-hidden max-w-sm md:max-w-lg w-full scale-[0.98] md:scale-[0.92]">

                <div class="rotating-border absolute inset-[-100%] opacity-60"></div>

                <div
                    class="relative bg-[#050914]/90 backdrop-blur-3xl p-4 md:p-12 rounded-[1.7rem] md:rounded-[2.4rem] border border-white/5 shadow-2xl shadow-purple-900/10">

                    <h2
                        class="text-purple-400 font-bold text-[7px] md:text-[9px] uppercase tracking-[0.4em] md:tracking-[1em] mb-6 md:mb-10 text-center opacity-70">
                        Fullstack_Core_Manifesto
                    </h2>

                    <div class="space-y-4 md:space-y-8">
                        <div v-for="(item, i) in MANIFEST_ITEMS" :key="i"
                            class="flex flex-col md:flex-row items-center md:items-start gap-1.5 md:gap-6 border-t border-white/5 pt-4 md:pt-8 first:border-0 first:pt-0 text-center md:text-left">

                            <span class="text-sky-500/50 font-mono text-[9px] md:text-sm shrink-0 md:w-4">
                                0{{ i + 1 }}
                            </span>

                            <p
                                class="text-slate-100 text-[15px] md:text-2xl font-light tracking-tight leading-tight flex-1">
                                <span class="text-white font-bold border-b border-sky-500/30">{{ item.t }}:</span>
                                <br class="md:hidden" />
                                <span class="md:inline-block md:ml-1">{{ item.v }}</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div
            class="flex flex-col items-center justify-end gap-5 md:gap-10 pb-4 md:pb-12 shrink-0 pointer-events-auto w-full z-10">

            <button type="button" @click="$emit('open')"
                class="group relative px-12 py-4 md:px-16 md:py-6 bg-sky-500 text-black font-black uppercase tracking-[0.4em] md:tracking-[0.6em] text-[10px] md:text-xs shadow-[0_0_40px_rgba(56,189,248,0.2)] active:scale-95 transition-all outline-none">
                ENTER SYSTEM
                <div class="absolute inset-0 bg-white opacity-0 group-hover:opacity-10 transition-opacity"></div>
            </button>

            <p
                class="text-slate-600 text-[7px] md:text-[11px] uppercase tracking-[0.5em] md:tracking-[1em] animate-pulse font-bold text-center">
                Identity Verified // Waiting For Input
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

    /* Změna: přidali jsme jen transform-origin a scale, aby animace rotovala čistě */
    animation: rotate 8s linear infinite;
    transform-origin: center center;
}

@keyframes rotate {
    from {
        transform: rotate(0deg) scale(2);
    }

    to {
        transform: rotate(360deg) scale(2);
    }
}
</style>
