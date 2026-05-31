<script setup>
import { storeToRefs } from 'pinia';
import { useNotificationStore } from '@/Stores/useNotificationStore';

const emit = defineEmits(['back', 'view-profile']);
const store = useNotificationStore();
const { friendRequests, friends } = storeToRefs(store);

const acceptRequest = async (id) => {
    try {
        await axios.patch(`/friendships/${id}`, { status: 'accepted' });
        store.updateFriendRequestStatus(id, 'accepted');
    } catch (e) { console.error("Accept failed", e); }
};

const declineRequest = async (id) => {
    try {
        await axios.delete(`/friendships/${id}`);
        store.removeFriendRequest(id);
    } catch (e) { console.error("Decline failed", e); }
};

const removeLink = async (id) => {
    try {
        await axios.delete(`/friendships/${id}`);
        store.removeFriend(id);
    } catch (e) { console.error("Unlink failed", e); }
};
</script>

<template>
    <div
        class="w-[82vw] max-w-xs md:w-full md:max-w-md bg-black/60 border border-purple-500/30 p-6 font-mono backdrop-blur-xl rounded-[2rem] flex flex-col max-h-[75vh]">

        <div class="flex justify-between mb-6 border-b border-purple-500/20 pb-2">
            <span class="text-purple-400 text-xs tracking-tighter">>> NEON_NETWORK_ENTITIES</span>
            <button @click="$emit('back')"
                class="text-[9px] hover:text-white text-purple-700 underline">TERMINATE_LINK</button>
        </div>

        <div class="space-y-4 overflow-y-auto no-scrollbar grow">
            <div v-for="req in friendRequests" :key="req.id" @click="$emit('view-profile', req.id)"
                class="flex items-center justify-between p-3 border border-purple-500/30 bg-purple-900/10 cursor-pointer">

                <div class="flex items-center gap-4">
                    <div
                        class="w-8 h-8 border border-purple-500/50 flex items-center justify-center bg-purple-900/30 text-purple-400">
                        <span :class="!req.read ? 'animate-pulse' : ''">ID</span>
                    </div>
                    <span class="text-white text-xs">{{ req.name }}</span>
                </div>

                <div class="flex gap-2" v-if="req.status === 'pending'">
                    <button @click.stop="acceptRequest(req.id)"
                        class="text-[9px] px-2 py-1 bg-green-900/20 border border-green-500/30 text-green-400 hover:bg-green-500/20">ACCEPT</button>
                    <button @click.stop="declineRequest(req.id)"
                        class="text-[9px] px-2 py-1 bg-red-900/20 border border-red-500/30 text-red-400 hover:bg-red-500/20">DECLINE</button>
                </div>
            </div>

            <div v-if="friends.length > 0" class="mt-6">
                <div class="text-purple-400 text-xs pb-2 border-b border-purple-500/20">>> ACTIVE_LINKS</div>
                <div v-for="friend in friends" :key="friend.id"
                    class="flex items-center justify-between p-3 border border-purple-500/10 mt-2">
                    <span class="text-white text-xs">{{ friend.name }}</span>
                    <button @click="removeLink(friend.id)"
                        class="text-[9px] text-red-500 hover:text-red-300 underline">UNLINK</button>
                </div>
            </div>

            <div v-if="friendRequests.length === 0 && friends.length === 0"
                class="text-center text-purple-500/20 text-[10px] py-10">
                >> NO_ACTIVE_OR_PENDING_LINKS
            </div>
        </div>
    </div>
</template>
