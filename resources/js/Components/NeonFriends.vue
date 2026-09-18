<script setup>
import { ref } from 'vue';
import { storeToRefs } from 'pinia';
import { useNotificationStore } from '@/Stores/useNotificationStore';

defineEmits(['back', 'view-profile']);

const store = useNotificationStore();
const { friendRequests, friends } = storeToRefs(store);

const decliningRequestId = ref(null);
const unlinkingFriendId = ref(null);

const acceptRequest = async (id) => {
    const success = await store.acceptFriendRequest(id);
    if (!success) console.error("Accept failed");
};

const declineRequest = async (id) => {
    const success = await store.declineFriendRequest(id);
    if (success) {
        decliningRequestId.value = null;
    } else {
        console.error("Decline failed");
    }
};

const removeLink = async (id) => {
    try {
        // Pokud máš v store i akci pro smazání aktivního přítele, zavolej ji. 
        // Případně pokud pro to používáš přímý endpoint, zkontroluj jeho URL (často bývá /friendships/{id}).
        await axios.delete(`/friendships/${id}`);
        store.removeFriend(id);
        unlinkingFriendId.value = null;
    } catch (e) { console.error("Unlink failed", e); }
};
</script>

<template>

    <div
        class="w-[82vw] max-w-xs md:w-full md:max-w-md bg-black/60 border border-purple-500/30 p-6 font-mono backdrop-blur-xl rounded-[2rem] flex flex-col max-h-[75vh]">

        <div class="flex justify-between mb-6 border-b border-purple-500/20 pb-2">
            <span class="text-purple-400 text-xs tracking-tighter">>> NEON_NETWORK_ENTITIES</span>
            <button
                type="button" class="text-[9px] text-purple-700 underline active:text-white py-1 px-2 -mr-2 select-none"
                @click="$emit('back')">TERMINATE_LINK</button>
        </div>

        <div class="space-y-4 overflow-y-auto no-scrollbar grow">

            <div
                v-for="req in friendRequests" :key="req.id" class="flex items-center justify-between p-3 border border-purple-500/30 bg-purple-900/10 cursor-pointer active:bg-purple-500/10 transition-all group"
            >
                <button type="button" class="flex items-center gap-4 flex-1 text-left" @click="$emit('view-profile', req.id)">
                    <div
                        class="w-8 h-8 border border-purple-500/50 flex items-center justify-center bg-purple-900/30 text-purple-400 group-hover:border-purple-400">
                        <span
                            :class="!req.read ? 'animate-pulse text-purple-300 shadow-[0_0_10px_#a855f7]' : ''">ID</span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-white text-xs uppercase tracking-wider">{{ req.name }}</span>
                        <span class="text-[8px] text-purple-400/60">REQUEST_PENDING</span>
                    </div>
                </button>

                <div v-if="req.status === 'pending'" class="flex gap-2 shrink-0">
                    <template v-if="decliningRequestId === req.id">
                        <span class="text-[8px] text-red-500 animate-pulse font-bold self-center">DECLINE?</span>
                        <button
                            type="button" class="text-[9px] px-2 py-1 bg-red-950/50 border border-red-500/40 text-red-400 active:bg-red-500 active:text-black font-bold select-none"
                            @click.stop="declineRequest(req.id)">
                            YES
                        </button>
                        <button
                            type="button" class="text-[9px] px-2 py-1 text-white/40 underline active:text-white select-none"
                            @click.stop="decliningRequestId = null">
                            NO
                        </button>
                    </template>
                    <template v-else>
                        <button
                            type="button" class="text-[9px] px-2 py-1.5 bg-green-900/20 border border-green-500/30 text-green-400 active:bg-green-500/40 transition-all select-none"
                            @click.stop="acceptRequest(req.id)">
                            ACCEPT
                        </button>
                        <button
                            type="button" class="text-[9px] px-2 py-1.5 bg-red-900/20 border border-red-500/30 text-red-400 active:bg-red-500/40 transition-all select-none"
                            @click.stop="decliningRequestId = req.id">
                            DECLINE
                        </button>
                    </template>
                </div>
            </div>

            <div v-if="friends.length > 0" class="mt-6">
                <div class="text-purple-400 text-xs pb-2 border-b border-purple-500/20">>> ACTIVE_LINKS</div>

                <div
                    v-for="friend in friends" :key="friend.id" class="flex items-center justify-between p-3 border border-purple-500/10 mt-2 bg-white/[0.01] cursor-pointer active:bg-purple-500/5 transition-all group"
                >

                    <button type="button" class="flex-1" @click="$emit('view-profile', friend.id)">
                        <div class="flex items-center gap-4">
                            <div class="w-2 h-2 rounded-full bg-purple-500 shadow-[0_0_8px_#a855f7] animate-pulse"></div>
                            <span
                                class="text-white text-xs uppercase tracking-wider group-hover:text-purple-300 transition-colors">{{
                                friend.name }}</span>
                        </div>
                    </button>

                    <div v-if="unlinkingFriendId === friend.id" class="flex items-center gap-2 shrink-0" @click.stop>
                        <span class="text-[8px] text-red-500 animate-pulse font-bold">UNLINK?</span>
                        <button
                            type="button" class="text-[9px] px-2 py-1 bg-red-950/50 border border-red-500/40 text-red-400 active:bg-red-500 active:text-black font-bold select-none"
                            @click="removeLink(friend.id)">
                            YES
                        </button>
                        <button
                            type="button" class="text-[9px] px-2 py-1 text-white/40 underline active:text-white select-none"
                            @click="unlinkingFriendId = null">
                            NO
                        </button>
                    </div>
                    <button
                        v-else type="button" class="text-[9px] text-red-500 active:text-red-400 underline relative z-15 py-2 px-3 -mr-2 select-none"
                        @click.stop="unlinkingFriendId = friend.id">
                        UNLINK
                    </button>
                </div>
            </div>

            <div
                v-if="friendRequests.length === 0 && friends.length === 0"
                class="text-center text-purple-500/20 text-[10px] py-10">
                >> NO_ACTIVE_OR_PENDING_LINKS
            </div>
        </div>
    </div>
</template>