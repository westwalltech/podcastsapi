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
        laravel({
            input: ['resources/js/addon.js'],
            publicDirectory: '../../../public/vendor/podcast-link-finder',
            buildDirectory: 'build',
            hotFile: '../../../public/vendor/podcast-link-finder/hot',
        }),
        statamic(),
    ],
});
