<script setup>
import { ref, onMounted } from 'vue';
import { Terminal, Share2 } from 'lucide-vue-next';

defineProps(['post']);

const cardRef = ref(null);
const isVisible = ref(false);

onMounted(() => {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            isVisible.value = entry.isIntersecting;
        });
    }, {
        threshold: 0.3 // Snížil jsem na 0.3, aby karta naskočila o něco dřív
    });

    if (cardRef.value) observer.observe(cardRef.value);
});
</script>

<template>
    <article ref="cardRef"
        class="neon-panel-wrapper w-full rounded-[2rem] snap-center transition-all duration-[600ms] ease-out"
        :class="[isVisible ? 'opacity-100 scale-100 translate-y-0' : 'opacity-0 scale-95 translate-y-10']"
        style="will-change: transform, opacity;">

        <!-- OPTIMALIZACE 1: Rotující border se renderuje JEN když je karta vidět -->
        <div v-if="isVisible" class="neon-border-active opacity-10"></div>

        <!-- OPTIMALIZACE 2: Backdrop-blur snížen/vyhozen, nahrazen tmavším BG -->
        <div class="neon-glass-core !flex-col !items-start p-16 rounded-[1.9rem]
                    bg-[#050914]/90 border border-white/5 shadow-xl">
            <!--
                Tady jsem vyhodil backdrop-blur-3xl.
                V tomhle tmavém setupu to 80% lidí nepozná, ale GPU ti poděkuje.
                Pokud to bez něj fakt nejde, dej tam max 'backdrop-blur-md'.
            -->

            <!-- HEADER -->
            <div class="w-full flex justify-between items-center mb-10 border-b border-white/10 pb-6">
                <div class="flex items-center gap-6">
                    <!-- Glow efekt na tečce je malý, ten je v pohodě -->
                    <div class="w-4 h-4 bg-sky-500 shadow-[0_0_15px_#3b82f6] rounded-full animate-pulse"></div>
                    <span class="font-mono text-sm text-sky-400 tracking-[0.5em] font-black uppercase italic">
                        {{ post.author }}
                    </span>
                </div>
                <span class="font-mono text-[10px] text-slate-600 uppercase tracking-widest">Authorized_Stream</span>
            </div>

            <!-- CONTENT -->
            <div class="py-10 w-full text-left">
                <p class="text-white text-5xl font-extralight tracking-tight leading-tight italic">
                    <span class="text-sky-500/30 not-italic mr-6 font-mono text-3xl">></span>
                    {{ post.content }}
                </p>
            </div>

            <!-- FOOTER -->
            <div class="w-full mt-12 pt-8 border-t border-white/5 flex justify-between items-center text-slate-500">
                <div class="flex gap-10">
                    <button
                        class="flex items-center gap-2 font-mono text-[10px] hover:text-sky-400 transition-colors uppercase tracking-widest outline-none">
                        <Terminal :size="14" /> Execute
                    </button>
                    <button
                        class="flex items-center gap-2 font-mono text-[10px] hover:text-fuchsia-400 transition-colors uppercase tracking-widest outline-none">
                        <Share2 :size="14" /> Uplink
                    </button>
                </div>
                <div class="font-mono text-[9px] tracking-[0.4em] opacity-40">NODE_REACTION_{{ post.id }}</div>
            </div>
        </div>
    </article>
</template>

<style scoped>
/* Ponecháno pro plynulost */
:deep(.neon-border-active) {
    animation-duration: 25s !important;
    /* Ještě o kousek pomalejší = míň překreslování */
}

/*
   DŮLEŽITÉ: Vyhnul jsem se animování vlastnosti 'blur' v :class.
   Animovat 'filter: blur()' v reálném čase je největší zabiják FPS.
   Teď se karta jen plynule objeví a zvětší.
*/
</style>
