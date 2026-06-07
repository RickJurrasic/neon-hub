<script setup>
import { ref, computed, watch } from 'vue';
import { useNotificationStore } from '@/Stores/useNotificationStore';
import { ArrowUp } from 'lucide-vue-next';
import NeonSocialPost from './NeonSocialPost.vue';

const store = useNotificationStore();
const mainContainer = ref(null);

// Interní stav pro ID postů, které uživatel aktuálně vidí na obrazovce
const currentVisibleIds = ref([]);

// Inicializace: Když se poprvé načtou posty ze storu, hned je zobrazíme
watch(() => store.posts, (newPosts) => {
    if (currentVisibleIds.value.length === 0 && newPosts.length > 0) {
        currentVisibleIds.value = newPosts.map(p => p.id);
    }
}, { immediate: true });

// Spočítáme, kolik postů je ve storu nových (nejsou v currentVisibleIds)
const incomingPostsCount = computed(() => {
    return store.posts.filter(p => !currentVisibleIds.value.includes(p.id)).length;
});

// Posty, které se reálně vykreslují ve feedu (seřazené od nejnovějších z těch schválených)
const visiblePosts = computed(() => {
    return store.posts.filter(p => currentVisibleIds.value.includes(p.id));
});

// Akce při kliknutí na "Nové příspěvky"
const loadIncomingPosts = () => {
    // Aktualizujeme zobrazené ID na kompletní sadu ze storu
    currentVisibleIds.value = store.posts.map(p => p.id);

    // Hladký scroll nahoru na začátek feedu
    if (mainContainer.value) {
        mainContainer.value.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }
};
</script>

<template>
    <main ref="mainContainer"
        class="w-full h-full overflow-y-auto no-scrollbar flex justify-center items-start pt-[14vh] pb-[15vh] scroll-smooth relative">

        <Transition enter-active-class="transition duration-500 ease-out"
            enter-from-class="transform -translate-y-4 opacity-0 scale-95"
            enter-to-class="transform translate-y-0 opacity-100 scale-100"
            leave-active-class="transition duration-300 ease-in"
            leave-from-class="transform translate-y-0 opacity-100 scale-100"
            leave-to-class="transform -translate-y-4 opacity-0 scale-95">
            <div v-if="incomingPostsCount > 0" class="absolute top-[16vh] z-30 left-1/2 -translate-x-1/2">
                <button @click="loadIncomingPosts"
                    class="flex items-center gap-2 bg-[#050914]/90 border border-sky-500/40 text-sky-400 font-mono text-[10px] tracking-[0.2em] uppercase px-4 py-2.5 rounded-full shadow-[0_0_15px_rgba(56,189,248,0.15)] hover:bg-sky-950/40 hover:border-sky-400 transition-all cursor-pointer backdrop-blur-md">
                    <ArrowUp :size="12" class="animate-bounce" />
                    <span>Nové příspěvky ({{ incomingPostsCount }})</span>
                </button>
            </div>
        </Transition>

        <div class="w-full max-w-3xl mx-auto px-4 md:px-8 flex flex-col items-stretch gap-6 md:gap-10 pb-12">

            <div v-if="visiblePosts.length === 0"
                class="w-full text-center py-12 font-mono text-slate-500 text-xs tracking-[0.2em]">
                >> NO_ACTIVE_LOGS_IN_FEED
            </div>

            <NeonSocialPost v-for="post in visiblePosts" :key="post.id" :post="post" class="w-full" />

        </div>
    </main>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}

.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
