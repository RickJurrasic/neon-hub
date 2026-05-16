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
    <main
        class="w-full h-full overflow-y-auto no-scrollbar flex justify-center items-start pt-[12vh] pb-[20vh] snap-y snap-mandatory scroll-pt-[15vh]">

        <div class="w-full max-w-4xl mx-auto px-4 md:px-12 flex flex-col items-stretch gap-20 pb-12">

            <NeonSocialPost v-for="post in posts" :key="post.id" :post="post" class="w-full snap-center" />

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
