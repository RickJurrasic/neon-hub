<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Neon Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-2xl text-cyan-400 leading-tight uppercase tracking-[0.2em] drop-shadow-[0_0_10px_rgba(34,211,238,0.8)]">
                Neon Hub Console
            </h2>
        </template>

        <div class="py-12 bg-slate-950 min-h-screen">
            <div class="max-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- HLAVNÍ KARTA S NEONOVÝM OKRAJEM -->
                <div class="overflow-hidden bg-black border-2 border-cyan-500/50 shadow-[0_0_20px_rgba(6,182,212,0.3)] sm:rounded-xl">
                    <div class="p-8">
                        <div class="flex items-center justify-between">
                            <div class="text-gray-100 font-mono">
                                <p class="text-cyan-400 mb-2">>> SYSTEM_READY</p>
                                <h3 class="text-3xl font-black italic">
                                    VÍTEJ V <span class="text-transparent bg-clip-text bg-gradient-to-r from-cyan-400 to-purple-500">NEONOVÉM REBOOTU</span>
                                </h3>
                                <p class="mt-4 text-gray-400 max-w-md">
                                    Právě jsi překonal Windows, OneDrive i PHP 8.5. Tohle není jen web, tohle je tvůj nový operační systém pro úspěch.
                                </p>
                            </div>

                            <!-- PULZUJÍCÍ TEČKA (Indikátor aktivity) -->
                            <div class="hidden md:block">
                                <div class="relative flex h-20 w-20">
                                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-cyan-400 opacity-75"></span>
                                  <span class="relative inline-flex rounded-full h-20 w-20 bg-cyan-500 shadow-[0_0_30px_rgba(6,182,212,1)]"></span>
                                </div>
                            </div>
                        </div>

                        <!-- TLAČÍTKO S EFEKTEM -->
                        <div class="mt-10">
                            <button class="px-8 py-3 bg-transparent border border-purple-500 text-purple-400 font-bold rounded-lg hover:bg-purple-500 hover:text-white hover:shadow-[0_0_25px_rgba(168,85,247,0.6)] transition-all duration-300">
                                SPUSTIT REVENGE PROTOKOL
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
