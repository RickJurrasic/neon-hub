<script setup>
import { ref, onMounted, nextTick } from 'vue';
import { Heart, MessageSquare, Eye } from 'lucide-vue-next';
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

    // 1. Uložíme si přesnou pozici karty vůči oknu PŘED změnou
    const rectBefore = cardRef.value.getBoundingClientRect();
    const isOpening = !showComments.value;

    // Zjistíme, jestli jsme úplně nahoře feedu (první post)
    const isFirstPost = scrollContainer.scrollTop === 0;

    // 2. Přepneme viditelnost
    showComments.value = !showComments.value;

    // 3. Počkáme na překreslení DOMu
    await nextTick();

    if (isOpening) {
        // Pokud jsme na prvním postu a už je nahoře, netřeba scrolovat vůbec
        if (isFirstPost && rectBefore.top > 0) {
            return;
        }

        // Jinak necháme prohlížeč plynule srovnat jen to, co utíká z obrazovky
        cardRef.value.scrollIntoView({
            behavior: 'smooth',
            block: 'nearest'
        });
    } else {
        // PŘI ZAVÍRÁNÍ:
        // Změříme novou pozici po smrštění karty
        const rectAfter = cardRef.value.getBoundingClientRect();
        const diff = rectBefore.top - rectAfter.top;

        // Dorovnáme pozici pouze tehdy, pokud se karta reálně pohnula vůči oknu
        // A zároveň nejsme limitováni absolutním vrškem feedu
        if (diff !== 0 && scrollContainer.scrollTop > 0) {
            scrollContainer.scrollTop -= diff;
        }
    }
};

onMounted(() => {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                isVisible.value = true;
            }
        });
    }, {
        threshold: 0.02,
        rootMargin: '200px 0px 400px 0px' // Načítáme s větším předstihem, plynulejší zážitek
    });

    if (cardRef.value) observer.observe(cardRef.value);
});
</script>

<template>
    <article ref="cardRef"
        class="neon-panel-wrapper w-full rounded-[1.5rem] transition-all duration-[500ms] ease-out min-h-[100px]"
        :class="[isVisible ? 'opacity-100 scale-100 translate-y-0' : 'opacity-10 scale-[0.98] translate-y-3']"
        style="will-change: transform, opacity; overflow-anchor: none;">

        <div class="relative w-full">
            <div v-if="isVisible" class="neon-border-active opacity-10"></div>

            <div
                class="neon-glass-core w-full !items-stretch p-5 md:p-8 rounded-[1.4rem] bg-[#050914]/90 border border-white/5 shadow-xl">

                <div
                    class="w-full flex justify-between items-center mb-4 md:mb-5 border-b border-white/10 pb-3 md:pb-4">
                    <div class="flex items-center gap-3 md:gap-4">
                        <div class="w-2.5 h-2.5 bg-sky-500 shadow-[0_0_12px_#3b82f6] rounded-full animate-pulse">
                        </div>
                        <span class="font-mono text-xs text-sky-400 tracking-[0.4em] font-black uppercase italic">
                            {{ post.author }}
                        </span>
                    </div>
                    <span
                        class="font-mono text-[8px] md:text-[9px] text-slate-600 uppercase tracking-widest">Authorized_Stream</span>
                </div>

                <div class="py-1 md:py-2 w-full text-left">
                    <p class="text-white text-lg md:text-2xl font-light tracking-tight leading-snug italic">
                        <span class="text-sky-500/30 not-italic mr-3 md:mr-4 font-mono text-xl md:text-2xl">></span>
                        {{ post.content }}
                    </p>
                </div>

                <div v-if="post.image"
                    class="w-full mt-4 relative overflow-hidden rounded-lg border border-sky-500/10 bg-black/40 group">
                    <div
                        class="absolute inset-0 bg-[linear-gradient(rgba(18,16,16,0)_50%,rgba(0,0,0,0.25)_50%),linear-gradient(90deg,rgba(255,0,0,0.06),rgba(0,255,0,0.02),rgba(0,0,255,0.06))] bg-[size:100%_4px,3px_100%] z-10 pointer-events-none opacity-40">
                    </div>
                    <div
                        class="absolute top-0 left-0 w-full h-0.5 bg-sky-500/20 shadow-[0_0_10px_#3b82f6] animate-scan-fast z-10 pointer-events-none">
                    </div>

                    <div
                        class="absolute top-2 left-2 z-10 bg-black/70 border border-sky-500/30 px-2 py-0.5 font-mono text-[8px] text-sky-400 flex items-center gap-1.5 backdrop-blur-sm uppercase tracking-wider">
                        <Eye :size="10" class="animate-pulse" />
                        <span>VISUAL_ATTACHMENT // {{ post.image_meta || 'SECURE_LINK' }}</span>
                    </div>

                    <img :src="post.image" alt="Visual Payload" class="w-full h-auto max-h-[35vh] object-cover transition-all duration-700
                       opacity-100 md:opacity-85 md:group-hover:opacity-100
                       md:scale-100 md:group-hover:scale-[1.01]
                       filter grayscale-0 md:grayscale md:group-hover:grayscale-0" />
                </div>

                <div
                    class="w-full mt-4 md:mt-5 pt-4 border-t border-white/5 flex justify-between items-center text-slate-500">
                    <div class="flex gap-5 md:gap-8">

                        <button
                            class="group flex items-center gap-2 font-mono text-[10px] hover:text-sky-400 transition-colors uppercase tracking-widest outline-none">
                            <Heart :size="13" class="group-hover:scale-110 transition-transform" />
                            <span>Pulse</span>
                            <span class="text-sky-500/50 font-bold">({{ post.likes_count || 0 }})</span>
                        </button>

                        <button @click="toggleComments"
                            class="group flex items-center gap-2 font-mono text-[10px] hover:text-fuchsia-400 transition-colors uppercase tracking-widest outline-none">
                            <MessageSquare :size="13" :class="{ 'text-fuchsia-400 scale-110': showComments }"
                                class="group-hover:scale-110 transition-transform" />
                            <span>Comms</span>
                            <span class="text-fuchsia-500/50 font-bold">({{ post.comments_count || 0 }})</span>
                        </button>

                    </div>
                    <div class="font-mono text-[8px] tracking-[0.3em] opacity-40 hidden sm:block">
                        NODE_REACTION_{{ post.id }}</div>
                </div>

                <Transition enter-active-class="transition duration-300 ease-out"
                    enter-from-class="transform scale-98 opacity-0 -translate-y-1"
                    enter-to-class="transform scale-100 opacity-100 translate-y-0"
                    leave-active-class="transition duration-200 ease-in"
                    leave-from-class="transform scale-100 opacity-100 translate-y-0"
                    leave-to-class="transform scale-98 opacity-0 -translate-y-1">
                    <NeonCommentSection v-if="showComments" :comments="post.comments" />
                </Transition>

            </div>
        </div>
    </article>
</template>
