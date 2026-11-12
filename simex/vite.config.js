import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/css/site.css', 'resources/js/app.js', 'resources/js/calc.js', 'resources/js/quote.js', 'resources/js/charts.js', 'resources/js/site.js'],
            refresh: false,
        }),
        tailwindcss(),
    ],
    build: { chunkSizeWarningLimit: 900 },
});
