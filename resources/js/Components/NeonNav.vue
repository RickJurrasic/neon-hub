<script setup>
import { Cpu, FileUser } from 'lucide-vue-next';

defineEmits(['open-cv']);
</script>

<template>
    <header class="fixed top-0 left-0 w-full z-[60]">
        <div class="neon-panel-wrapper nav-wrapper h-16 border-b border-white/5">

            <div class="nav-light-scanner"></div>

            <div class="neon-glass-core h-full !flex-row items-center justify-between px-4 md:px-12 bg-[#050914]/95">

                <div class="flex items-center gap-2 md:gap-3 md:w-64 shrink-0">
                    <div class="relative group cursor-pointer">
                        <div
                            class="absolute -inset-2 bg-sky-500/20 blur-md rounded-full opacity-0 group-hover:opacity-100 transition-opacity">
                        </div>
                        <Cpu :size="20" class="text-sky-400 relative md:scale-110" />
                    </div>
                    <span class="font-black text-lg md:text-xl uppercase tracking-tighter text-white select-none">
                        Neon<span class="text-sky-400">Hub</span>
                    </span>
                </div>

                <div class="flex flex-1 justify-center items-center px-1 md:px-10 lg:px-20 min-w-0">

                    <div class="hidden md:flex items-center gap-3.5 bg-slate-950/80 border border-sky-500/30 px-5 py-1.5 rounded-full shadow-[0_0_15px_rgba(56,189,248,0.12)] hover:border-fuchsia-500/60 hover:shadow-[0_0_25px_rgba(192,43,155,0.3)] transition-all duration-300 cursor-pointer group active:scale-98"
                        @click="$emit('open-cv')">
                        <div class="flex items-center gap-2.5">
                            <span class="relative flex h-1.5 w-1.5 shrink-0">
                                <span
                                    class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500"></span>
                            </span>

                            <span
                                class="text-[9px] font-mono text-sky-400/60 uppercase tracking-wider group-hover:text-fuchsia-400/70 transition-colors">System
                                Author:</span>
                            <span
                                class="text-xs font-semibold text-sky-100 group-hover:text-white transition-colors tracking-wide">Erik
                                Sternad</span>
                        </div>

                        <div class="h-3.5 w-[1px] bg-sky-500/20 group-hover:bg-fuchsia-500/30 transition-colors"></div>

                        <span
                            class="text-[10px] font-mono font-bold text-sky-400 group-hover:text-fuchsia-400 transition-colors uppercase tracking-wide">
                            [ View CV ]
                        </span>
                    </div>

                    <button
                        class="flex md:hidden items-center gap-1.5 bg-slate-950/90 border border-sky-500/40 px-2.5 py-1 rounded-full shadow-[0_0_10px_rgba(56,189,248,0.15)] active:scale-95 transition-all cursor-pointer text-white"
                        @click="$emit('open-cv')">
                        <FileUser :size="11" class="text-sky-400" />
                        <span class="text-[9px] font-mono font-bold tracking-wider uppercase">CV</span>
                        <span class="relative flex h-1.5 w-1.5 ml-0.5">
                            <span
                                class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500"></span>
                        </span>
                    </button>

                </div>

                <div
                    class="flex items-center justify-end gap-2 md:gap-4 md:w-64 max-w-[140px] xs:max-w-[180px] sm:max-w-none shrink-0">

                    <div class="flex flex-col items-end min-w-0">
                        <span
                            class="text-[9px] font-black text-white/90 tracking-tighter uppercase truncate w-full text-right">
                            {{ $page.props.auth?.user?.name || 'Admin_Root' }}
                        </span>
                        <span
                            class="text-[7px] font-mono text-sky-500/70 tracking-widest italic truncate w-full text-right">
                            {{ $page.props.auth?.user?.handle || '0x882_ALPHA' }}
                        </span>
                    </div>

                    <div class="relative group shrink-0">
                        <div
                            class="absolute -inset-0.5 bg-gradient-to-tr from-sky-500 to-fuchsia-500 rounded-full blur-xs opacity-40 group-hover:opacity-100 transition-opacity">
                        </div>

                        <div
                            class="relative w-8 h-8 md:w-9 md:h-9 rounded-full bg-gradient-to-tr from-fuchsia-600 to-sky-400 p-[1px]">
                            <div class="w-full h-full rounded-full bg-[#050914] flex items-center justify-center">
                                <span class="text-[9px] md:text-[10px] font-bold text-white tracking-wider">
                                    {{$page.props.auth?.user?.name ? $page.props.auth.user.name.split(' ').map(n =>
                                        n[0]).join('').substring(0, 2).toUpperCase() : 'RH'}}
                                </span>
                            </div>
                        </div>

                        <div v-if="$page.props.auth?.user?.faction"
                            class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full border border-[#050914] bg-amber-400 shadow-[0_0_6px_#f59e0b]">
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </header>
</template>

<style scoped>
/* Styly zůstávají beze změny */
.nav-wrapper {
    padding: 0;
    position: relative;
    background: transparent;
}

.nav-light-scanner {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    height: 1px;
    transform: translateY(-50%);
    background: linear-gradient(90deg,
            rgba(56, 189, 248, 1) 0%,
            rgba(192, 43, 155, 1) 25%,
            rgba(56, 189, 248, 1) 50%,
            rgba(192, 43, 155, 1) 75%,
            rgba(56, 189, 248, 1) 100%);
    background-size: 200% 100%;
    animation: liquid-line 8s linear infinite;
    pointer-events: none;
    box-shadow: 0 0 12px rgba(56, 189, 248, 0.5);
    z-index: 70;
}

@keyframes liquid-line {
    from {
        background-position: 0% 0;
    }

    to {
        background-position: 200% 0;
    }
}

.neon-glass-core {
    @apply border-b border-white/5;
}
</style>
