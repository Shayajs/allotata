import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/site-web-editor.css',
                'resources/js/app.js',
                'resources/js/site-web-editor.js'
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        host: true,
        hmr: {
            host: '192.168.1.2',
        },
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
