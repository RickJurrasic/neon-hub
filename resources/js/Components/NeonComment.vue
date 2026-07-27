<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { Pencil, Check, X } from 'lucide-vue-next';

const props = defineProps({
    comment: {
        type: Object,
        required: true
    }
});

// Reaktivní stavy pro správu inline editace
const isEditing = ref(false);
const editedText = ref(props.comment.text);

const handleUpdate = () => {
    // Validace prázdného textu nebo pokud se text nezměnil
    if (!editedText.value.trim() || editedText.value === props.comment.text) {
        isEditing.value = false;
        return;
    }

    // Odeslání požadavku přes Inertia na backend patch routu
    router.patch(route('comments.update', props.comment.id), {
        content: editedText.value
    }, {
        preserveScroll: true, // Stránka neodskočí nahoru
        onSuccess: () => {
            isEditing.value = false;
        }
    });
};

const cancelEdit = () => {
    editedText.value = props.comment.text;
    isEditing.value = false;
};
</script>

<template>
    <div
        class="group/comment flex flex-col gap-2 p-4 bg-[#070c1a]/40 border border-white/[0.02] rounded-[1rem] transition-colors hover:bg-[#070c1a]/80">

        <div class="flex justify-between items-center">
            <div class="flex items-center gap-2">
                <span class="font-mono text-xs text-sky-400/80 font-bold uppercase tracking-wider">
                    {{ comment.author }}
                </span>

                <span v-if="comment.can_edit"
                    class="font-mono text-[8px] bg-sky-500/10 text-sky-400 px-1.5 py-0.5 rounded border border-sky-500/20 uppercase tracking-widest font-black">
                    YOU
                </span>
            </div>

            <div class="flex items-center gap-3">
                <span class="font-mono text-[9px] text-slate-600 tracking-widest">
                    T+{{ comment.timestamp }}
                </span>

                <button v-if="comment.can_edit && !isEditing" @click="isEditing = true"
                    class="opacity-0 group-hover/comment:opacity-100 text-slate-600 hover:text-fuchsia-400 transition-all p-0.5 outline-none cursor-pointer">
                    <Pencil :size="11" />
                </button>
            </div>
        </div>

        <div
            class="text-left pl-2 border-l border-sky-500/20 group-hover/comment:border-fuchsia-500/40 transition-colors">

            <p v-if="!isEditing" class="text-slate-300 text-sm font-light leading-relaxed">
                {{ comment.text }}
            </p>

            <div v-else class="flex items-center gap-2 mt-1 w-full animate-in fade-in duration-200">
                <input v-model="editedText" type="text" @keyup.enter="handleUpdate" @keyup.esc="cancelEdit"
                    class="grow bg-[#050914] border border-fuchsia-500/30 rounded-[0.5rem] px-3 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-fuchsia-500 transition-all font-light font-mono"
                    autofocus />
                <button @click="handleUpdate"
                    class="p-1 text-emerald-400 hover:text-emerald-300 transition-colors outline-none cursor-pointer">
                    <Check :size="14" />
                </button>
                <button @click="cancelEdit"
                    class="p-1 text-rose-400 hover:text-rose-300 transition-colors outline-none cursor-pointer">
                    <X :size="14" />
                </button>
            </div>
        </div>
    </div>
</template>
