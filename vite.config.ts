import inertia from '@inertiajs/vite';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import i18n from 'laravel-vue-i18n/vite';
import fs from 'node:fs';
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
            script: {
                globalTypeFiles: [
                    path.resolve('resources/js/spatie/types.ts'),
                    path.resolve('resources/js/wayfinder/types.d.ts'),
                    path.resolve('resources/js/types/inertia.d.ts'),
                ],
                fs: {
                    fileExists: (file: string) => {
                        try {
                            return fs.statSync(file).isFile();
                        } catch {
                            return false;
                        }
                    },
                    readFile: (file: string) => {
                        try {
                            return fs.readFileSync(file, 'utf-8');
                        } catch {
                            return undefined;
                        }
                    },
                },
            },
        }),
        i18n(),
        watchAndRun([
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
