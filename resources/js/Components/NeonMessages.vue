<template>
    <div
        class="w-[82vw] md:w-full md:max-w-2xl bg-black/60 border border-cyan-500/20 p-5 md:p-8 font-mono rounded-[2rem] flex flex-col max-h-[75vh] overflow-hidden">

        <div class="flex justify-between mb-8 border-b border-cyan-500/10 pb-2 shrink-0">
            <span class="text-cyan-400 text-xs">>> INBOX_DECRYPTED</span>
            <button @click="$emit('back')"
                class="text-[10px] hover:text-white text-cyan-700 underline">CLOSE_SESSION</button>
        </div>

        <div class="space-y-6 overflow-y-auto no-scrollbar grow pr-1">
            <div class="border-l-2 border-fuchsia-600 pl-4 bg-fuchsia-600/5 py-2">
                <div class="text-[9px] text-fuchsia-500">FROM: RECRUITER_SYS</div>
                <div class="text-white text-sm">"Tohle SPA rozhraní je fakt plynulý. Máte tam i ten Depth Zoom?"</div>
            </div>

        </div>
    </div>
</template>

<style scoped>
/* Přidej na konec komponent */
.no-scrollbar::-webkit-scrollbar {
    width: 2px;
}

.no-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(168, 85, 247, 0.2);
    /* Jemná fialová/kyanová podle komponenty */
    border-radius: 10px;
}
</style>
