import '../css/app.css';
import '../css/neon-ui.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

// --- INICIALIZACE LARAVEL ECHO (TRACER BULLET) ---
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: 'neon-hub.test', // 👈 Změna z 127.0.0.1 na doménu, pro kterou máš SSL certifikát
    wsPort: 8080,
    wssPort: 8080,
    forceTLS: true, // 🔒 Ponechat true (vyžaduje wss://)
    enabledTransports: ['ws', 'wss'],
});
// --------------------------------------------------

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
