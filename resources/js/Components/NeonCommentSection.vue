<script setup>
import { ref, computed } from 'vue';
import { useNotificationStore } from '@/Stores/useNotificationStore';

const props = defineProps({
    postId: {
        type: [String, Number],
        required: true
    }
});

const store = useNotificationStore();
const commentText = ref('');

// Dynamicky taháme konkrétní post přímo ze storu, aby byla zajištěna 100% real-time reaktivita
const post = computed(() => {
    return store.posts.find(p => String(p.id) === String(props.postId));
});

const comments = computed(() => {
    return post.value?.comments || [];
});

async function submitComment() {
    if (!commentText.value.trim()) return;

    const text = commentText.value;
    commentText.value = ''; // Okamžité pročištění UI (zero latency dojem)

    const success = await store.sendComment(props.postId, text);

    if (!success) {
        // Pokud požadavek spadl, vrátíme text zpátky do inputu
        commentText.value = text;
    }
}
</script>

<template>
    <div class="mt-4 border-t border-cyan-500/10 pt-4 font-mono">
        <div class="space-y-3 max-h-60 overflow-y-auto pr-1 no-scrollbar mb-4">
            <div v-for="comment in comments" :key="comment.id"
                class="border-l border-fuchsia-500/30 bg-fuchsia-500/5 p-2 rounded-r-xl transition-all duration-300">
                <div class="flex justify-between items-center text-[10px]">
                    <span class="text-fuchsia-400 font-bold">// {{ comment.author }}</span>
                    <span class="text-white/30 text-[8px]">{{ comment.timestamp }}</span>
                </div>
                <div class="text-xs text-white/90 mt-1 break-words">
                    {{ comment.text }}
                </div>
            </div>

            <div v-if="comments.length === 0" class="text-center text-white/20 text-[10px] py-2">
                >> NO_COMMENTS_IN_NODE
            </div>
        </div>

        <div class="border border-cyan-500/20 p-1.5 bg-black/40 rounded-xl">
            <div class="flex gap-2">
                <input v-model="commentText" type="text" placeholder="Write a comment..."
                    class="bg-black border border-cyan-500/10 rounded-lg px-3 py-1.5 text-xs text-white w-full focus:outline-none focus:border-cyan-500 transition-all duration-300"
                    @keyup.enter="submitComment" />
                <button @click="submitComment"
                    class="text-[10px] bg-cyan-500 text-black px-4 py-1.5 rounded-lg font-bold hover:bg-cyan-400 active:scale-95 transition-all shrink-0">
                    EXECUTE
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Skrytí scrollbaru při zachování funkčnosti scrollování */
.no-scrollbar::-webkit-scrollbar {
    width: 3px;
}

.no-scrollbar::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.1);
}

.no-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(6, 182, 212, 0.2);
    border-radius: 2px;
}

.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
