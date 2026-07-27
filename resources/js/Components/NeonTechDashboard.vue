<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { storeToRefs } from 'pinia';
import { Cpu, ShieldAlert, Terminal, Activity, Database, Server } from '@lucide/vue';
import { useNotificationStore } from '@/Stores/useNotificationStore';
import axios from 'axios';

const props = defineProps({ isOpened: Boolean, mode: String });
const store = useNotificationStore();

// Reaktivní reference z Pinia pro Alert sekci
const { alerts } = storeToRefs(store);

const isAlertMode = computed(() => props.mode === 'alerts');

// --- TELEMETRIE (LARAVEL PULSE & AGENT STREAM) ---
const telemetry = ref(null);
const isLoading = ref(true);
let pollingInterval = null;

const fetchTelemetry = async () => {
    try {
        const response = await axios.get('/api/core-engine/telemetry');
        telemetry.value = response.data;
    } catch (error) {
        console.error('Core Engine telemetry link broken:', error);
    } finally {
        isLoading.value = false;
    }
};

onMounted(() => {
    fetchTelemetry();
    // Polling: Každých 5 sekund stáhneme čerstvý stav ze serveru
    pollingInterval = setInterval(fetchTelemetry, 5000);
});

onUnmounted(() => {
    if (pollingInterval) clearInterval(pollingInterval);
});
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
                        {{ isAlertMode ? 'Alert_Center' : 'Core_Engine_v.1.3' }}
                    </h3>
                </div>

                <div class="flex-1 w-full px-6 overflow-hidden relative">
                    <transition name="fade-slide" mode="out-in">

                        <div v-if="!isAlertMode" key="diag" class="flex flex-col h-full space-y-6 overflow-hidden">

                            <div class="flex flex-col flex-1 min-h-[50%] overflow-hidden">
                                <div class="flex items-center gap-2 mb-2 text-white/40">
                                    <Terminal :size="12" class="text-sky-500" />
                                    <span
                                        class="text-[8px] font-black uppercase tracking-wider">Agent_Activity_Stream</span>
                                </div>

                                <div
                                    class="flex-1 bg-black/40 border border-white/5 rounded-xl p-3 overflow-y-auto custom-scroll font-mono text-[9px] space-y-2">
                                    <div v-if="telemetry" v-for="(log, idx) in telemetry.activity_stream" :key="idx"
                                        class="leading-relaxed">
                                        <span class="text-white/20">[{{ log.timestamp }}]</span>
                                        <span class="ml-1"
                                            :class="log.type === 'warning' ? 'text-amber-400' : 'text-cyan-400'">{{
                                            log.system }}:</span>
                                        <span class="text-white/70 ml-1">{{ log.message }}</span>
                                    </div>
                                    <div v-else class="text-white/20 animate-pulse">Awaiting secure uplink...</div>
                                </div>
                            </div>

                            <div class="flex flex-col space-y-3">
                                <div class="flex items-center gap-2 text-white/40">
                                    <Activity :size="12" class="text-sky-500" />
                                    <span
                                        class="text-[8px] font-black uppercase tracking-wider">System_Diagnostics</span>
                                </div>

                                <div v-if="telemetry" class="grid grid-cols-1 gap-2 font-mono text-[9px]">
                                    <div
                                        class="p-2 border border-white/5 bg-white/[0.02] rounded-lg flex justify-between items-center">
                                        <span class="text-white/40 uppercase">Node_Status</span>
                                        <span class="text-emerald-400 font-bold tracking-wider animate-pulse">{{
                                            telemetry.status }}</span>
                                    </div>

                                    <div class="p-2 border border-white/5 bg-white/[0.02] rounded-lg space-y-1">
                                        <div class="flex justify-between items-center text-white/40">
                                            <span class="uppercase">Memory_Usage</span>
                                            <span class="text-yellow-400 font-bold">{{ telemetry.metrics.memory_usage_mb
                                                }} MB</span>
                                        </div>
                                        <div class="w-full bg-white/5 h-1 rounded-full overflow-hidden">
                                            <div class="bg-yellow-400 h-full transition-all duration-500"
                                                :style="{ width: Math.min((telemetry.metrics.memory_usage_mb / 128) * 100, 100) + '%' }">
                                            </div>
                                        </div>
                                    </div>

                                    <div
                                        class="p-2 border border-white/5 bg-white/[0.02] rounded-lg flex justify-between items-center">
                                        <div class="flex items-center gap-1.5">
                                            <Server :size="10" class="text-white/30" />
                                            <span class="text-white/40 uppercase">Pending_Jobs</span>
                                        </div>
                                        <span
                                            :class="telemetry.metrics.pending_jobs > 0 ? 'text-amber-400 font-bold' : 'text-white/30'">
                                            {{ telemetry.metrics.pending_jobs }}
                                        </span>
                                    </div>

                                    <div
                                        class="p-2 border border-white/5 bg-white/[0.02] rounded-lg flex justify-between items-center">
                                        <div class="flex items-center gap-1.5">
                                            <Database :size="10" class="text-white/30" />
                                            <span class="text-white/40 uppercase">Slow_Queries</span>
                                        </div>
                                        <span
                                            :class="telemetry.metrics.slow_queries_detected > 0 ? 'text-rose-500 font-bold' : 'text-emerald-400'">
                                            {{ telemetry.metrics.slow_queries_detected }}
                                        </span>
                                    </div>
                                </div>

                                <div v-else class="space-y-2 animate-pulse">
                                    <div class="h-6 bg-white/5 rounded-lg"></div>
                                    <div class="h-10 bg-white/5 rounded-lg"></div>
                                    <div class="h-6 bg-white/5 rounded-lg"></div>
                                </div>
                            </div>

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
