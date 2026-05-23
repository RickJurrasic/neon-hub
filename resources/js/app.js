import '../css/app.css';
import '../css/neon-ui.css';
import './bootstrap'; // Tady je teď Echo konfigurace

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { createPinia } from 'pinia';
import { initNotificationService } from '@/Services/NotificationService';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const pinia = createPinia();
        const app = createApp({ render: () => h(App, props) });

        app.use(plugin)
           .use(pinia)
           .use(ZiggyVue)
           .mount(el);

        // --- INICIALIZACE SLUŽEB ---
        // Předpokládáme, že ID uživatele máš v props (např. z Inertia Share)
        const userId = props.initialPage.props.auth?.user?.id;
        if (userId) {
            initNotificationService(userId);
        }

        return app;
    },
    progress: {
        color: '#4B5563',
    },
});
