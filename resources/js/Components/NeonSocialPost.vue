<script setup>
import { ref, onMounted } from 'vue';
import { Terminal, Share2 } from 'lucide-vue-next';

defineProps(['post']);

const cardRef = ref(null);
const isVisible = ref(false);

onMounted(() => {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            // Pokud je karta aspoň z 50 % vidět, aktivujeme ji
            isVisible.value = entry.isIntersecting;
        });
    }, {
        threshold: 0.5 // Aktivuje se, když je karta na středu
    });

    if (cardRef.value) observer.observe(cardRef.value);
});
</script>

<template>
    <article ref="cardRef"
        class="neon-panel-wrapper w-full rounded-[2rem] snap-center transition-all duration-[800ms] ease-out"
        :class="[isVisible ? 'opacity-100 scale-100 blur-0 translate-y-0' : 'opacity-0 scale-90 blur-xl translate-y-20']">
        <div class="neon-border-active opacity-20"></div>

        <div
            class="neon-glass-core !flex-col !items-start p-16 rounded-[1.9rem] bg-[#050914]/40 backdrop-blur-3xl border border-white/10 shadow-2xl">

            <!-- HEADER -->
            <div class="w-full flex justify-between items-center mb-10 border-b border-white/10 pb-6">
                <div class="flex items-center gap-6">
                    <div class="w-4 h-4 bg-sky-500 shadow-[0_0_20px_#3b82f6] rounded-full animate-pulse"></div>
                    <span class="font-mono text-sm text-sky-400 tracking-[0.5em] font-black uppercase italic">{{
                        post.author }}</span>
                </div>
                <span class="font-mono text-[10px] text-slate-500 uppercase tracking-widest">Authorized_Stream</span>
            </div>

            <!-- CONTENT -->
            <div class="py-10 w-full text-left">
                <p class="text-white text-5xl font-extralight tracking-tight leading-tight italic">
                    <span class="text-sky-500/30 not-italic mr-6 font-mono text-3xl">></span>
                    {{ post.content }}
                </p>
            </div>

            <!-- FOOTER -->
            <div class="w-full mt-12 pt-8 border-t border-white/5 flex justify-between items-center">
                <div class="flex gap-10 opacity-60">
                    <button
                        class="flex items-center gap-2 font-mono text-[10px] hover:text-sky-400 transition-colors uppercase tracking-widest">
                        <Terminal :size="14" /> Execute
                    </button>
                    <button
                        class="flex items-center gap-2 font-mono text-[10px] hover:text-fuchsia-400 transition-colors uppercase tracking-widest">
                        <Share2 :size="14" /> Uplink
                    </button>
                </div>
                <div class="font-mono text-[9px] text-slate-700 tracking-[0.4em]">NODE_REACTION_{{ post.id }}</div>
            </div>
        </div>
    </article>
</template>

<style scoped>
/* Plynulá rotace borderu, aby byla méně náročná na CPU */
:deep(.neon-border-active) {
    animation-duration: 20s !important;
}
</style>
