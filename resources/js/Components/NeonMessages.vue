<script setup>
import { storeToRefs } from 'pinia';
import { useNotificationStore } from '@/Stores/useNotificationStore';

const emit = defineEmits(['back']);
const store = useNotificationStore();
const { messages } = storeToRefs(store);
</script>

<template>
    <div
        class="w-[82vw] md:w-full md:max-w-2xl bg-black/60 border border-cyan-500/20 p-5 md:p-8 font-mono rounded-[2rem] flex flex-col max-h-[75vh] overflow-hidden">

        <div class="flex justify-between mb-8 border-b border-cyan-500/10 pb-2 shrink-0">
            <span class="text-cyan-400 text-xs">>> INBOX_DECRYPTED</span>
            <button @click="$emit('back')"
                class="text-[10px] hover:text-white text-cyan-700 underline">CLOSE_SESSION</button>
        </div>

        <div class="space-y-6 overflow-y-auto no-scrollbar grow pr-1">
            <div v-for="msg in messages" :key="msg.id" class="border-l-2 pl-4 py-2 transition-all duration-500"
                :class="msg.read ? 'border-fuchsia-900 bg-fuchsia-900/5' : 'border-fuchsia-600 bg-fuchsia-600/5'">

                <div class="text-[9px]" :class="msg.read ? 'text-fuchsia-800' : 'text-fuchsia-500'">
                    FROM: {{ msg.sender || 'UNKNOWN_SOURCE' }}
                </div>
                <div class="text-sm" :class="msg.read ? 'text-white/40' : 'text-white'">
                    "{{ msg.text }}"
                </div>
                <div class="text-[8px] mt-1" :class="msg.read ? 'text-white/10' : 'text-white/30'">
                    {{ msg.time }}
                </div>
            </div>

            <div v-if="messages.length === 0" class="text-center text-white/20 text-[10px] py-10">
                >> NO_ENCRYPTED_MESSAGES_FOUND
            </div>
        </div>
    </div>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    width: 2px;
}

.no-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(168, 85, 247, 0.2);
    border-radius: 10px;
}
</style>
