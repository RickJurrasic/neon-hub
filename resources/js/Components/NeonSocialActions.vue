<script setup>
import { UserPlus } from '@lucide/vue';
</script>

<template>
    <aside class="fixed left-8 top-1/2 -translate-y-1/2 z-[150]">
        <!-- MASTER WRAPPER (75% výšky) -->
        <div class="relative h-[75vh] w-20 overflow-hidden rounded-[2.4rem] p-[1.5px]">

            <!-- ROTUJÍCÍ BORDER (Identický s kartou) -->
            <div class="rotating-border"></div>

            <!-- VNITŘNÍ SKLO (Obsah) -->
            <div
                class="glass-background relative h-full w-full bg-[#050914]/95 backdrop-blur-[40px] rounded-[2.35rem] flex flex-col items-center py-10">

                <!-- Dekorační horní linka -->
                <h2
                    class="text-purple-400 font-bold text-[7px] uppercase tracking-[0.5em] mb-8 opacity-50 vertical-text">
                    Neon_Hub
                </h2>

                <!-- IKONY -->
                <div class="flex-1 flex flex-col justify-around w-full items-center">
                    <div class="action-item">
                        <span class="label">FRIENDS</span>
                        <div class="icon-style">
                            <UserPlus />
                        </div>
                    </div>

                    <div class="action-item">
                        <span class="label">UPLINK</span>
                        <div class="icon-style">💬</div>
                    </div>

                    <div class="action-item">
                        <span class="label">ALERTS</span>
                        <div class="icon-style">🔔</div>
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
/* ROTUJÍCÍ BORDER - PŘESNĚ PODLE TVÉHO MANIFESTU */
.rotating-border {
    position: absolute;
    /* Musí být obří čtverec, aby při rotaci kolem 75vh obdélníku neprosvítaly rohy */
    top: -150%;
    left: -400%;
    width: 900%;
    height: 400%;
    background: conic-gradient(from 0deg,
            transparent 0%,
            rgba(194, 49, 162, 0.3) 25%,
            rgba(56, 189, 248, 0.6) 50%,
            rgba(192, 43, 155, 1) 75%,
            transparent 100%);
    animation: rotate 8s linear infinite;
}

@keyframes rotate {
    from {
        transform: rotate(0deg);
    }

    to {
        transform: rotate(360deg);
    }
}

/* STYLY PRO OBSAH */
.action-item {
    @apply relative flex flex-col items-center cursor-pointer transition-all duration-500 hover:scale-125;
}

.label {
    @apply text-[8px] tracking-[0.2em] font-mono text-white/30 mb-1;
}

.icon-style {
    @apply text-3xl transition-all duration-500 text-white/20;
    filter: drop-shadow(0 0 8px rgba(255, 255, 255, 0.05));
    backdrop-filter: blur(2px);
}

.action-item:hover .icon-style {
    @apply text-white/90;
    filter: drop-shadow(0 0 15px rgba(56, 189, 248, 0.5));
}

.vertical-text {
    writing-mode: vertical-rl;
    text-orientation: mixed;
}

/* Badge (pokud ho tam pořád chceš) */
.badge {
    @apply absolute -top-1 -right-2 w-5 h-5 flex items-center justify-center rounded-full text-[10px] font-black text-white bg-sky-500/80;
}

.glass-background {
    /*
       Klíčem je mít pozadí (background-color) úplně černé/tmavé
       a gradienty nechat jen jako "plovoucí výboje" s průhledností.
    */
    background-color: #050914;
    background-image:
        /* Výboj 1: Modrý mrak, který mizí do ztracena */
        radial-gradient(circle at var(--pos-1, 30% 40%),
            rgba(56, 189, 248, 0.05) 0%,
            rgba(56, 189, 248, 0) 25%,
            transparent 50%),
        /* Výboj 2: Fialový mrak s jiným těžištěm */
        radial-gradient(circle at var(--pos-2, 70% 80%),
            rgba(194, 49, 162, 0.05) 0%,
            rgba(194, 49, 162, 0) 30%,
            transparent 60%),
        /* Výboj 3: Temnější hluboká modř pro hloubku */
        radial-gradient(circle at var(--pos-3, 50% 50%),
            rgba(30, 27, 75, 0.05) 0%,
            transparent 70%);

    background-size: 800% 800%;
    animation: liquid-void 10s ease-in-out infinite;
    position: relative;
    overflow: hidden;
}

/* Opět ten tvůj oblíbený grain, ten nesmí chybět */
.glass-background::after {
    content: "";
    position: absolute;
    inset: 0;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.8' numOctaves='2'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
    opacity: 0.04;
    mix-blend-mode: soft-light;
    pointer-events: none;
}

@keyframes liquid-void {
    0% {
        background-position: 0% 10%;
        filter: hue-rotate(0deg);
    }

    50% {
        background-position: 100% 90%;
        /* Jemná změna odstínu v průběhu času dodá organiku */
        filter: hue-rotate(10deg);
    }

    100% {
        background-position: 20% 10%;
        filter: hue-rotate(-10deg);
    }
}
</style>
