import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/scene.js', 'resources/css/admin.css', 'resources/js/admin.js'],
            refresh: false,
        }),
    ],
    build: {
        target: 'es2020',
        chunkSizeWarningLimit: 900,
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (id.includes('node_modules/three')) return 'three';
                    if (id.includes('node_modules/gsap')) return 'gsap';
                },
            },
        },
    },
});
