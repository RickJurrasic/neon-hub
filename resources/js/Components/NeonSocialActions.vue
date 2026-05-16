<script setup>
import { UserPlus, MessageSquareCode, BellDot, Home } from '@lucide/vue';

defineProps({
    activeTab: {
        type: String,
        default: 'feed'
    },
    isMobile: {
        type: Boolean,
        default: false
    }
});

defineEmits(['change-view']);
</script>

<template>
    <component :is="isMobile ? 'div' : 'aside'"
        :class="isMobile ? 'mobile-dock-position z-50 w-[92%] max-w-sm' : 'fixed left-8 top-1/2 -translate-y-1/2 z-50 hidden md:block'">

        <div class="neon-panel-wrapper"
            :class="isMobile ? 'h-16 w-full rounded-full !p-[1px]' : 'h-[75vh] w-40 rounded-[8rem]'">

            <div class="neon-border-active" :class="{ 'mobile-border-override': isMobile }"></div>

            <div class="neon-glass-core" :class="isMobile
                ? '!flex-row !items-center justify-around h-full w-full rounded-full px-4 py-2'
                : 'rounded-[7.8rem] py-16 flex flex-col items-center justify-between h-full'">

                <h2 v-if="!isMobile"
                    class="text-purple-400 font-bold text-[7px] uppercase tracking-[0.5em] mb-8 opacity-50 vertical-text">
                    Neon_Hub
                </h2>

                <div
                    :class="isMobile ? 'flex flex-row w-full h-full justify-around items-center' : 'flex-1 h-full flex flex-col justify-around w-full items-center relative'">

                    <div class="action-item group"
                        :class="{ 'is-active': activeTab === 'feed', 'is-mobile-item': isMobile }"
                        @click="$emit('change-view', 'feed')">
                        <span class="label">SYSTEM</span>
                        <div class="icon-style">
                            <Home :size="isMobile ? 22 : 32" :stroke-width="1.5" />
                        </div>
                    </div>

                    <div class="action-item group"
                        :class="{ 'is-active': activeTab === 'friends' || activeTab === 'profile', 'is-mobile-item': isMobile }"
                        @click="$emit('change-view', 'friends')">
                        <span class="label">FRIENDS</span>
                        <div class="icon-style">
                            <UserPlus :size="isMobile ? 22 : 32" :stroke-width="1.5" />
                        </div>
                    </div>

                    <div class="action-item group"
                        :class="{ 'is-active': activeTab === 'messages', 'is-mobile-item': isMobile }"
                        @click="$emit('change-view', 'messages')">
                        <span class="label">MESSAGES</span>
                        <div class="icon-style">
                            <MessageSquareCode :size="isMobile ? 22 : 32" :stroke-width="1.5" />
                        </div>
                    </div>

                    <div class="action-item group"
                        :class="{ 'is-active': activeTab === 'notifications', 'is-mobile-item': isMobile }"
                        @click="$emit('change-view', 'notifications')">
                        <span class="label">ALERTS</span>
                        <div class="icon-style relative">
                            <BellDot :size="isMobile ? 22 : 32" :stroke-width="1.5" />
                            <div class="absolute bg-rose-500 rounded-full animate-pulse shadow-[0_0_10px_#f43f5e]"
                                :class="isMobile ? '-top-0.5 -right-0.5 w-1.5 h-1.5' : '-top-0.5 -right-0.5 w-2 h-2'">
                            </div>
                        </div>
                    </div>

                </div>

                <div v-if="!isMobile" class="mt-8 opacity-20 font-mono text-[8px] text-sky-500">
                    v.2.6
                </div>
            </div>
        </div>
    </component>
</template>

<style scoped>
/* Responzivní ukotvení doku pro mobilní zobrazení */
.mobile-dock-position {
    position: fixed !important;
    bottom: 24px !important;
    left: 50% !important;
    transform: translateX(-50%) !important;
    width: 92% !important;
    max-width: 360px !important;
    height: 64px !important;
    border-radius: 9999px !important;
    padding: 1px !important;
    z-index: 9999 !important;
}

/* Zkrocení obřího rotujícího borderu na mobilních rozměrech */
:deep(.neon-border-active).mobile-border-override {
    top: -100% !important;
    left: -50% !important;
    width: 200% !important;
    height: 300% !important;
}

/* Reset orby efektů, pokud jsme na mobilu */
.mobile-dock-position :deep(.neon-glass-core)::before,
.mobile-dock-position :deep(.neon-glass-core)::after {
    display: none !important;
}

.vertical-text {
    writing-mode: vertical-rl;
    text-orientation: mixed;
}

/* Společný základ pro akční tlačítka */
.action-item {
    @apply relative flex flex-col items-center cursor-pointer transition-all duration-300;
    will-change: transform;
}

/* Specifické rozvržení tlačítek pro mobil s mezerou mezi ikonou a textem */
.is-mobile-item {
    @apply flex-col-reverse justify-center items-center h-full flex-1;
    gap: 5px !important;
    /* Prostor pro dýchání prvků nad sebou */
    -webkit-tap-highlight-color: transparent;
}

.action-item:hover {
    transform: scale(1.1);
}

.is-mobile-item:active {
    transform: scale(0.95);
}

/* Škálování textu */
.label {
    @apply text-[7px] font-mono text-sky-400/50 uppercase transition-all duration-300 select-none text-center;
    text-shadow: 0 0 5px rgba(56, 189, 248, 0.2);
}

/* Desktop label má spodní margin, mobilní nikoliv díky flex gapu */
.action-item:not(.is-mobile-item) .label {
    @apply mb-2 tracking-[0.3em];
}

/* Mobilní text jemně roztáhneme do šířky, aby ladil s desktop designem */
.is-mobile-item .label {
    @apply mt-0 tracking-[0.15em] text-[7px];
}

.icon-style {
    @apply transition-all duration-300 text-sky-200/30 flex items-center justify-center;
    filter: drop-shadow(0 0 2px rgba(56, 189, 248, 0.1));
}

/* Aktivní / Hover neonová záře sjednocená pro desktop i mobilní simulaci */
.action-item:hover .icon-style,
.action-item.is-active .icon-style {
    @apply text-white;
    filter: drop-shadow(0 0 8px rgba(56, 189, 248, 0.9)) drop-shadow(0 0 16px rgba(194, 49, 162, 0.5));
}

.action-item:hover .label,
.action-item.is-active .label {
    @apply text-sky-300 opacity-100;
    text-shadow: 0 0 8px rgba(56, 189, 248, 0.6);
}

.action-item:hover:not(.is-mobile-item) .icon-style {
    transform: translateY(-2px);
}
</style>
