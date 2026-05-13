<template>
    <div
        class="w-full max-w-2xl bg-black/80 border border-purple-500/40 p-8 font-mono backdrop-blur-2xl shadow-[0_0_50px_rgba(168,85,247,0.2)] relative overflow-hidden">

        <!-- Dekorační scan-line efekt -->
        <div
            class="absolute inset-0 bg-gradient-to-b from-transparent via-purple-500/5 to-transparent h-[200%] animate-scan-slow pointer-events-none">
        </div>

        <!-- Header -->
        <div class="flex justify-between items-start mb-8 border-b border-purple-500/30 pb-4 relative z-10">
            <div>
                <h2 class="text-white text-xl tracking-[0.3em] uppercase italic">Entity_Profile</h2>
                <p class="text-[10px] text-purple-400/70 tracking-widest">ACCESSING_LOCAL_DATABASE... DONE</p>
            </div>
            <button @click="$emit('back')"
                class="border border-purple-500/50 px-3 py-1 text-[10px] text-purple-400 hover:bg-purple-500/20 transition-all">
                CLOSE_SESSION [X]
            </button>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-3 gap-6 relative z-10">

            <!-- Levý sloupec: Avatar a Rank -->
            <div class="col-span-1 space-y-4">
                <div
                    class="aspect-square bg-purple-900/20 border-2 border-purple-500/60 flex items-center justify-center relative group">
                    <div
                        class="absolute inset-2 border border-purple-500/20 group-hover:border-purple-500/80 transition-all">
                    </div>
                    <span class="text-4xl text-purple-500/40">?</span>
                </div>
                <div class="bg-white/5 border border-white/10 p-2">
                    <div class="text-[8px] text-purple-500">TRUST_LEVEL</div>
                    <div class="h-1 bg-purple-900 mt-1">
                        <div class="h-full bg-purple-500 w-[75%] shadow-[0_0_10px_#a855f7]"></div>
                    </div>
                </div>
            </div>

            <!-- Pravý sloupec: Data -->
            <div class="col-span-2 space-y-6">
                <div>
                    <label class="text-[9px] text-purple-500 block mb-1">IDENTIFICATION_TAG</label>
                    <div class="text-white text-lg tracking-widest bg-white/5 p-2 border-l-2 border-purple-500">
                        RICK_JURRASIC_BEYOND_01
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-[9px] text-purple-500 block mb-1">LAST_SEEN_LATENCY</label>
                        <div class="text-xs text-white/80">14ms_STABLE</div>
                    </div>
                    <div>
                        <label class="text-[9px] text-purple-500 block mb-1">NETWORK_ROLE</label>
                        <div class="text-xs text-white/80 uppercase">Architect_Core</div>
                    </div>
                </div>

                <div class="border border-white/5 p-4 bg-white/[0.02]">
                    <label class="text-[9px] text-purple-500 block mb-2 underline">ENCRYPTED_BIO</label>
                    <p class="text-[11px] text-white/60 leading-relaxed italic">
                        "Zabývá se vývojem Neonových rozhraní a AI-healing experimenty. Často k vidění v klášterních
                        zahradách při plánování sémantických struktur."
                    </p>
                </div>
            </div>
        </div>

        <!-- Footer / Action Buttons -->
        <div class="mt-8 flex gap-4 relative z-10">
            <button
                class="flex-1 bg-purple-600/20 border border-purple-500/50 py-2 text-[10px] text-white uppercase hover:bg-purple-600/40 transition-all tracking-tighter">
                Send_Encrypted_Message
            </button>
            <button
                class="flex-1 border border-red-500/30 py-2 text-[10px] text-red-400 uppercase hover:bg-red-500/10 transition-all tracking-tighter">
                Sever_Connection
            </button>
        </div>
    </div>
</template>

<style scoped>
@keyframes scan {
    from {
        transform: translateY(-100%);
    }

    to {
        transform: translateY(100%);
    }
}

.animate-scan-slow {
    animation: scan 8s linear infinite;
}
</style>
