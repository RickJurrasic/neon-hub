<script setup>

defineProps({
    isOpened: {
        type: Boolean,
        default: false
    }
});
</script>

<template>
    <!-- Změněno na fixed, aby to neuhýbalo ani o pixel -->
    <div class="fixed inset-0 flex overflow-hidden pointer-events-none">

        <!-- LEVÉ KŘÍDLO (NEON) -->
        <div class="w-1/2 h-full bg-[#03060f] border-r-[1px] border-sky-500/10 transition-transform duration-[1800ms] ease-in-out pointer-events-auto flex items-start justify-end"
            :style="isOpened ? 'transform: translateX(-100%)' : 'transform: translateX(0)'">
            <div class="pr-12 mt-[15vh]">
                <h1
                    class="text-7xl md:text-9xl font-black text-white tracking-tighter select-none leading-none opacity-90">
                    NEON
                </h1>
            </div>
        </div>

        <!-- PRAVÉ KŘÍDLO (HUB) -->
        <!-- OPRAVA: změněno border-r na border-l -->
        <div class="w-1/2 h-full bg-[#03060f] border-l-[1px] border-sky-500/10 transition-transform duration-[1800ms] ease-in-out pointer-events-auto flex items-start justify-start"
            :style="isOpened ? 'transform: translateX(100%)' : 'transform: translateX(0)'">
            <div class="pl-12 mt-[15vh]">
                <h1
                    class="text-7xl md:text-9xl font-black text-sky-400 tracking-tighter select-none leading-none opacity-90 uppercase">
                    HUB
                </h1>
            </div>
        </div>
    </div>
</template>
