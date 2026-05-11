<script setup>
import { UserPlus, MessageSquareCode, BellDot } from '@lucide/vue';
</script>

<template>
    <aside class="fixed left-8 top-1/2 -translate-y-1/2 z-50">
        <!-- MASTER WRAPPER využívající globální třídu -->
        <div class="neon-panel-wrapper h-[75vh] w-40 rounded-[8rem]">

            <!-- ROTUJÍCÍ BORDER (Globální třída) -->
            <div class="neon-border-active"></div>

            <!-- VNITŘNÍ SKLO (Globální třída) -->
            <div class="neon-glass-core rounded-[7.8rem] py-16 flex flex-col items-center justify-between h-full">

                <!-- Dekorační horní linka -->
                <h2
                    class="text-purple-400 font-bold text-[7px] uppercase tracking-[0.5em] mb-8 opacity-50 vertical-text">
                    Neon_Hub
                </h2>

                <!-- IKONY -->
                <div class="flex-1 h-full flex flex-col justify-around w-full items-center relative">

                    <!-- FRIENDS -->
                    <div class="action-item group">
                        <span class="label">FRIENDS</span>
                        <div class="icon-style">
                            <UserPlus :size="32" :stroke-width="1.5" />
                        </div>
                    </div>

                    <!-- UPLINK -->
                    <div class="action-item group">
                        <span class="label">MESSAGES</span>
                        <div class="icon-style">
                            <MessageSquareCode :size="32" :stroke-width="1.5" />
                        </div>
                    </div>

                    <!-- ALERTS -->
                    <div class="action-item group">
                        <span class="label">ALERTS</span>
                        <div class="icon-style relative">
                            <BellDot :size="32" :stroke-width="1.5" />
                            <!-- Aktivní alert puntík -->
                            <div
                                class="absolute -top-0.5 -right-0.5 w-2 h-2 bg-rose-500 rounded-full animate-pulse shadow-[0_0_10px_#f43f5e]">
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Verze dole -->
                <div class="mt-8 opacity-20 font-mono text-[8px] text-sky-500">
                    v.2.6
                </div>
            </div>
        </div>
    </aside>
</template>

<style scoped>
/* Pouze specifické styly pro tuto komponentu */
.vertical-text {
    writing-mode: vertical-rl;
    text-orientation: mixed;
}

.action-item {
    @apply relative flex flex-col items-center cursor-pointer transition-all duration-500;
    will-change: transform;
}

.action-item:hover {
    transform: scale(1.15);
}

.label {
    @apply text-[7px] tracking-[0.3em] font-mono text-sky-400/50 mb-2 uppercase transition-all duration-500;
    text-shadow: 0 0 5px rgba(56, 189, 248, 0.2);
}

.icon-style {
    @apply transition-all duration-500 text-sky-200/30;
    filter: drop-shadow(0 0 2px rgba(56, 189, 248, 0.1));
}

/* Hover efekty využívající vnoření */
.action-item:hover .icon-style {
    @apply text-white;
    filter: drop-shadow(0 0 10px rgba(56, 189, 248, 0.8)) drop-shadow(0 0 20px rgba(194, 49, 162, 0.4));
    transform: translateY(-2px);
}

.action-item:hover .label {
    @apply text-sky-300 opacity-100;
    text-shadow: 0 0 8px rgba(56, 189, 248, 0.5);
}
</style>
