<script setup>
import { ref } from 'vue';
import { Send } from 'lucide-vue-next';

// Props pro předání komentářů z parent komponenty (postu)
defineProps({
    comments: {
        type: Array,
        default: () => [
            { id: 1, author: 'Peggy_Core', text: 'Zase řešíte ty plínky a ptačák reunion? Typický.', timestamp: '02:45' },
            { id: 2, author: 'Bedrich_420', text: 'tyvole voni tam fakt maj funkcni komentare! funguje to voe', timestamp: '03:12' }
        ]
    }
});

const emit = defineEmits(['send-comment']);
const newComment = ref('');

const handleSubmit = () => {
    if (!newComment.value.trim()) return;
    emit('send-comment', newComment.value);
    newComment.value = '';
};
</script>

<template>
    <div class="w-full mt-6 bg-[#03060d]/60 border border-white/5 rounded-[1.5rem] p-6 md:p-8 space-y-6">

        <div class="flex justify-between items-center border-b border-white/5 pb-4">
            <span class="font-mono text-[10px] text-fuchsia-400 tracking-[0.3em] uppercase font-bold">
                // COMMS_STREAM_ACTIVE
            </span>
            <span class="font-mono text-[9px] text-slate-600">
                {{ comments.length }} TARGET_RESPONSES
            </span>
        </div>

        <div class="space-y-4 max-h-[300px] overflow-y-auto pr-2 custom-scrollbar">
            <div v-for="comment in comments" :key="comment.id"
                class="group/comment flex flex-col gap-2 p-4 bg-[#070c1a]/40 border border-white/[0.02] rounded-[1rem] transition-colors hover:bg-[#070c1a]/80">

                <div class="flex justify-between items-center">
                    <span class="font-mono text-xs text-sky-400/80 font-bold uppercase tracking-wider">
                        {{ comment.author }}
                    </span>
                    <span class="font-mono text-[9px] text-slate-600 tracking-widest">
                        T+{{ comment.timestamp }}
                    </span>
                </div>

                <p
                    class="text-slate-300 text-sm font-light leading-relaxed text-left pl-2 border-l border-sky-500/20 group-hover/comment:border-fuchsia-500/40 transition-colors">
                    {{ comment.text }}
                </p>
            </div>
        </div>

        <form @submit.prevent="handleSubmit" class="relative flex items-center mt-4">
            <input v-model="newComment" type="text" placeholder="Napiš odpověď do sítě..."
                class="w-full bg-[#050914] border border-white/10 rounded-[1rem] px-6 py-4 pr-16 text-sm text-slate-200 placeholder-slate-600 focus:outline-none focus:border-fuchsia-500/50 focus:shadow-[0_0_20px_rgba(217,70,239,0.05)] transition-all font-light" />
            <button type="submit"
                class="absolute right-3 p-2.5 text-slate-500 hover:text-fuchsia-400 transition-colors outline-none">
                <Send :size="16" />
            </button>
        </form>

    </div>
</template>

<style scoped>
/* Čistý sci-fi scrollbar, žádný tlustý systémový hnus */
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(56, 189, 248, 0.1);
    border-radius: 10px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(217, 70, 239, 0.3);
}
</style>
