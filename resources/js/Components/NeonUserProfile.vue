<template>
    <div
        class="w-[82vw] max-w-sm md:w-full md:max-w-2xl bg-black/80 border border-purple-500/40 p-4 md:p-8 font-mono backdrop-blur-2xl shadow-[0_0_50px_rgba(168,85,247,0.2)] relative overflow-y-auto no-scrollbar max-h-[70vh] flex flex-col rounded-[2rem] shrink-0">

        <div
            class="absolute inset-0 bg-gradient-to-b from-transparent via-purple-500/5 to-transparent h-[200%] animate-scan-slow pointer-events-none">
        </div>

        <div class="flex justify-between items-start mb-4 border-b border-purple-500/30 pb-2 relative z-10 shrink-0">
            <div>
                <h2 class="text-base md:text-xl text-white tracking-[0.3em] uppercase italic">Entity_Profile</h2>
                <p class="text-[8px] md:text-[10px] text-purple-400/70 tracking-widest">ACCESSING_DATABASE... DONE</p>
            </div>
            <button @click="$emit('back')"
                class="border border-purple-500/50 px-2 md:px-3 py-1 text-[9px] md:text-[10px] text-purple-400 hover:bg-purple-500/20 transition-all">
                CLOSE [X]
            </button>
        </div>

        <div class="grow space-y-4 relative z-10 overflow-hidden">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6">

                <div class="col-span-1 flex flex-row md:flex-col gap-3 md:space-y-4 items-center md:items-stretch">
                    <div
                        class="w-16 h-16 md:w-full md:h-auto md:aspect-square bg-purple-900/20 border-2 border-purple-500/60 flex items-center justify-center relative group shrink-0">
                        <div
                            class="absolute inset-1.5 border border-purple-500/20 group-hover:border-purple-500/80 transition-all">
                        </div>
                        <span class="text-2xl md:text-4xl text-purple-500/40">?</span>
                    </div>
                    <div class="bg-white/5 border border-white/10 p-2 w-full">
                        <div class="text-[8px] text-purple-500">TRUST_LEVEL</div>
                        <div class="h-1 bg-purple-900 mt-1">
                            <div class="h-full bg-purple-500 w-[75%] shadow-[0_0_10px_#a855f7]"></div>
                        </div>
                    </div>
                </div>

                <div class="col-span-1 md:col-span-2 space-y-3 md:space-y-6">
                    <div>
                        <label class="text-[8px] md:text-[9px] text-purple-500 block mb-1">IDENTIFICATION_TAG</label>
                        <div
                            class="text-white text-xs md:text-lg tracking-widest bg-white/5 p-1.5 border-l-2 border-purple-500 truncate">
                            RICK_JURRASIC_BEYOND_01
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-[8px] md:text-[9px] text-purple-500 block mb-1">LAST_SEEN_LATENCY</label>
                            <div class="text-[10px] md:text-xs text-white/80">14ms_STABLE</div>
                        </div>
                        <div>
                            <label class="text-[8px] md:text-[9px] text-purple-500 block mb-1">NETWORK_ROLE</label>
                            <div class="text-[10px] md:text-xs text-white/80 uppercase">Architect_Core</div>
                        </div>
                    </div>

                    <div class="border border-white/5 p-2 md:p-4 bg-white/[0.02]">
                        <label
                            class="text-[8px] md:text-[9px] text-purple-500 block mb-1 md:mb-2 underline">ENCRYPTED_BIO</label>
                        <p class="text-[9px] md:text-[11px] text-white/60 leading-tight italic truncate-3-lines">
                            "Zabývá se vývojem Neonových rozhraní a AI-healing experimenty."
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
    </div>
</template>
