<script setup>
import { ref, watch } from 'vue'; // watch stačí, onMounted tu teď nevyužijeme
import { useNotificationStore } from '@/Stores/useNotificationStore';
import { usePage } from '@inertiajs/vue3'; // DŮLEŽITÉ: Import usePage

const isOpened = ref(false);
const store = useNotificationStore();
const page = usePage(); // TADY jsi to měl chybějící!

const openSystem = () => {
    isOpened.value = true;
};

// Teď už 'page' existuje a watch ji může použít
watch(isOpened, (newVal) => {
    if (newVal && page.props.auth?.user) {
        store.initListeners(page.props.auth.user.id);
    }
});
</script>

<template>
    <div
        class="relative h-screen w-full bg-[#02040a] overflow-hidden font-sans text-slate-200 selection:bg-purple-900/40 selection:text-white">

        <!-- 1. Vrstva: Samotná aplikace (v pozadí) -->
        <NeonSocialCore :isOpened="isOpened" />

        <!-- 2. Vrstva: Mechanická brána -->
        <NeonGate :isOpened="isOpened" />

        <!-- 3. Vrstva: HUD a Vstupní karta (zůstává na vrchu) -->
        <NeonOverlay :isOpened="isOpened" @open="openSystem" />

    </div>
</template>
