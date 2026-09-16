import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.ts'],
            refresh: [
                'app/**',
                'routes/**',
                'resources/views/**',
                'lang/**',
                'resources/lang/**',
            ],
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        tailwindcss(),
    ],
    server: {
        host: '127.0.0.1',
        port: 5173,
        strictPort: true,
        hmr: {
            host: '127.0.0.1',
            port: 5173,
        },
        watch: {
            ignored: [
                '**/storage/**',
                '**/database/**/*.sqlite*',
                '**/vendor/**',
                '**/public/build/**',
            ],
        },
    },
    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
});