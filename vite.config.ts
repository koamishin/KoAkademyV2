import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import fastGlob from 'fast-glob';
import { dirname } from 'node:path';
import { fileURLToPath } from 'node:url';
import { defineConfig } from 'vite';

const projectDirectory = dirname(fileURLToPath(import.meta.url));

const moduleEntries = fastGlob.sync('Modules/*/resources/js/entries/*.ts', {
    cwd: projectDirectory,
    absolute: true,
});

export default defineConfig(({ command }) => ({
    server: {
        host: '127.0.0.1',
        hmr: {
            host: '127.0.0.1',
        },
    },
    plugins: [
        laravel({
            input: [
                'resources/js/app.ts',
                'resources/css/filament/admin/theme.css',
                ...moduleEntries,
            ],
            ssr: 'resources/js/ssr.ts',
            refresh: true,
        }),
        tailwindcss(),
        ...(command === 'serve'
            ? [
                  wayfinder({
                      formVariants: true,
                  }),
              ]
            : []),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
}));
