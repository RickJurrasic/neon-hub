<script setup>
import { ref } from 'vue';
import {
    Briefcase,
    Code2,
    Download,
    Mail,
    CheckCircle2,
    Languages,
    GraduationCap,
    Github,
    Linkedin,
    ExternalLink
} from 'lucide-vue-next';

defineEmits(['back']);

const copied = ref(false);

const copyEmail = () => {
    navigator.clipboard.writeText('sternaderik@gmail.com');
    copied.value = true;
    setTimeout(() => copied.value = false, 2000);
};

// Tvůj reálný ověřený technologický stack
const techStack = [
    { name: 'PHP (OOP & MVC Architecture)', level: 75 },
    { name: 'Vue.js 3 (Composition API)', level: 75 },
    { name: 'TailwindCSS & UI Layouts', level: 85 },
    { name: 'Laravel Framework & Inertia.js', level: 65 },
    { name: 'REST APIs & Legacy SOAP', level: 80 },
    { name: 'Sys Monitoring (Kibana, Grafana)', level: 80 }
];
</script>

<template>
    <div
        class="w-[92vw] max-w-sm md:w-full md:max-w-3xl bg-[#030712]/90 border border-sky-500/30 p-4 md:p-6 font-mono backdrop-blur-2xl shadow-[0_0_50px_rgba(56,189,248,0.15)] relative overflow-y-auto no-scrollbar max-h-[85vh] md:max-h-[75vh] flex flex-col rounded-3xl shrink-0">

        <div
            class="absolute inset-0 bg-gradient-to-b from-transparent via-sky-500/5 to-transparent h-[200%] animate-scan-slow pointer-events-none">
        </div>

        <div class="flex justify-between items-start mb-5 border-b border-sky-500/20 pb-3 relative z-10 shrink-0">
            <div>
                <div class="flex items-center gap-2">
                    <span class="relative flex h-2 w-2">
                        <span
                            class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                    </span>
                    <h2 class="text-sm md:text-lg font-black text-white tracking-[0.2em] uppercase">Core_Author_Profile
                    </h2>
                </div>
                <p class="text-[8px] md:text-[10px] text-sky-400/70 tracking-widest uppercase mt-0.5">
                    Security Clearance: LEVEL_5_CREATOR // STATUS: ACTIVE_DEVELOPER
                </p>
            </div>
            <button @click="$emit('back')"
                class="border border-fuchsia-500/40 px-3 py-1 rounded-full text-[9px] md:text-[10px] text-fuchsia-400 hover:bg-fuchsia-500/20 hover:border-fuchsia-500 transition-all cursor-pointer select-none">
                CLOSE [X]
            </button>
        </div>

        <div class="grow space-y-5 relative z-10 overflow-y-auto no-scrollbar pr-0 md:pr-1">

            <div
                class="grid grid-cols-1 md:grid-cols-4 gap-4 bg-[#050a15]/70 border border-sky-500/15 p-4 rounded-2xl shadow-[inset_0_0_20px_rgba(56,189,248,0.03)]">
                <div class="col-span-1 flex md:flex-col items-center justify-center">
                    <div
                        class="w-16 h-16 md:w-full md:aspect-square bg-gradient-to-tr from-fuchsia-600/20 to-sky-500/20 border border-sky-500/30 flex items-center justify-center relative rounded-xl overflow-hidden group shadow-[inset_0_0_15px_rgba(56,189,248,0.1)]">
                        <span
                            class="text-xl md:text-3xl font-black text-sky-400 group-hover:scale-110 transition-transform select-none">ES</span>
                    </div>
                </div>

                <div class="col-span-1 md:col-span-3 space-y-1.5 text-center md:text-left">
                    <div class="text-white text-base md:text-xl font-bold tracking-wide">Erik Sternad</div>
                    <div class="flex items-center gap-3 justify-center md:justify-start">
                        <div class="text-xs font-semibold text-fuchsia-400 uppercase tracking-wider">Technical Support
                            Specialist & Aspiring Full-Stack Developer</div>
                        <a href="https://github.com/RickJurrasic" target="_blank"
                            class="text-slate-400 hover:text-sky-400">
                            <Github :size="14" />
                        </a>
                        <a href="#" target="_blank" class="text-slate-400 hover:text-sky-400">
                            <Linkedin :size="14" />
                        </a>
                    </div>
                    <p class="text-[11px] text-slate-400 leading-relaxed font-sans md:font-mono pt-1">
                        Technical support specialist with a strong background in fintech, shifting into professional
                        full-stack development. Experienced in REST API debugging, deep log analysis, and e-commerce
                        modules.
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-4 flex flex-col">
                    <div
                        class="space-y-3 bg-[#050a15]/70 border border-sky-500/15 p-4 rounded-2xl flex-1 shadow-[inset_0_0_20px_rgba(56,189,248,0.02)]">
                        <div class="flex items-center gap-2 text-sky-400 border-b border-sky-500/10 pb-1.5">
                            <Code2 :size="14" />
                            <span class="text-[10px] uppercase tracking-wider font-bold">Verified_Tech_Stack</span>
                        </div>
                        <div class="space-y-2.5">
                            <div v-for="tech in techStack" :key="tech.name" class="space-y-1">
                                <div class="flex justify-between text-[10px]">
                                    <span class="text-slate-300">{{ tech.name }}</span>
                                    <span class="text-sky-400/80 font-mono">{{ tech.level }}%</span>
                                </div>
                                <div class="h-1 bg-[#02040a] rounded-full overflow-hidden border border-white/5">
                                    <div class="h-full bg-gradient-to-r from-sky-500 to-fuchsia-500 transition-all duration-1000"
                                        :style="{ width: tech.level + '%' }"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        class="grid grid-cols-1 sm:grid-cols-2 gap-3 bg-[#050a15]/70 border border-sky-500/15 p-4 rounded-2xl shadow-[inset_0_0_20px_rgba(56,189,248,0.02)]">
                        <div class="space-y-2">
                            <div class="flex items-center gap-1.5 text-sky-400 border-b border-sky-500/10 pb-1">
                                <Languages :size="12" />
                                <span class="text-[9px] uppercase tracking-wider font-bold">Languages</span>
                            </div>
                            <div class="space-y-1 text-[10px]">
                                <div class="flex justify-between"><span class="text-slate-300">Czech:</span> <span
                                        class="text-slate-500">Native</span></div>
                                <div class="flex justify-between"><span class="text-slate-300">English:</span> <span
                                        class="text-emerald-400 font-bold">B2-C1</span></div>
                                <div class="flex justify-between"><span class="text-slate-300">German:</span> <span
                                        class="text-emerald-400 font-bold">B2-C1</span></div>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <div class="flex items-center gap-1.5 text-fuchsia-400 border-b border-fuchsia-500/10 pb-1">
                                <GraduationCap :size="12" />
                                <span class="text-[9px] uppercase tracking-wider font-bold">Certificates</span>
                            </div>
                            <div class="space-y-0.5 text-[9px] leading-tight">
                                <div class="font-bold text-slate-300">ITNetwork Retraining</div>
                                <div class="text-fuchsia-400/80">PHP Web Programmer (2024)</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    class="space-y-4 bg-[#050a15]/70 border border-fuchsia-500/15 p-4 rounded-2xl flex flex-col justify-start shadow-[inset_0_0_20px_rgba(217,70,239,0.02)]">
                    <div class="flex items-center gap-2 text-fuchsia-400 border-b border-fuchsia-500/10 pb-1.5">
                        <Briefcase :size="14" />
                        <span class="text-[10px] uppercase tracking-wider font-bold">Deployment_History</span>
                    </div>

                    <div class="relative pl-3 border-l border-fuchsia-500/50 space-y-1">
                        <div class="text-[11px] font-bold text-white tracking-wide">Neon-Hub (Core Portfolio App)</div>
                        <div class="text-[9px] text-fuchsia-400 font-bold">
                            Laravel 13 / Vue 3 / Reverb WebSockets / AI Multi-Agent Pipeline
                        </div>
                        <p class="text-[10px] text-slate-400 font-sans md:font-mono leading-relaxed">
                            Architected a high-fidelity reactive ecosystem on Laravel 13 & Vue 3. Engineered an advanced
                            multi-agent
                            AI pipeline with automated Gemini/Groq fallback strategies running on asynchronous queue
                            workers.
                            Implemented low-latency real-time event broadcasting via Reverb/Echo and built a custom
                            system
                            telemetry engine mirroring Laravel Pulse architecture. Fully secured with a robust Pest PHP
                            TDD suite.
                        </p>
                    </div>

                    <div class="relative pl-3 border-l border-sky-500/40 space-y-1">
                        <div class="text-[11px] font-bold text-white tracking-wide">Technical Support Specialist</div>
                        <div class="text-[9px] text-sky-400 font-bold">GoPay / Fintech Branch (03/2024 - Present)</div>
                        <p class="text-[10px] text-slate-400 font-sans md:font-mono leading-relaxed">
                            Debugging and integration troubleshooting of merchant payment systems using REST APIs and
                            legacy SOAP. Investigating core application failures and system monitoring via Kibana,
                            Grafana, and OpenSearch. Contributing to OpenSource module improvements (WooCommerce,
                            PrestaShop).
                        </p>
                    </div>

                    <div class="relative pl-3 border-l border-sky-500/40 space-y-1">
                        <div class="text-[11px] font-bold text-white tracking-wide flex justify-between">
                            Auto Saloon
                            <a href="https://github.com/RickJurrasic/auto-saloon" target="_blank">
                                <ExternalLink :size="10" class="text-sky-400" />
                            </a>
                        </div>
                        <div class="text-[9px] text-sky-400 font-bold">Laravel & Inertia.js / Vue 3 SSR / GoPay Fintech
                            API</div>
                        <p class="text-[10px] text-slate-400 font-sans md:font-mono leading-relaxed">
                            Premium automotive showroom and test-drive booking SPA leveraging Inertia Server-Side
                            Rendering (SSR). Architected a native GoPay payment workflow managing automated checkout
                            redirects, transaction states, and asynchronous webhook handling. Developed an
                            administrative module with automated 2:1 image aspect-ratio cropping via Intervention Image.
                        </p>
                    </div>

                    <div class="relative pl-3 border-l border-sky-500/40 space-y-1">
                        <div class="text-[11px] font-bold text-white tracking-wide">Freelance: Pension Vyhlídka</div>
                        <p class="text-[10px] text-slate-400 font-sans md:font-mono leading-relaxed">
                            Custom CMS in PHP 7.4/MySQL with automated localization.
                        </p>
                    </div>

                    <div class="relative pl-3 border-l border-slate-800 space-y-0.5 opacity-60">
                        <div class="text-[10px] font-bold text-slate-400">Ops & Customer Services Background</div>
                        <p class="text-[9px] text-slate-400 font-sans leading-tight">
                            Hotel Receptionist/Night Auditor (2015-2023). Managed high-pressure situations, CRM systems
                            (Fidelio, HotelTime).
                        </p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-2 border-t border-sky-500/10 shrink-0">
                <button @click="copyEmail"
                    class="w-full bg-sky-500/10 border border-sky-500/40 py-2.5 rounded-xl text-[10px] text-sky-400 uppercase font-bold hover:bg-sky-500/20 hover:border-sky-500 transition-all flex items-center justify-center gap-2 cursor-pointer active:scale-98 select-none">
                    <CheckCircle2 v-if="copied" :size="12" class="text-emerald-400" />
                    <Mail v-else :size="12" />
                    {{ copied ? 'Email Copied!' : 'Copy Contact Email' }}
                </button>
                <a href="/assets/erik-sternad-cv.pdf" download="Erik_Sternad_CV.pdf"
                    class="w-full bg-gradient-to-r from-fuchsia-600/20 to-sky-600/20 border border-fuchsia-500/40 py-2.5 rounded-xl text-[10px] text-white uppercase font-bold hover:from-fuchsia-600/30 hover:to-sky-600/30 hover:border-fuchsia-500 transition-all flex items-center justify-center gap-2 cursor-pointer active:scale-98 text-center select-none">
                    <Download :size="12" class="text-fuchsia-400" />
                    Download Official CV (PDF)
                </a>
            </div>
        </div>
    </div>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}

.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

@keyframes scan-slow {
    0% {
        transform: translateY(-50%);
    }

    100% {
        transform: translateY(50%);
    }
}

.animate-scan-slow {
    animation: scan-slow 8s linear infinite;
}
</style>
