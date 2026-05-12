<script setup>
import { ref, watch, onUnmounted, computed } from 'vue';
import { Cpu, Activity, Bell, ShieldAlert, ChevronLeft } from '@lucide/vue';

const props = defineProps({
    isOpened: Boolean,
    mode: String // Místo activeTab přijímá 'stats' nebo 'alerts'
});

const bars = ref(Array(15).fill(40));
const notifications = ref([
    { id: 1, type: 'alert', title: 'SECURITY_BREACH', msg: 'Unauthorized uplink from sector 7G.', time: '14:20' },
    { id: 2, type: 'info', title: 'SYSTEM_SYNC', msg: 'Neural networks are 100% stable.', time: '12:05' },
    { id: 3, type: 'alert', title: 'DATA_ENCRYPTION', msg: 'Incoming packet needs decryption.', time: '09:45' }
]);

// Je dashboard v režimu "seznam notifikací"?
const isAlertMode = computed(() => props.mode === 'alerts');

let intervalId = null;

const startSimulation = () => {
    if (intervalId) return;
    intervalId = setInterval(() => {
        bars.value = bars.value.map(() => Math.floor(Math.random() * 80) + 20);
    }, 2000);
};

watch(() => props.isOpened, (newVal) => {
    if (newVal) setTimeout(startSimulation, 1500);
}, { immediate: false });

onUnmounted(() => {
    if (intervalId) clearInterval(intervalId);
});
</script>

<template>
    <aside class="fixed right-8 top-1/2 -translate-y-1/2 z-50">
        <div class="neon-panel-wrapper h-[85vh] w-72 rounded-[3rem]">
            <div class="neon-border-active"
                :class="{ '!border-rose-500/50 shadow-[0_0_20px_rgba(244,63,94,0.3)]': isAlertMode }"></div>

            <div class="neon-glass-core rounded-[3rem] py-12 flex flex-col h-full overflow-hidden relative">

                <!-- HEADER (DYNAMICKÝ) -->
                <div
                    class="flex items-center gap-3 border-b border-white/5 pb-4 mb-6 w-full px-6 transition-all duration-500">
                    <component :is="isAlertMode ? ShieldAlert : Cpu"
                        class="animate-pulse transition-colors duration-500"
                        :class="isAlertMode ? 'text-rose-500' : 'text-sky-400'" :size="18" />
                    <h3 class="text-[10px] font-black uppercase tracking-[0.3em] transition-colors"
                        :class="isAlertMode ? 'text-rose-400' : 'text-white/70'">
                        {{ isAlertMode ? 'Alert_Center' : 'Core_Engine_v.13' }}
                    </h3>
                </div>

                <!-- HLAVNÍ OBSAH S TRANSITION -->
                <div class="flex-1 w-full px-6 overflow-hidden relative">
                    <transition name="fade-slide" mode="out-in">

                        <!-- REŽIM DIAGNOSTIKA (Default) -->
                        <div v-if="!isAlertMode" key="diag" class="flex flex-col h-full space-y-8">
                            <!-- Observer Logs -->
                            <div>
                                <div
                                    class="flex items-center justify-between mb-3 text-[8px] font-bold uppercase tracking-widest text-slate-500">
                                    <span>Observer_Logs</span>
                                    <span class="text-emerald-500 animate-pulse">Live</span>
                                </div>
                                <div
                                    class="space-y-2 font-mono text-[9px] bg-black/20 p-3 rounded-xl border border-white/5">
                                    <div class="flex gap-2 text-slate-300"><span class="text-emerald-500/50">01</span>
                                        [OK] Injecting feed_v2</div>
                                    <div class="flex gap-2 text-slate-300"><span class="text-sky-500/50">02</span> [OK]
                                        Auth_Gateway</div>
                                    <div class="flex gap-2 text-purple-400 animate-pulse"><span
                                            class="text-purple-500/50">03</span> Analyzing...</div>
                                </div>
                            </div>

                            <!-- Neural Load -->
                            <div>
                                <div
                                    class="flex items-center gap-2 mb-3 text-[8px] font-bold uppercase tracking-widest text-slate-500">
                                    <Activity :size="14" class="text-fuchsia-500" />
                                    <span>Neural_Load</span>
                                </div>
                                <div
                                    class="h-16 flex items-end gap-1.5 px-2 bg-black/10 rounded-lg py-1 border border-white/5 overflow-hidden">
                                    <div v-for="(height, i) in bars" :key="i"
                                        class="flex-1 bg-gradient-to-t from-fuchsia-600/40 to-sky-400/60 rounded-full transition-all duration-[2000ms]"
                                        :style="{ height: height + '%' }">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- REŽIM NOTIFIKACE (Po kliku na Alerts) -->
                        <div v-else key="alerts" class="flex flex-col h-full">
                            <div class="space-y-3 overflow-y-auto pr-1 custom-scroll">
                                <div v-for="notif in notifications" :key="notif.id"
                                    class="group p-3 border border-white/5 bg-white/5 hover:bg-rose-500/10 hover:border-rose-500/30 rounded-xl transition-all duration-300 cursor-pointer">
                                    <div class="flex justify-between items-start mb-1">
                                        <span class="text-[7px] font-black uppercase tracking-tighter"
                                            :class="notif.type === 'alert' ? 'text-rose-500' : 'text-sky-500'">
                                            {{ notif.title }}
                                        </span>
                                        <span class="text-[7px] text-white/20 font-mono">{{ notif.time }}</span>
                                    </div>
                                    <p class="text-[9px] text-white/60 leading-tight group-hover:text-white/90">{{
                                        notif.msg }}</p>
                                </div>
                            </div>

                            <!-- Nápověda pro návrat -->
                            <div class="mt-auto pt-4 text-center">
                                <p class="text-[7px] text-slate-600 font-mono animate-bounce uppercase">
                                    Click Home to return to Diag
                                </p>
                            </div>
                        </div>
                    </transition>
                </div>

                <!-- FOOTER -->
                <div
                    class="mt-6 pt-4 border-t border-white/5 w-full px-6 flex justify-between items-center opacity-40 text-[8px] font-mono text-slate-500">
                    <div class="flex gap-1">
                        <div class="w-1.5 h-1.5 rounded-full"
                            :class="isAlertMode ? 'bg-rose-500 animate-ping' : 'bg-sky-500 animate-ping'"></div>
                        <div class="w-1.5 h-1.5 rounded-full" :class="isAlertMode ? 'bg-rose-500' : 'bg-sky-500'"></div>
                    </div>
                    <span>{{ isAlertMode ? 'SECURITY_MODE' : 'SYSTEM_IDLE' }}</span>
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
