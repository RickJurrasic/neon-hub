<script setup>
import { ref, watch } from 'vue';
import NeonNav from './NeonNav.vue';
import NeonSocialActions from './NeonSocialActions.vue';
import NeonTechDashboard from './NeonTechDashboard.vue';
import NeonSocialFeed from './NeonSocialFeed.vue';
import NeonMessages from './NeonMessages.vue';
import NeonFriends from './NeonFriends.vue';
import NeonUserProfile from './NeonUserProfile.vue';

const props = defineProps({
    isOpened: { type: Boolean, default: false }
});

// --- STAVY PRO NAVIGACI ---
const activeTab = ref('feed');
const dashboardMode = ref('stats');
const selectedEntityId = ref(null);

// --- STAVY PRO FÁZE ---
const stage1 = ref(false);
const stage2 = ref(false);
const stage3 = ref(false);

// --- LOGIKA PŘEPÍNÁNÍ ---
const handleViewChange = (view) => {
    if (!stage3.value) return;

    // Pokud klikneš na ALERTS (notifications)
    if (view === 'notifications') {
        // Tady se děje ten nezávislý switch obsahu uvnitř dashboardu
        dashboardMode.value = dashboardMode.value === 'alerts' ? 'stats' : 'alerts';

        // Na mobilu ale chceme přepnout kartu, aby se dashboard zobrazil
        activeTab.value = 'notifications';
        return;
    }

    // Pro ostatní pohledy (feed, messages, friends) nastavíme aktivní tab normálně
    activeTab.value = view;

    if (view === 'feed') {
        // ❌ ODEBRÁNO: dashboardMode.value = 'stats'
        // Dashboard si teď pamatuje svůj stav a nereaguje na Home tlačítko
        selectedEntityId.value = null;
    }
};

const openEntityProfile = (id) => {
    selectedEntityId.value = id;
    activeTab.value = 'profile';
};

// --- WATCHER PRO SPOUŠTĚNÍ SYSTÉMU ---
watch(() => props.isOpened, (newVal) => {
    if (newVal) {
        setTimeout(() => { stage1.value = true; }, 100);
        setTimeout(() => { stage2.value = true; }, 500);
        setTimeout(() => { stage3.value = true; }, 900);
    } else {
        stage1.value = false;
        stage2.value = false;
        stage3.value = false;
        activeTab.value = 'feed';
        dashboardMode.value = 'stats';
        selectedEntityId.value = null;
    }
}, { immediate: true });
</script>

<template>
    <div id="neon-scanline-layer" class="pointer-events-none z-50"></div>

    <div class="absolute inset-0 h-[100dvh] w-full transition-all duration-[1200ms] ease-out overflow-hidden"
        :class="[isOpened ? 'opacity-100 scale-100' : 'opacity-0 scale-95 pointer-events-none']">
        <template v-if="stage1">

            <template v-if="stage2">
                <NeonNav class="animate-in fade-in duration-700" />

                <NeonSocialActions class="hidden md:block" :active-tab="activeTab" @change-view="handleViewChange" />

                <NeonSocialActions class="block md:hidden" :is-mobile="true" :active-tab="activeTab"
                    @change-view="handleViewChange" />

                <NeonTechDashboard :isOpened="isOpened" :mode="dashboardMode"
                    class="hidden md:block animate-in fade-in duration-700" />
            </template>

            <div class="h-full w-full flex justify-center items-stretch md:px-0">
                <template v-if="stage3">
                    <transition name="depth-zoom" mode="out-in">

                        <div v-if="activeTab === 'feed' || activeTab === 'notifications'" key="feed"
                            class="w-full flex flex-col items-stretch grow justify-center max-w-4xl mx-auto h-full relative">

                            <NeonSocialFeed class="animate-in zoom-in-95 fade-in duration-700 w-full h-full"
                                :class="activeTab === 'notifications' ? 'hidden md:block' : 'block'" />

                            <div v-if="activeTab === 'notifications'"
                                class="absolute inset-0 flex md:hidden items-center justify-center p-6 animate-in zoom-in-95 fade-in duration-300 z-10">

                                <div class="w-full max-w-sm max-h-[70vh] flex items-center justify-center">
                                    <NeonTechDashboard :isOpened="isOpened" :mode="dashboardMode"
                                        class="w-full h-full" />
                                </div>

                            </div>

                        </div>

                        <div v-else-if="activeTab === 'messages'" key="messages"
                            class="w-full h-full overflow-y-auto no-scrollbar pt-[12vh] pb-[12vh] px-4 flex justify-center items-start md:items-center max-w-4xl mx-auto grow">
                            <NeonMessages @back="activeTab = 'feed'"
                                class="w-full animate-in zoom-in-95 fade-in duration-700" />
                        </div>

                        <div v-else-if="activeTab === 'friends'" key="friends"
                            class="w-full h-full overflow-y-auto no-scrollbar pt-[12vh] pb-[12vh] px-4 flex justify-center items-start md:items-center max-w-4xl mx-auto grow">
                            <NeonFriends @back="activeTab = 'feed'" @view-profile="openEntityProfile"
                                class="w-full animate-in zoom-in-95 fade-in duration-700" />
                        </div>

                        <div v-else-if="activeTab === 'profile'" key="profile"
                            class="w-full h-full overflow-y-auto no-scrollbar pt-[12vh] pb-[12vh] px-4 flex justify-center items-center max-w-4xl mx-auto grow">
                            <NeonUserProfile :entityId="selectedEntityId" @back="activeTab = 'friends'"
                                class="w-full animate-in zoom-in-95 fade-in duration-700" />
                        </div>

                    </transition>
                </template>
            </div>

            <div class="fixed inset-0 -z-10 bg-[#02040a]">
                <div class="absolute inset-0 opacity-20 bg-[radial-gradient(circle_at_50%_50%,#1e293b,transparent)]">
                </div>
            </div>
        </template>
    </div>
</template>

<style scoped>
.depth-zoom-enter-active,
.depth-zoom-leave-active {
    transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    pointer-events: none;
}

.depth-zoom-leave-to {
    transform: scale(0.9);
    opacity: 0;
    filter: blur(12px);
}

.depth-zoom-enter-from {
    transform: scale(1.1);
    opacity: 0;
    filter: blur(12px);
}

/* ⚠️ TYHLE TŘÍDY JSOU KLÍČOVÉ PRO SPRÁVNÝ HYBRIDNÍ SCROLL FEEDU - NESMAZAT */
.animate-in {
    animation-fill-mode: forwards;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }

    to {
        opacity: 1;
    }
}

.fade-in {
    animation: fadeIn 0.8s ease-out;
}

.depth-zoom-enter-active :deep(> *),
.depth-zoom-leave-active :deep(> *) {
    will-change: transform, opacity;
}
</style>
