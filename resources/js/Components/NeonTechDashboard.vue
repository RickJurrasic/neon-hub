<script setup>
import { computed } from 'vue';
import { storeToRefs } from 'pinia';
import { Cpu, ShieldAlert } from '@lucide/vue';
import { useNotificationStore } from '@/Stores/useNotificationStore';

const props = defineProps({ isOpened: Boolean, mode: String });
const store = useNotificationStore();

// Tady si vytáhneme reaktivní referenci na 'alerts'
const { alerts } = storeToRefs(store);

const isAlertMode = computed(() => props.mode === 'alerts');
</script>

<template>
    <aside class="xl:fixed xl:right-8 xl:top-1/2 xl:-translate-y-1/2 xl:z-50 flex items-center justify-center">
        <div class="neon-panel-wrapper xl:h-[85vh] h-full xl:w-72 w-full max-w-none rounded-[3rem]">
            <div class="neon-border-active"
                :class="{ '!border-rose-500/50 shadow-[0_0_20px_rgba(244,63,94,0.3)]': isAlertMode }"></div>
            <div class="neon-glass-core rounded-[3rem] py-12 flex flex-col h-full overflow-hidden relative">
                <div class="flex items-center gap-3 border-b border-white/5 pb-4 mb-6 w-full px-6">
                    <component :is="isAlertMode ? ShieldAlert : Cpu" class="animate-pulse"
                        :class="isAlertMode ? 'text-rose-500' : 'text-sky-400'" :size="18" />
                    <h3 class="text-[10px] font-black uppercase tracking-[0.3em]"
                        :class="isAlertMode ? 'text-rose-400' : 'text-white/70'">
                        {{ isAlertMode ? 'Alert_Center' : 'Core_Engine_v.13' }}
                    </h3>
                </div>
                <div class="flex-1 w-full px-6 overflow-hidden relative">
                    <transition name="fade-slide" mode="out-in">
                        <div v-if="!isAlertMode" key="diag" class="flex flex-col h-full space-y-8">
                        </div>
                        <div v-else key="alerts" class="flex flex-col h-full">
                            <div class="space-y-3 overflow-y-auto pr-1 custom-scroll">
                                <div v-for="notif in alerts" :key="notif.id"
                                    class="p-3 border border-white/5 bg-white/5 rounded-xl">
                                    <div class="flex justify-between mb-1">
                                        <span class="text-[7px] font-black uppercase"
                                            :class="notif.type === 'alert' ? 'text-rose-500' : 'text-sky-500'">{{
                                                notif.title }}</span>
                                        <span class="text-[7px] text-white/20 font-mono">{{ notif.time }}</span>
                                    </div>
                                    <p class="text-[9px] text-white/60">{{ notif.msg }}</p>
                                </div>
                            </div>
                        </div>
                    </transition>
                </div>
            </div>
        </div>
    </aside>
</template>

<style scoped>
.fade-slide-enter-active,
.fade-slide-leave-active {
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.fade-slide-enter-from {
    opacity: 0;
    transform: translateX(20px);
}

.fade-slide-leave-to {
    opacity: 0;
    transform: translateX(-20px);
}

.custom-scroll::-webkit-scrollbar {
    width: 2px;
}

.custom-scroll::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 10px;
}

.neon-panel-wrapper {
    transition: all 0.5s ease;
}
</style>
