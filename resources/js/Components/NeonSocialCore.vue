<script setup>
import { ref, watch } from 'vue';
import { useNotificationStore } from '@/Stores/useNotificationStore';
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

const store = useNotificationStore();

// --- STAVY ---
const activeTab = ref('feed');
const dashboardMode = ref('stats');
const selectedEntityId = ref(null);
const stage1 = ref(false);
const stage2 = ref(false);
const stage3 = ref(false);

// --- LOGIKA ---
const handleViewChange = (payload) => {
    if (!stage3.value) return;

    const view = typeof payload === 'string' ? payload : payload.view;
    activeTab.value = view;

    // Reset notifikací a přepnutí dashboardu
    if (view === 'notifications') {
        dashboardMode.value = dashboardMode.value === 'alerts' ? 'stats' : 'alerts';
        store.markAlertsAsRead();
    } else if (view === 'messages') {
        store.markMessagesAsRead();
        dashboardMode.value = 'stats';
    } else if (view === 'friends') {
        store.markRequestsAsRead();
        dashboardMode.value = 'stats';
    } else {
        dashboardMode.value = 'stats';
    }

    if (view === 'feed') selectedEntityId.value = null;
};

const openEntityProfile = (id) => {
    selectedEntityId.value = id;
    activeTab.value = 'profile';
};

// --- WATCHER ---
watch(() => props.isOpened, (newVal) => {
    if (newVal) {
        setTimeout(() => { stage1.value = true; }, 100);
        setTimeout(() => { stage2.value = true; }, 500);
        setTimeout(() => { stage3.value = true; }, 900);
    } else {
        stage1.value = false; stage2.value = false; stage3.value = false;
        activeTab.value = 'feed'; dashboardMode.value = 'stats';
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
                <NeonSocialActions class="hidden xl:block" :active-tab="activeTab" @change-view="handleViewChange" />
                <NeonSocialActions class="block xl:hidden" :is-mobile="true" :active-tab="activeTab"
                    @change-view="handleViewChange" />
                <NeonTechDashboard :isOpened="isOpened" :mode="dashboardMode"
                    class="hidden xl:block animate-in fade-in duration-700" />
            </template>

            <div class="h-full w-full flex justify-center items-stretch md:px-0">
                <template v-if="stage3">
                    <transition name="depth-zoom" mode="out-in">
                        <div v-if="activeTab === 'feed' || activeTab === 'notifications'" key="feed"
                            class="w-full flex flex-col items-stretch grow justify-center max-w-4xl mx-auto h-full relative">

                            <NeonSocialFeed class="animate-in zoom-in-95 fade-in duration-700 w-full h-full"
                                :class="{ 'hidden xl:block': activeTab === 'notifications' }" />

                            <div v-if="activeTab === 'notifications'"
                                class="absolute inset-0 flex xl:hidden items-center justify-center p-6 pb-28 animate-in zoom-in-95 fade-in duration-300 z-10">
                                <div
                                    class="w-full max-w-[290px] md:max-w-md h-full max-h-[62vh] flex items-center justify-center">
                                    <NeonTechDashboard :isOpened="isOpened" :mode="dashboardMode"
                                        class="w-full h-full" />
                                </div>
                            </div>
                        </div>

                        <div v-else-if="activeTab === 'messages'" key="messages"
                            class="w-full h-full overflow-y-auto no-scrollbar pt-[12vh] pb-[12vh] px-4 flex justify-center items-start md:items-center max-w-2xl mx-auto grow">
                            <NeonMessages @back="activeTab = 'feed'"
                                class="w-full animate-in zoom-in-95 fade-in duration-700" />
                        </div>

                        <div v-else-if="activeTab === 'friends'" key="friends"
                            class="w-full h-full overflow-y-auto no-scrollbar pt-[12vh] pb-[12vh] px-4 flex justify-center items-start md:items-center max-w-2xl mx-auto grow">
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
</style>
