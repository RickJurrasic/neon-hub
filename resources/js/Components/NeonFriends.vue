<script setup>
import { storeToRefs } from 'pinia';
import { useNotificationStore } from '@/Stores/useNotificationStore';

const emit = defineEmits(['back', 'view-profile']);
const store = useNotificationStore();
const { friendRequests } = storeToRefs(store);
</script>

<template>
    <div
        class="w-[82vw] max-w-xs md:w-full md:max-w-md bg-black/60 border border-purple-500/30 p-6 font-mono backdrop-blur-xl shadow-[0_0_30px_rgba(168,85,247,0.15)] rounded-[2rem] flex flex-col max-h-[75vh] overflow-hidden">

        <div class="flex justify-between mb-6 border-b border-purple-500/20 pb-2 shrink-0">
            <span class="text-purple-400 text-xs tracking-tighter">>> NEON_NETWORK_ENTITIES</span>
            <button @click="$emit('back')"
                class="text-[9px] hover:text-white text-purple-700 underline">TERMINATE_LINK</button>
        </div>

        <div class="space-y-4 overflow-y-auto no-scrollbar grow pr-1">
            <div v-for="req in friendRequests" :key="req.id" @click="$emit('view-profile', req.id)"
                class="group flex items-center justify-between p-3 border transition-all cursor-pointer" :class="req.read
                    ? 'border-white/5 bg-white/5 hover:border-purple-500/30'
                    : 'border-purple-500/30 bg-purple-900/10 hover:border-purple-500/50'">

                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 border flex items-center justify-center"
                        :class="req.read ? 'bg-purple-900/10 border-purple-500/20 text-purple-600' : 'bg-purple-900/30 border-purple-500/50 text-purple-400'">
                        <span :class="!req.read ? 'animate-pulse' : ''">ID</span>
                    </div>
                    <div>
                        <div class="text-xs uppercase tracking-widest"
                            :class="req.read ? 'text-white/40' : 'text-white'">
                            {{ req.name || 'Entity_' + req.id }}
                        </div>
                        <div class="text-[9px]" :class="req.read ? 'text-purple-500/30' : 'text-purple-500/70'">
                            STATUS: {{ req.read ? 'LINK_ESTABLISHED' : 'NEW_SIGNAL_DETECTED' }}
                        </div>
                    </div>
                </div>

                <div class="text-[10px] transition-opacity"
                    :class="req.read ? 'text-purple-700' : 'text-purple-400 opacity-0 group-hover:opacity-100'">
                    [ VIEW_PROFILE ]
                </div>
            </div>

            <div v-if="friendRequests.length === 0" class="text-center text-purple-500/20 text-[10px] py-10">
                >> NO_PENDING_REQUESTS
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
