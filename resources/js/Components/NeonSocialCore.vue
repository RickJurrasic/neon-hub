<script setup>
import { ref, watch } from 'vue';
import NeonNav from './NeonNav.vue';
import NeonSocialActions from './NeonSocialActions.vue';
import NeonTechDashboard from './NeonTechDashboard.vue';
import NeonSocialFeed from './NeonSocialFeed.vue';
import NeonMessages from './NeonMessages.vue';
import NeonFriends from './NeonFriends.vue';
import NeonUserProfile from './NeonUserProfile.vue'; // Nezapomeň na import!

const props = defineProps({
    isOpened: { type: Boolean, default: false }
});

// --- STAVY PRO NAVIGACI ---
const activeTab = ref('feed');
const dashboardMode = ref('stats');
const selectedEntityId = ref(null);

// --- STAVY PRO FÁZE (Tohle ti tam chybělo/rozbilo se) ---
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
        // Postupné zapínání panelů
        setTimeout(() => { stage1.value = true; }, 100);
        setTimeout(() => { stage2.value = true; }, 500);
        setTimeout(() => { stage3.value = true; }, 900);
    } else {
        // Okamžitý reset při zavření
        stage1.value = false;
        stage2.value = false;
        stage3.value = false;
        activeTab.value = 'feed';
        dashboardMode.value = 'stats';
        selectedEntityId.value = null;
    }
}, { immediate: true }); // immediate zajistí, že se to zkontroluje i při mountu
</script>

<template>
    <div id="neon-scanline-layer"></div>
    <div class="absolute inset-0 transition-all duration-[1200ms] ease-out overflow-hidden"
        :class="[isOpened ? 'opacity-100 scale-100' : 'opacity-0 scale-95 pointer-events-none']">
        <template v-if="stage1">
            <!-- UI PANELY -->
            <template v-if="stage2">
                <NeonNav class="animate-in fade-in duration-700" />

                <!-- Používáme naši novou funkci handleViewChange -->
                <NeonSocialActions @change-view="handleViewChange" class="animate-in fade-in duration-700" />

                <NeonTechDashboard :isOpened="isOpened" :mode="dashboardMode" class="animate-in fade-in duration-700" />
            </template>

            <!-- HLAVNÍ TERMINÁLOVÝ STŘED -->
            <div class="h-full w-full flex justify-center items-center">
                <template v-if="stage3">
                    <transition name="depth-zoom" mode="out-in">

                        <!-- FEED (Zobrazí se i při módu notifications!) -->
                        <div v-if="activeTab === 'feed' || activeTab === 'notifications'" key="feed"
                            class="w-full flex justify-center">
                            <NeonSocialFeed class="animate-in zoom-in-95 fade-in duration-700" />
                        </div>

                        <!-- MESSAGES -->
                        <div v-else-if="activeTab === 'messages'" key="messages" class="w-full flex justify-center">
                            <NeonMessages @back="activeTab = 'feed'" />
                        </div>

                        <!-- FRIENDS -->
                        <div v-else-if="activeTab === 'friends'" key="friends" class="w-full flex justify-center">
                            <!-- Tady zachytíme emit z NeonFriends -->
                            <NeonFriends @back="activeTab = 'feed'" @view-profile="openEntityProfile" />
                        </div>

                        <!-- USER PROFILE (Nový hloubkový pohled) -->
                        <div v-else-if="activeTab === 'profile'" key="profile" class="w-full flex justify-center">
                            <NeonUserProfile :entityId="selectedEntityId" @back="activeTab = 'friends'" />
                        </div>

                    </transition>
                </template>
            </div>

            <!-- KYBERPUNKOVÉ POZADÍ -->
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

/* Pomocné animace */
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

/* Zajištění, že se komponenty nebudou při zoomu ořezávat nepěkně */
.depth-zoom-enter-active :deep(> *),
.depth-zoom-leave-active :deep(> *) {
    will-change: transform, opacity;
}
</style>
