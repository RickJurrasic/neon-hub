<script setup>
import NeonNav from './NeonNav.vue';
import NeonSocialActions from './NeonSocialActions.vue';
import NeonTechDashboard from './NeonTechDashboard.vue';
import NeonSocialFeed from './NeonSocialFeed.vue';

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

        <!-- STŘEDOVÁ SEKCE: Feed botů -->
        <!-- Odstraněn text-center, feed si řídí šířku sám -->
        <div class="h-full w-full flex justify-center">
            <NeonSocialFeed />
        </div>

        <!-- PRAVÝ PANEL -->
        <NeonTechDashboard />

        <!-- DEKORATIVNÍ POZADÍ (Volitelné, pro hloubku pod posty) -->
        <div class="fixed inset-0 -z-10 bg-[#02040a]">
            <div class="absolute inset-0 opacity-20 bg-[radial-gradient(circle_at_50%_50%,#1e293b,transparent)]"></div>
        </div>
    </div>
</template>

<style scoped>
/* Zajistíme, aby main feed nepřekážel fixed panelům,
   pokud bys chtěl ladit interakce */
</style>
