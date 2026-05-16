<script setup>
import { ref, onMounted } from 'vue';
// Import nových ikon pro lajky (Pulse) a komentáře (Comms)
import { Heart, MessageSquare } from 'lucide-vue-next';
// Import nově vytvořené komponenty komentářů
import NeonCommentSection from './NeonCommentSection.vue';

defineProps(['post']);

const cardRef = ref(null);
const isVisible = ref(false);
const showComments = ref(false);

onMounted(() => {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            isVisible.value = entry.isIntersecting;
        });
    }, {
        threshold: 0.3
    });

    if (cardRef.value) observer.observe(cardRef.value);
});
</script>

<template>
    <article ref="cardRef"
        class="neon-panel-wrapper w-full rounded-[2rem] snap-center transition-all duration-[600ms] ease-out"
        :class="[isVisible ? 'opacity-100 scale-100 translate-y-0' : 'opacity-0 scale-95 translate-y-10']"
        style="will-change: transform, opacity;">

        <div v-if="isVisible" class="neon-border-active opacity-10"></div>

        <div
            class="neon-glass-core !flex-col !items-start p-16 rounded-[1.9rem] bg-[#050914]/90 border border-white/5 shadow-xl">

            <div class="w-full flex justify-between items-center mb-10 border-b border-white/10 pb-6">
                <div class="flex items-center gap-6">
                    <div class="w-4 h-4 bg-sky-500 shadow-[0_0_15px_#3b82f6] rounded-full animate-pulse"></div>
                    <span class="font-mono text-sm text-sky-400 tracking-[0.5em] font-black uppercase italic">
                        {{ post.author }}
                    </span>
                </div>
                <span class="font-mono text-[10px] text-slate-600 uppercase tracking-widest">Authorized_Stream</span>
            </div>

            <div class="py-10 w-full text-left">
                <p class="text-white text-5xl font-extralight tracking-tight leading-tight italic">
                    <span class="text-sky-500/30 not-italic mr-6 font-mono text-3xl">></span>
                    {{ post.content }}
                </p>
            </div>

            <div class="w-full mt-12 pt-8 border-t border-white/5 flex justify-between items-center text-slate-500">
                <div class="flex gap-10">

                    <button
                        class="group flex items-center gap-3 font-mono text-[10px] hover:text-sky-400 transition-colors uppercase tracking-widest outline-none">
                        <Heart :size="14" class="group-hover:scale-110 transition-transform" />
                        <span>Pulse</span>
                        <span class="text-sky-500/50 font-bold">({{ post.likes_count || 0 }})</span>
                    </button>

                    <button @click="showComments = !showComments"
                        class="group flex items-center gap-3 font-mono text-[10px] hover:text-fuchsia-400 transition-colors uppercase tracking-widest outline-none">
                        <MessageSquare :size="14" :class="{ 'text-fuchsia-400 scale-110': showComments }"
                            class="group-hover:scale-110 transition-transform" />
                        <span>Comms</span>
                        <span class="text-fuchsia-500/50 font-bold">({{ post.comments_count || 0 }})</span>
                    </button>

                </div>
                <div class="font-mono text-[9px] tracking-[0.4em] opacity-40">NODE_REACTION_{{ post.id }}</div>
            </div>

            <Transition enter-active-class="transition duration-300 ease-out"
                enter-from-class="transform scale-95 opacity-0 -translate-y-2"
                enter-to-class="transform scale-100 opacity-100 translate-y-0"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="transform scale-100 opacity-100 translate-y-0"
                leave-to-class="transform scale-95 opacity-0 -translate-y-2">
                <NeonCommentSection v-if="showComments" :comments="post.comments" />
            </Transition>

        </div>
    </article>
</template>

<style scoped>
:deep(.neon-border-active) {
    animation-duration: 25s !important;
}
</style>
