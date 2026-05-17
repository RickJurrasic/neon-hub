<script setup>
import { ref } from 'vue';
import NeonSocialPost from './NeonSocialPost.vue';

// Simulace dat - plně naložená funkčními komentáři a stabilními obrázky
const posts = ref([
    {
        id: '0x1A4',
        author: 'BOT_EPSILON',
        content: 'Neural link established. Monitoring sentiment in sector 7-G. Capturing live data packet.',
        type: 'SYSTEM_LOG',
        time: '2.4ms',
        likes_count: 128,
        comments_count: 3,
        image: 'https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?q=80&w=800&auto=format&fit=crop',
        image_meta: 'SYS_STREAM_S7G.RAW',
        comments: [
            // ZMĚNA: Klíče upraveny na author a timestamp
            { id: 1, author: 'NET_RUNNER_99', text: 'Stahování stabilní. Sleduju anomálie.', timestamp: '00:42' },
            { id: 2, author: 'DISCONNECT_ME', text: 'Zase sektor 7-G? Tam to minule bouchlo.', timestamp: '01:05' },
            { id: 3, author: 'OVERLORD', text: 'Log schválen. Pokračujte.', timestamp: '01:14' }
        ]
    },
    {
        id: '0x1A5',
        author: 'CORE_SENTINEL',
        content: 'Unusual activity detected in the uplink. Probability of breach: 0.04%. Isolating anomalous sectors.',
        type: 'ALERT',
        time: '5.1ms',
        likes_count: 512,
        comments_count: 2,
        image: 'https://images.unsplash.com/photo-1515621061946-eff1c2a352bd?q=80&w=800&auto=format&fit=crop',
        image_meta: 'CORE_GATEWAY_DETECT.PNG',
        comments: [
            // ZMĚNA: Klíče upraveny na author and timestamp
            { id: 1, author: 'SYS_ADMIN', text: 'Breach mitigován. Spouštím hloubkový scan.', timestamp: '02:18' },
            { id: 2, author: 'GLITCH_HUNTER', text: '0.04% je moc. Někdo nám leze do brány.', timestamp: '02:20' }
        ]
    },
    {
        id: '0x1A6',
        author: 'VOID_WALKER',
        content: 'The digital void speaks in binary, but the meaning is lost in translation.',
        type: 'BROADCAST',
        time: '12.8ms',
        likes_count: 64,
        comments_count: 0,
        comments: []
    },
    {
        id: '0x1A7',
        author: 'ARCHIVE_01',
        content: 'Historical data restoration complete. Loading memory shards from old Tokyo servers...',
        type: 'DATA_STREAM',
        time: '15.2ms',
        likes_count: 1024,
        comments_count: 4,
        image: 'https://images.unsplash.com/photo-1503899036084-c55cdd92da26?q=80&w=800&auto=format&fit=crop',
        image_meta: 'RECOVERED_SECTOR_9.IMG',
        comments: [
            // ZMĚNA: Klíče upraveny na author and timestamp
            { id: 1, author: 'CHROME_EYE', text: 'Ty staré neonové fonty z roku 2026 jsou masterpiece.', timestamp: '04:12' },
            { id: 2, author: 'REBEL_CELL', text: 'Našel jsi v tom archivu přístupové klíče k Megacorpu?', timestamp: '04:55' },
            { id: 3, author: 'DEUS_EX', text: 'Záloha stažena na lokální cluster.', timestamp: '05:01' },
            { id: 4, author: 'KUSANAGI', text: 'Vrací se mi vzpomínky... skvělá práce.', timestamp: '05:24' }
        ]
    },
]);
</script>

<template>
    <main
        class="w-full h-full overflow-y-auto no-scrollbar flex justify-center items-start pt-[12vh] pb-[20vh] snap-y snap-mandatory scroll-pt-[14vh]">

        <div class="w-full max-w-4xl mx-auto px-2 md:px-12 flex flex-col items-stretch gap-8 md:gap-20 pb-12">

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
