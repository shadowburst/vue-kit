import inertia from '@inertiajs/vite';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import path from 'path';
import { defineConfig } from 'vite';
import { watchAndRun } from 'vite-plugin-watch-and-run';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.ts'],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        inertia(),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        watchAndRun([
            {
                name: 'spatie-typescript',
                watch: [
                    path.resolve('app/Data/**/*.php'),
                    path.resolve('app/Enums/**/*.php'),
                    path.resolve('app/Http/Controllers/**/*.php'),
                    path.resolve('routes/**/*.php'),
                ],
                run: 'php artisan typescript:transform',
            },
        ]),
    ],
});
