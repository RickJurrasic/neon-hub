<script setup>
import { ref, computed, onMounted, nextTick, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useNotificationStore } from '@/Stores/useNotificationStore'

defineEmits(['back'])

const store = useNotificationStore()
const { messages } = storeToRefs(store)

const currentView = ref('inbox')
const scrollContainer = ref(null)

const activeConversationId = ref(null)
const activeAgentName = ref('')
const referenceMessageId = ref(null)

const deletingId = ref(null)
const replyText = ref('')

onMounted(() => {
    store.fetchMessages()
})

const inboxConversations = computed(() => {
    const map = {}

    messages.value.forEach(m => {
        const convId = m.conversation_id
        if (!convId) return

        const messageDate = new Date(m.created_at || m.time || 0)

        if (!map[convId] || new Date(map[convId].created_at || map[convId].time || 0) < messageDate) {
            map[convId] = {
                conversation_id: convId,
                text: m.text || m.content || '',
                time: m.time,
                created_at: m.created_at || m.time,
                sender: m.sender || m.role || 'UNKNOWN',
                agentName: m.agent_name || m.agentName || '',
                lastMessageId: m.id,
                read: m.read ?? false
            }
        }
    })

    return Object.values(map).sort(
        (a, b) => new Date(b.created_at || b.time) - new Date(a.created_at || a.time)
    )
})

const activeChatMessages = computed(() => {
    if (!activeConversationId.value) return []

    return messages.value
        .filter(m => String(m.conversation_id) === String(activeConversationId.value))
        .map(m => {
            return {
                ...m,
                text: m.text || m.content || '',
                content: m.content || m.text || '',
                createdAt: m.created_at || m.time
            }
        })
        .sort((a, b) => {
            const dateA = new Date(a.createdAt).getTime()
            const dateB = new Date(b.createdAt).getTime()

            if (!Number.isNaN(dateA) && !Number.isNaN(dateB)) {
                return dateA - dateB
            }
            return 0
        })
})

const scrollToBottom = () => {
    nextTick(() => {
        if (scrollContainer.value) {
            scrollContainer.value.scrollTop = scrollContainer.value.scrollHeight
        }
    })
}

watch(activeChatMessages, () => {
    scrollToBottom()
}, { deep: true })

function openChat(conv) {
    activeConversationId.value = conv.conversation_id
    activeAgentName.value = conv.agentName
    referenceMessageId.value = conv.lastMessageId
    currentView.value = 'chat'
    replyText.value = ''

    scrollToBottom()
    setTimeout(scrollToBottom, 50)
}

function closeChat() {
    currentView.value = 'inbox'
    activeConversationId.value = null
    activeAgentName.value = ''
    referenceMessageId.value = null
}

async function submitChatReply() {
    if (!replyText.value.trim()) return

    const textToSend = replyText.value
    replyText.value = ''

    const success = await store.sendReply(
        referenceMessageId.value,
        textToSend
    )

    if (!success) {
        replyText.value = textToSend
        return
    }

    const latest = messages.value
        .filter(m => m.conversation_id === activeConversationId.value)
        .slice(-1)[0]

    if (latest) {
        referenceMessageId.value = latest.id
    }
}

async function purgeConversation(conversationId) {
    await store.deleteConversation(conversationId)
    deletingId.value = null
}
</script>

<template>
    <div
        class="w-[82vw] md:w-full md:max-w-2xl bg-black/70 border border-cyan-500/20 p-4 md:p-8 font-mono rounded-[2rem] flex flex-col h-[75vh] max-h-[75vh] overflow-hidden backdrop-blur-md">

        <template v-if="currentView === 'inbox'">
            <div class="flex justify-between mb-6 border-b border-cyan-500/10 pb-2 shrink-0">
                <span class="text-cyan-400 text-xs animate-pulse">
                    >> INBOX_DECRYPTED
                </span>

                <button type="button" class="text-[10px] text-cyan-700 underline active:text-white" @click="$emit('back')">
                    CLOSE_SESSION
                </button>
            </div>

            <div class="space-y-6 overflow-y-auto grow pr-1">
                <div
                    v-for="conv in inboxConversations" :key="conv.conversation_id"
                    class="border-l-2 pl-4 py-2 transition-all duration-300 border-fuchsia-600 bg-fuchsia-600/5">
                    <div class="text-[9px] text-fuchsia-500">
                        <span v-if="conv.sender === 'YOU'">
                            FROM: YOU ➔ {{ conv.agentName }}
                        </span>
                        <span v-else>
                            FROM: {{ conv.sender }}
                        </span>
                    </div>

                    <div class="text-sm mt-1 text-white break-words">
                        "{{ conv.text }}"
                    </div>

                    <div class="text-[8px] mt-1 text-white/30">
                        {{ conv.time }}
                    </div>

                    <div class="flex gap-4 mt-2 pt-1 border-t border-white/10">
                        <button type="button" class="text-[10px] text-cyan-500 underline" @click="openChat(conv)">
                            [OPEN SECURE CHANNEL]
                        </button>

                        <button type="button" class="text-[10px] text-red-500 underline" @click="deletingId = conv.conversation_id">
                            [PURGE_NODE]
                        </button>
                    </div>

                    <div
                        v-if="deletingId === conv.conversation_id"
                        class="mt-3 p-3 border border-red-500/30 bg-red-950/10 rounded-xl text-xs text-red-400 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 transition-all duration-300">
                        <span class="font-mono animate-pulse text-[10px]">
                            >> CRITICAL: WIPE ENTIRE DATA NODE AND HISTORY?
                        </span>
                        <div class="flex gap-4 font-bold text-[10px]">
                            <button
                                type="button" class="text-red-500 underline hover:text-red-400"
                                @click="purgeConversation(conv.conversation_id)">
                                [YES_PURGE]
                            </button>
                            <button type="button" class="text-white/50 underline hover:text-white" @click="deletingId = null">
                                [NO_ABORT]
                            </button>
                        </div>
                    </div>
                </div>

                <div v-if="inboxConversations.length === 0" class="text-center text-white/20 text-[10px] py-10">
                    >> NO_ENCRYPTED_MESSAGES_FOUND
                </div>
            </div>
        </template>

        <template v-else-if="currentView === 'chat'">
            <div class="flex justify-between mb-4 border-b border-cyan-500/20 pb-2 shrink-0">
                <span class="text-cyan-400 text-xs">
                    >> SECURE_CHANNEL:
                    <span class="text-fuchsia-400 font-bold">
                        {{ activeAgentName }}
                    </span>
                </span>

                <button type="button" class="text-[10px] text-cyan-600 underline" @click="closeChat">
                    BACK_TO_INBOX
                </button>
            </div>

            <div ref="scrollContainer" class="space-y-4 overflow-y-auto grow mb-4 flex flex-col p-1">
                <div
                    v-for="cMsg in activeChatMessages" :key="cMsg.id"
                    class="border-l-2 pl-3 py-1.5 rounded-r-lg max-w-[90%] transition-all duration-200"
                    :class="(cMsg.sender === 'YOU' || cMsg.role === 'user')
                        ? 'border-cyan-500 bg-cyan-500/5 self-end text-right'
                        : 'border-fuchsia-600 bg-fuchsia-600/5 self-start'">

                    <div
                        class="text-[8px] uppercase"
                        :class="(cMsg.sender === 'YOU' || cMsg.role === 'user') ? 'text-cyan-500' : 'text-fuchsia-500'">
                        {{ (cMsg.sender === 'YOU' || cMsg.role === 'user') ? 'YOU' : cMsg.sender }}
                    </div>

                    <div class="text-xs text-white mt-1 break-words">
                        "{{ cMsg.text }}"
                    </div>

                    <div class="text-[7px] text-white/30 mt-1">
                        {{ cMsg.time }}
                    </div>
                </div>
            </div>

            <div class="border border-cyan-500/30 p-2 bg-black/90 rounded-xl shrink-0">
                <div class="flex gap-2">
                    <label for="chat-reply-input" class="sr-only">Type datastream</label>
                                        <input
                        id="chat-reply-input" v-model="replyText" type="text" placeholder="Type datastream..."
                        class="bg-black border border-cyan-500/20 rounded-lg px-3 py-2 text-xs text-white w-full focus:outline-none focus:border-cyan-500"
                        :disabled="store.isRateLimited"
                        @keyup.enter="submitChatReply" />

                    <button
                        type="button"
                        class="text-[10px] bg-cyan-500 text-black px-5 py-2 rounded-lg font-bold transition-all"
                        :class="store.isRateLimited ? 'opacity-40 cursor-not-allowed' : 'hover:bg-cyan-400 active:scale-95'"
                        :disabled="store.isRateLimited"
                        @click="submitChatReply">
                        <span v-if="store.isRateLimited">COOLDOWN ({{ store.rateLimitRetry }}s)</span>
                        <span v-else>SEND</span>
                    </button>
                </div>
            </div>
        </template>
    </div>
</template>

<style scoped>
.space-y-4 {
    scroll-behavior: smooth;
}

div::-webkit-scrollbar {
    width: 4px;
}

div::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.3);
}

div::-webkit-scrollbar-thumb {
    background: rgba(6, 182, 212, 0.3);
    border-radius: 4px;
}

div::-webkit-scrollbar-thumb:hover {
    background: rgba(6, 182, 212, 0.6);
}
</style>