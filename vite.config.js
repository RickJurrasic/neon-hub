import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue'; // Nezapomeň na tyto importy
import Components from 'unplugin-vue-components/vite';
import fs from 'fs';

export default defineConfig(({ mode }) => {
    // Tady načteme env proměnné
    const env = loadEnv(mode, process.cwd(), '');

    return {
        server: {
            https: {
                key: fs.readFileSync(env.REVERB_TLS_KEY),
                cert: fs.readFileSync(env.REVERB_TLS_CERT),
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
    };
});
