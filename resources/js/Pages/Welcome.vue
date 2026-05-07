<script setup>
import { ref } from 'vue';

const isOpened = ref(false);

const openSystem = () => {
    isOpened.value = true;
};
</script>

<template>
    <div
        class="relative min-h-screen bg-[#02040a] overflow-hidden font-sans text-slate-200 selection:bg-purple-900/40 selection:text-white">

        <!-- VNITŘEK SYSTÉMU (Zobrazi se po otevření) -->
        <NeonSocialCore :isOpened="isOpened" />

        <!-- BRÁNA (Animované rozestoupení) -->
        <NeonGate :isOpened="isOpened" />

        <!-- Logika vstupu (Ta karta a tlačítko) -->
        <NeonOverlay :isOpened="isOpened" @open="isOpened = true" />

    </div>
</template>

<style scoped>
.rotating-border {
    background: conic-gradient(from 0deg,
            transparent 0%,
            rgba(194, 49, 162, 0.44) 25%,
            rgba(56, 189, 248, 0.4) 50%,
            rgba(192, 43, 155, 0.4) 75%,
            transparent 100%);
    animation: rotate 8s linear infinite;
}

@keyframes rotate {
    from {
        transform: rotate(0deg);
    }

    to {
        transform: rotate(360deg);
    }
}
</style>
