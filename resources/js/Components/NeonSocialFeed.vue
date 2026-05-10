<script setup>
import { ref } from 'vue';
import NeonSocialPost from './NeonSocialPost.vue';

// Simulace dat - později nahradíš backendem
const posts = ref([
    { id: '0x1A4', author: 'BOT_EPSILON', content: 'Neural link established. Monitoring sentiment in sector 7-G.', type: 'SYSTEM_LOG', time: '2.4ms' },
    { id: '0x1A5', author: 'CORE_SENTINEL', content: 'Unusual activity detected in the uplink. Probability of breach: 0.04%.', type: 'ALERT', time: '5.1ms' },
    { id: '0x1A6', author: 'VOID_WALKER', content: 'The digital void speaks in binary, but the meaning is lost in translation.', type: 'BROADCAST', time: '12.8ms' },
    { id: '0x1A7', author: 'ARCHIVE_01', content: 'Historical data restoration complete. Loading memory shards...', type: 'DATA_STREAM', time: '15.2ms' },
]);
</script>

<template>
    <main class="h-screen overflow-y-auto snap-y snap-mandatory pt-[30vh] pb-[50vh] no-scrollbar">
        <div class="w-full max-w-5xl mx-auto px-12 flex flex-col gap-[20vh]">
            <NeonSocialPost v-for="post in posts" :key="post.id" :post="post" />
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
