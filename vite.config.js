import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
// 1. Importuj plugin
import Components from 'unplugin-vue-components/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        // 2. Přidej konfiguraci pluginu
        Components({
            // Cesty ke složkám, kde má hledat komponenty
            dirs: ['resources/js/Components'],
            // Povolit automatickou detekci komponent (včetně .vue)
            extensions: ['vue'],
            // Generovat d.ts soubor pro TypeScript (pokud ho nepoužíváš, nevadí, nechej to tak)
            dts: true,
        }),
    ],
});
