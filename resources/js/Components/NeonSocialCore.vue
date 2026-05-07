<script setup>

defineProps({
    isOpened: {
        type: Boolean,
        default: false
    }
});
</script>

<template>
    <div class="absolute inset-0 transition-all duration-[1200ms] delay-700 ease-out"
        :class="[isOpened ? 'opacity-100 scale-100 blur-0' : 'opacity-0 scale-95 blur-md']">

        <NeonNav />

        <!-- LEVÝ PANEL -->
        <NeonSocialActions />

        <main class="max-w-6xl mx-auto px-6 pt-40 text-center">
            <h2 class="text-7xl font-black text-white mb-6 tracking-tighter uppercase">
                System Fully Operational
            </h2>
            <p
                class="text-slate-500 text-xl font-light italic leading-relaxed max-w-2xl mx-auto uppercase tracking-widest opacity-80">
                "Precision engineering with a touch of neon violet."
            </p>
        </main>

        <!-- PRAVÝ PANEL -->
        <NeonTechDashboard />
    </div>
</template>
