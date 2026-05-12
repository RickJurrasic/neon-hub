<script setup>

defineProps({
    isOpened: {
        type: Boolean,
        default: false
    }
});
</script>

<template>
    <div class="fixed inset-0 flex overflow-hidden pointer-events-none z-[100]">

        <div class="gate-wing w-1/2 h-full bg-[#03060f] border-r-[1px] border-sky-500/10 transition-transform duration-[1800ms] ease-in-out pointer-events-auto flex items-start justify-end"
            :style="isOpened ? 'transform: translate3d(-100%, 0, 0)' : 'transform: translate3d(0, 0, 0)'">
            <div class="pr-12 mt-[15vh]">
                <h1
                    class="text-7xl md:text-9xl font-black text-white tracking-tighter select-none leading-none opacity-90">
                    NEON
                </h1>
            </div>
        </div>

        <div class="gate-wing w-1/2 h-full bg-[#03060f] border-l-[1px] border-sky-500/10 transition-transform duration-[1800ms] ease-in-out pointer-events-auto flex items-start justify-start"
            :style="isOpened ? 'transform: translate3d(100%, 0, 0)' : 'transform: translate3d(0, 0, 0)'">
            <div class="pl-12 mt-[15vh]">
                <h1
                    class="text-7xl md:text-9xl font-black text-sky-400 tracking-tighter select-none leading-none opacity-90 uppercase">
                    HUB
                </h1>
            </div>
        </div>
    </div>
</template>

<style scoped>
.gate-wing {
    /* Tyhle vlastnosti říkají prohlížeči: "Tohle je samostatná vrstva, neřeš nic okolo" */
    will-change: transform;
    backface-visibility: hidden;
    contain: strict;
}
</style>
