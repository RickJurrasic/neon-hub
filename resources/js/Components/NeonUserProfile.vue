<script setup>
import { computed } from 'vue';
import { useNotificationStore } from '@/Stores/useNotificationStore';

// Sjednoceno s NeonSocialCore.vue, které posílá :entityId="selectedEntityId"
const props = defineProps({
    entityId: {
        type: Number,
        required: true
    }
});

defineEmits(['back']);

const store = useNotificationStore();

// Dynamicky najdeme entitu buď v žádostech, nebo v aktivních přátelích podle předaného entityId
const entity = computed(() => {
    // Prohledáme friendRequests (kontrolujeme přímé id nebo schované uživatelské user_id)
    const requestMatch = store.friendRequests.find(r => r.id === props.entityId || r.user_id === props.entityId);
    if (requestMatch) return requestMatch;

    // Prohledáme aktivní přátele
    const friendMatch = store.friends.find(f => f.id === props.entityId || f.user_id === props.entityId);
    return friendMatch || null;
});

// Pomocné reaktivní hodnoty s fallbacky pro případ, že data v DB chybí
const idTag = computed(() => entity.value?.name || `UNKNOWN_NODE_${props.entityId}`);
const networkRole = computed(() => entity.value?.role || 'EXTERNAL_NODE');
const bio = computed(() => entity.value?.bio || '"Záznam v šifrovaném biu je prázdný."');
const trustLevel = computed(() => entity.value?.trust_level ?? 50);
const latency = computed(() => entity.value?.latency || '24ms_STABLE');
</script>

<template>
    <div
        class="w-[82vw] max-w-sm md:w-full md:max-w-2xl bg-black/80 border border-purple-500/40 p-4 md:p-8 font-mono backdrop-blur-2xl shadow-[0_0_50px_rgba(168,85,247,0.2)] relative overflow-y-auto no-scrollbar max-h-[70vh] flex flex-col rounded-[2rem] shrink-0">

        <div
            class="absolute inset-0 bg-gradient-to-b from-transparent via-purple-500/5 to-transparent h-[200%] animate-scan-slow pointer-events-none">
        </div>

        <div class="flex justify-between items-start mb-4 border-b border-purple-500/30 pb-2 relative z-10 shrink-0">
            <div>
                <h2 class="text-base md:text-xl text-white tracking-[0.3em] uppercase italic">Entity_Profile</h2>
                <p class="text-[8px] md:text-[10px] text-purple-400/70 tracking-widest">
                    ACCESSING_DATABASE... {{ entity ? 'DONE' : 'NOT_FOUND' }}
                </p>
            </div>
            <button @click="$emit('back')"
                class="border border-purple-500/50 px-2 md:px-3 py-1 text-[9px] md:text-[10px] text-purple-400 hover:bg-purple-500/20 transition-all">
                CLOSE [X]
            </button>
        </div>

        <div v-if="entity" class="grow space-y-4 relative z-10 overflow-hidden">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6">

                <div class="col-span-1 flex flex-row md:flex-col gap-3 md:space-y-4 items-center md:items-stretch">
                    <div
                        class="w-16 h-16 md:w-full md:h-auto md:aspect-square bg-purple-900/20 border-2 border-purple-500/60 flex items-center justify-center relative group shrink-0 overflow-hidden">
                        <div
                            class="absolute inset-1.5 border border-purple-500/20 group-hover:border-purple-500/80 transition-all z-20 pointer-events-none">
                        </div>
                        <img v-if="entity.avatar" :src="entity.avatar"
                            class="w-full h-full object-cover relative z-10" />
                        <span v-else class="text-2xl md:text-4xl text-purple-500/40 relative z-10">?</span>
                    </div>

                    <div class="bg-white/5 border border-white/10 p-2 w-full">
                        <div class="text-[8px] text-purple-500">TRUST_LEVEL</div>
                        <div class="h-1 bg-purple-900 mt-1">
                            <div class="h-full bg-purple-500 shadow-[0_0_10px_#a855f7] transition-all duration-500"
                                :style="{ width: trustLevel + '%' }"></div>
                        </div>
                    </div>
                </div>

                <div class="col-span-1 md:col-span-2 space-y-3 md:space-y-6">
                    <div>
                        <label class="text-[8px] md:text-[9px] text-purple-500 block mb-1">IDENTIFICATION_TAG</label>
                        <div
                            class="text-white text-xs md:text-lg tracking-widest bg-white/5 p-1.5 border-l-2 border-purple-500 truncate uppercase">
                            {{ idTag }}
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-[8px] md:text-[9px] text-purple-500 block mb-1">LAST_SEEN_LATENCY</label>
                            <div class="text-[10px] md:text-xs text-white/80">{{ latency }}</div>
                        </div>
                        <div>
                            <label class="text-[8px] md:text-[9px] text-purple-500 block mb-1">NETWORK_ROLE</label>
                            <div class="text-[10px] md:text-xs text-white/80 uppercase">{{ networkRole }}</div>
                        </div>
                    </div>

                    <div class="border border-white/5 p-2 md:p-4 bg-white/[0.02]">
                        <label
                            class="text-[8px] md:text-[9px] text-purple-500 block mb-1 md:mb-2 underline">ENCRYPTED_BIO</label>
                        <p class="text-[9px] md:text-[11px] text-white/60 leading-tight italic whitespace-pre-line">
                            {{ bio }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="mt-4 flex flex-col md:flex-row gap-2 md:gap-4 shrink-0">
                <button
                    class="w-full bg-purple-600/20 border border-purple-500/50 py-2 text-[9px] md:text-[10px] text-white uppercase hover:bg-purple-600/40 transition-all tracking-tighter truncate">
                    Send_Encrypted_Message
                </button>
                <button
                    class="w-full border border-red-500/30 py-2 text-[9px] md:text-[10px] text-red-400 uppercase hover:bg-red-500/10 transition-all tracking-tighter truncate">
                    Sever_Connection
                </button>
            </div>
        </div>

        <div v-else class="text-center py-12 text-red-400 text-xs tracking-widest">
            >> ERROR: INVALID_ENTITY_ID_ACCESS_DENIED << </div>
        </div>
</template>
