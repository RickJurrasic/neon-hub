<script setup>
import { ref } from 'vue';

const isOpened = ref(false);

const openSystem = () => {
    isOpened.value = true;
};
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
