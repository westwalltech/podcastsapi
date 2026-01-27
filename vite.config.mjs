import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import statamic from '@statamic/cms/vite-plugin';

export default defineConfig({
    server: {
        host: 'localhost',
        port: 5177,
        strictPort: true,
    },
    plugins: [
        statamic(),
        laravel({
            input: ['resources/js/addon.js'],
            publicDirectory: 'resources/dist',
            buildDirectory: 'build',
        }),
    ],
});
