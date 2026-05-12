<template>
    <div
        class="w-full max-w-md bg-black/60 border border-purple-500/30 p-6 font-mono backdrop-blur-xl shadow-[0_0_30px_rgba(168,85,247,0.15)]">
        <div class="flex justify-between mb-6 border-b border-purple-500/20 pb-2">
            <span class="text-purple-400 text-xs tracking-tighter">>> NEON_NETWORK_ENTITIES</span>
            <button @click="$emit('back')" class="text-[9px] hover:text-white text-purple-700">TERMINATE_LINK</button>
        </div>

        <div class="space-y-4">
            <!-- Ukázka jednoho "přítele" / entity -->
            <div v-for="i in 3" :key="i"
                class="group flex items-center justify-between p-3 border border-white/5 hover:border-purple-500/50 transition-all cursor-pointer bg-white/5">
                <div class="flex items-center gap-4">
                    <div
                        class="w-10 h-10 bg-purple-900/30 border border-purple-500/50 flex items-center justify-center text-purple-400">
                        <span class="animate-pulse">ID</span>
                    </div>
                    <div>
                        <div class="text-xs text-white uppercase tracking-widest">Entity_0{{ i }}</div>
                        <div class="text-[9px] text-purple-500/70">STATUS: ENCRYPTED_SIGNAL</div>
                    </div>
                </div>
                <div class="text-[10px] text-purple-400 opacity-0 group-hover:opacity-100 transition-opacity">
                    [ VIEW_PROFILE ]
                </div>
            </div>
        </div>
    </div>
</template>
