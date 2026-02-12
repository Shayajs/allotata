import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/site-web-editor.css',
                'resources/css/media-library.css',
                'resources/js/app.js',
                'resources/js/site-web-editor.js',
                'resources/js/course-lesson-editor.js',
                'resources/js/media-library.js',
                'resources/js/course-blocks.js',
                'resources/js/tracking-visite.js',
                'resources/js/admin-kanban.js',
                'resources/js/admin-notes.js',
                'resources/js/admin-internal-messaging.js',
                'resources/js/checkout.js'
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
