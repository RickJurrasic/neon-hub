<template>
    <!-- Použijeme max-w-2xl, aby to v tom velkém prostoru vypadalo jako terminál -->
    <div class="w-full max-w-2xl bg-black/60 border border-cyan-500/20 p-8 font-mono">
        <div class="flex justify-between mb-8 border-b border-cyan-500/10 pb-2">
            <span class="text-cyan-400 text-xs">>> INBOX_DECRYPTED</span>
            <button @click="$emit('back')"
                class="text-[10px] hover:text-white text-cyan-700 underline">CLOSE_SESSION</button>
        </div>

        <div class="space-y-6">
            <div class="border-l-2 border-fuchsia-600 pl-4 bg-fuchsia-600/5 py-2">
                <div class="text-[9px] text-fuchsia-500">FROM: RECRUITER_SYS</div>
                <div class="text-white text-sm">"Tohle SPA rozhraní je fakt plynulý. Máte tam i ten Depth Zoom?"</div>
            </div>
        </div>
    </div>
</template>
