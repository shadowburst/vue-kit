import inertia from '@inertiajs/vite';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import i18n from 'laravel-vue-i18n/vite';
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
        i18n(),
        watchAndRun([
            {
                name: 'spatie-data',
                watch: [path.resolve('app/Data/**/*.php')],
                run: 'php artisan typescript:transform',
            },
            {
                name: 'wayfinder',
                watch: [
                    path.resolve('app/Models/**/*.php'),
                    path.resolve('app/Enums/**/*.php'),
                    path.resolve('app/Http/Controllers/**/*.php'),
                    path.resolve('app/Http/Requests/**/*.php'),
                    path.resolve('app/Http/Resources/**/*.php'),
                    path.resolve('app/Http/Middleware/**/*.php'),
                    path.resolve('app/Data/**/*.php'),
                    path.resolve('routes/**/*.php'),
                ],
                run: 'php artisan wayfinder:generate',
            },
        ]),
    ],
});
