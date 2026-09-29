import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.ts'],
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
        tailwindcss(),
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
    build: {
        reportCompressedSize: false,
        chunkSizeWarningLimit: 1500,
        rollupOptions: {
            input: ['resources/css/app.css', 'resources/js/app.ts'],
            output: {
                manualChunks(id) {
                    if (!id.includes('node_modules')) return;
                    if (id.includes('leaflet')) return 'maps';
                    if (id.includes('chart.js') || id.includes('vue-chartjs')) return 'charts';
                    if (id.includes('@lucide/vue') || id.includes('lucide')) return 'icons';
                    if (id.includes('vue') || id.includes('inertia') || id.includes('pinia')) return 'framework';
                },
            },
        },
    },
    server: {
        host: '127.0.0.1',
        port: 5173,
        strictPort: true,
        watch: {
            ignored: ['**/storage/**', '**/vendor/**', '**/node_modules/**', '**/فطارنى/**'],
        },
    },
});
