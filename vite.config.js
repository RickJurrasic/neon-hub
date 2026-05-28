import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import Components from 'unplugin-vue-components/vite';
import fs from 'fs'; // Přidáno pro čtení certifikátů

export default defineConfig({
    server: {
        // Konfigurace pro HTTPS pomocí tvých lokálních certifikátů
        https: {
            key: fs.readFileSync('./neon-hub.test-key.pem'),
            cert: fs.readFileSync('./neon-hub.test.pem'),
        },
        host: 'neon-hub.test',
    },
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
        Components({
            dirs: ['resources/js/Components'],
            extensions: ['vue'],
            dts: true,
        }),
    ],
});
