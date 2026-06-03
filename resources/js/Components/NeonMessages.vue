<script setup>
import { ref, computed, onMounted, nextTick, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useNotificationStore } from '@/Stores/useNotificationStore'

const emit = defineEmits(['back'])

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

/**
 * INBOX = poslední zpráva z každé konverzace (Nejnovější konverzace nahoře)
 */
const inboxConversations = computed(() => {
    const map = {}

    messages.value.forEach(m => {
        const convId = m.conversation_id
        if (!convId) return

        // FIX: Porovnáváme primárně přes kompletní timestamp created_at
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

    // Seřadíme konverzace v inboxu tak, aby nejnovější podle vytvoření byla nahoře
    return Object.values(map).sort(
        (a, b) => new Date(b.created_at || b.time) - new Date(a.created_at || a.time)
    )
})

/**
 * CHAT = Chronologické řazení (Nejstarší nahoře -> Nejnovější dole)
 */
const activeChatMessages = computed(() => {
    if (!activeConversationId.value) return []

    return messages.value
        .filter(m => String(m.conversation_id) === String(activeConversationId.value))
        .map(m => {
            // KLÍČOVÁ OPRAVA: Použijeme ...m, abychom stoprocentně zachovali 'role',
            // 'content' a všechny ostatní původní parametry z backendu!
            return {
                ...m,
                // Pro jistotu sjednotíme text i content, ať šablona najde obojí pod jakýmkoliv názvem
                text: m.text || m.content || '',
                content: m.content || m.text || '',
                // Připravíme si časové razítko pro sort
                createdAt: m.created_at || m.time
            }
        })
        // Neprůstřelný chronologický sort (Od nejstarší zprávy nahoře po nejnovější dole)
        .sort((a, b) => {
            const dateA = new Date(a.createdAt).getTime()
            const dateB = new Date(b.createdAt).getTime()

            if (!isNaN(dateA) && !isNaN(dateB)) {
                return dateA - dateB
            }
            return 0
        })
})

/**
 * AUTO SCROLL
 */
const scrollToBottom = () => {
    nextTick(() => {
        if (scrollContainer.value) {
            scrollContainer.value.scrollTop = scrollContainer.value.scrollHeight
        }
    })
}

// Sledujeme změny ve zprávách a odpalujeme scroll dolů
watch(activeChatMessages, () => {
    scrollToBottom()
}, { deep: true })

function openChat(conv) {
    activeConversationId.value = conv.conversation_id
    activeAgentName.value = conv.agentName
    referenceMessageId.value = conv.lastMessageId
    currentView.value = 'chat'
    replyText.value = ''

    // Zdvojený pojistný scroll, protože v-else-if v šabloně může chvíli montovat DOM element do stránky
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
    // Zavoláme novou akci ve storu (viz krok 2 níže)
    await store.deleteConversation(conversationId)

    // Po úspěšném smazání resetujeme stav, čímž se menu schová
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

                <button @click="$emit('back')" class="text-[10px] text-cyan-700 underline active:text-white">
                    CLOSE_SESSION
                </button>
            </div>

            <div class="space-y-6 overflow-y-auto grow pr-1">
                <div v-for="conv in inboxConversations" :key="conv.conversation_id"
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
                        <button @click="openChat(conv)" class="text-[10px] text-cyan-500 underline">
                            [OPEN SECURE CHANNEL]
                        </button>

                        <button @click="deletingId = conv.conversation_id" class="text-[10px] text-red-500 underline">
                            [PURGE_NODE]
                        </button>
                    </div>

                    <div v-if="deletingId === conv.conversation_id"
                        class="mt-3 p-3 border border-red-500/30 bg-red-950/10 rounded-xl text-xs text-red-400 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 transition-all duration-300">
                        <span class="font-mono animate-pulse text-[10px]">
                            >> CRITICAL: WIPE ENTIRE DATA NODE AND HISTORY?
                        </span>
                        <div class="flex gap-4 font-bold text-[10px]">
                            <button @click="purgeConversation(conv.conversation_id)"
                                class="text-red-500 underline hover:text-red-400">
                                [YES_PURGE]
                            </button>
                            <button @click="deletingId = null" class="text-white/50 underline hover:text-white">
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

                <button @click="closeChat" class="text-[10px] text-cyan-600 underline">
                    BACK_TO_INBOX
                </button>
            </div>

            <!-- Kontejner se zprávami -->
            <div ref="scrollContainer" class="space-y-4 overflow-y-auto grow mb-4 flex flex-col p-1">
                <div v-for="cMsg in activeChatMessages" :key="cMsg.id"
                    class="border-l-2 pl-3 py-1.5 rounded-r-lg max-w-[90%] transition-all duration-200" :class="cMsg.sender === 'YOU'
                        ? 'border-cyan-500 bg-cyan-500/5 self-end text-right'
                        : 'border-fuchsia-600 bg-fuchsia-600/5 self-start'">
                    <div class="text-[8px] uppercase text-fuchsia-500">
                        {{ cMsg.sender }}
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
                    <input v-model="replyText" type="text" placeholder="Type datastream..."
                        class="bg-black border border-cyan-500/20 rounded-lg px-3 py-2 text-xs text-white w-full focus:outline-none focus:border-cyan-500"
                        @keyup.enter="submitChatReply" />

                    <button @click="submitChatReply"
                        class="text-[10px] bg-cyan-500 text-black px-5 py-2 rounded-lg font-bold hover:bg-cyan-400 active:scale-95 transition-all">
                        SEND
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

/* Stylování scrollbaru pro tvůj sci-fi look */
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
