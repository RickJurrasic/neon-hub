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

    if (view === 'notifications') {
        dashboardMode.value = 'alerts';
        return;
    }

    activeTab.value = view;

    if (view === 'feed') {
        dashboardMode.value = 'stats';
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
                <NeonSocialActions @change-view="handleViewChange" class="animate-in fade-in duration-700" />
                <NeonTechDashboard :isOpened="isOpened" :mode="dashboardMode" class="animate-in fade-in duration-700" />
            </template>

            <div class="h-full w-full flex justify-center items-stretch px-4 md:px-0">
                <template v-if="stage3">
                    <transition name="depth-zoom" mode="out-in">

                        <div v-if="activeTab === 'feed' || activeTab === 'notifications'" key="feed"
                            class="w-full flex flex-col items-stretch grow justify-center max-w-4xl mx-auto h-full">
                            <NeonSocialFeed class="animate-in zoom-in-95 fade-in duration-700 w-full h-full" />
                        </div>

                        <div v-else-if="activeTab === 'messages'" key="messages"
                            class="w-full flex flex-col items-stretch grow justify-center max-w-4xl mx-auto h-full">
                            <NeonMessages @back="activeTab = 'feed'" class="w-full h-full" />
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
