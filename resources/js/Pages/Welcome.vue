<script setup>
import { ref, watch } from 'vue';
import { useNotificationStore } from '@/Stores/useNotificationStore';
import { usePage } from '@inertiajs/vue3';
import NeonSocialCore from '../Components/NeonSocialCore.vue';
import NeonGate from '../Components/NeonGate.vue';
import NeonOverlay from '../Components/NeonOverlay.vue';

const isOpened = ref(false);
const store = useNotificationStore();
const page = usePage();

const openSystem = () => {
    isOpened.value = true;

    // Inicializace přes API
    if (page.props.auth?.user) {
        axios.post(route('system.initialize'))
            .then(() => console.log('System_Core: Node initialized.'))
            .catch(err => console.error('System_Core_Error:', err));
    }
};

// Watcher hlídá, kdy se brána otevře, a tehdy spustí listenery
watch(isOpened, (newVal) => {
    if (newVal && page.props.auth?.user) {
        store.initListeners(page.props.auth.user.id);
    }
});
</script>

<template>
    <div class="relative h-screen w-full bg-black overflow-hidden font-sans text-slate-200">
        <NeonSocialCore :is-opened="isOpened" :initial-state="page.props.initialState" />
        <NeonGate :is-opened="isOpened" />
        <NeonOverlay :is-opened="isOpened" @open="openSystem" />
    </div>
</template>