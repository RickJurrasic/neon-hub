<script setup>
import { ref, onMounted, nextTick } from 'vue';
import { Heart, MessageSquare, Eye } from 'lucide-vue-next'; // Přidán Eye icon pro image tracking
import NeonCommentSection from './NeonCommentSection.vue';

defineProps(['post']);

const cardRef = ref(null);
const isVisible = ref(false);
const showComments = ref(false);

const toggleComments = async () => {
    const scrollContainer = cardRef.value?.closest('main');

    if (!scrollContainer) {
        showComments.value = !showComments.value;
        return;
    }

    const cardTop = cardRef.value.offsetTop;
    const isOpening = !showComments.value;

    showComments.value = !showComments.value;

    await nextTick();

    if (isOpening) {
        scrollContainer.scrollTo({
            top: cardTop - (window.innerHeight * 0.15),
            behavior: 'smooth'
        });
    } else {
        scrollContainer.scrollTop = cardTop - (window.innerHeight * 0.15);
    }
};

onMounted(() => {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            isVisible.value = entry.isIntersecting;
        });
    }, {
        threshold: 0.2 // Mírně sníženo, aby se velké posty s obrázkem aktivovaly dřív
    });

    if (cardRef.value) observer.observe(cardRef.value);
});
</script>

<template>
    <article ref="cardRef"
        class="neon-panel-wrapper w-full rounded-[2rem] snap-center transition-all duration-[600ms] ease-out"
        :class="[isVisible ? 'opacity-100 scale-100 translate-y-0' : 'opacity-0 scale-95 translate-y-10']"
        style="will-change: transform, opacity; overflow-anchor: none;">
        <div v-if="isVisible" class="neon-border-active opacity-10"></div>

        <div
            class="neon-glass-core w-full !items-stretch p-6 md:p-12 rounded-[1.9rem] bg-[#050914]/90 border border-white/5 shadow-xl">

            <div class="w-full flex justify-between items-center mb-6 md:mb-8 border-b border-white/10 pb-4 md:pb-6">
                <div class="flex items-center gap-4 md:gap-6">
                    <div class="w-3 h-3 md:w-4 md:h-4 bg-sky-500 shadow-[0_0_15px_#3b82f6] rounded-full animate-pulse">
                    </div>
                    <span
                        class="font-mono text-xs md:text-sm text-sky-400 tracking-[0.5em] font-black uppercase italic">
                        {{ post.author }}
                    </span>
                </div>
                <span
                    class="font-mono text-[8px] md:text-[10px] text-slate-600 uppercase tracking-widest">Authorized_Stream</span>
            </div>

            <div class="py-2 md:py-4 w-full text-left">
                <p class="text-white text-xl md:text-4xl font-extralight tracking-tight leading-tight italic">
                    <span class="text-sky-500/30 not-italic mr-3 md:mr-6 font-mono text-2xl md:text-3xl">></span>
                    {{ post.content }}
                </p>
            </div>

            <div v-if="post.image"
                class="w-full mt-6 relative overflow-hidden rounded-xl border border-sky-500/10 bg-black/40 group">
                <div
                    class="absolute inset-0 bg-[linear-gradient(rgba(18,16,16,0)_50%,rgba(0,0,0,0.25)_50%),linear-gradient(90deg,rgba(255,0,0,0.06),rgba(0,255,0,0.02),rgba(0,0,255,0.06))] bg-[size:100%_4px,3px_100%] z-10 pointer-events-none opacity-40">
                </div>
                <div
                    class="absolute top-0 left-0 w-full h-0.5 bg-sky-500/20 shadow-[0_0_10px_#3b82f6] animate-scan-fast z-10 pointer-events-none">
                </div>

                <div
                    class="absolute top-3 left-3 z-10 bg-black/70 border border-sky-500/30 px-2 py-0.5 font-mono text-[8px] text-sky-400 flex items-center gap-1.5 backdrop-blur-sm uppercase tracking-wider">
                    <Eye :size="10" class="animate-pulse" />
                    <span>VISUAL_ATTACHMENT // {{ post.image_meta || 'SECURE_LINK' }}</span>
                </div>

                <img :src="post.image" alt="Visual Payload" class="w-full h-auto max-h-[50vh] object-cover transition-all duration-700
           opacity-100 md:opacity-80 md:group-hover:opacity-100
           md:scale-100 md:group-hover:scale-[1.02]
           filter grayscale-0 md:grayscale md:group-hover:grayscale-0" />
            </div>

            <div
                class="w-full mt-6 md:mt-8 pt-6 border-t border-white/5 flex justify-between items-center text-slate-500">
                <div class="flex gap-6 md:gap-10">

                    <button
                        class="group flex items-center gap-2 md:gap-3 font-mono text-[10px] hover:text-sky-400 transition-colors uppercase tracking-widest outline-none">
                        <Heart :size="14" class="group-hover:scale-110 transition-transform" />
                        <span>Pulse</span>
                        <span class="text-sky-500/50 font-bold">({{ post.likes_count || 0 }})</span>
                    </button>

                    <button @click="toggleComments"
                        class="group flex items-center gap-2 md:gap-3 font-mono text-[10px] hover:text-fuchsia-400 transition-colors uppercase tracking-widest outline-none">
                        <MessageSquare :size="14" :class="{ 'text-fuchsia-400 scale-110': showComments }"
                            class="group-hover:scale-110 transition-transform" />
                        <span>Comms</span>
                        <span class="text-fuchsia-500/50 font-bold">({{ post.comments_count || 0 }})</span>
                    </button>

                </div>
                <div class="font-mono text-[8px] md:text-[9px] tracking-[0.4em] opacity-40 hidden sm:block">
                    NODE_REACTION_{{ post.id }}</div>
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

/* Rychlý laserový skener pro obrázek */
@keyframes imageScan {
    0% {
        top: 0%;
    }

    50% {
        top: 100%;
    }

    100% {
        top: 0%;
    }
}

.animate-scan-fast {
    animation: imageScan 4s linear infinite;
}
</style>
