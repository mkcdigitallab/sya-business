import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            // Un seul point d'entrée : app.js importe lui-même le CSS.
            input: ['resources/js/app.js'],
            refresh: true,
        }),
        vue({
            // Les images de /public (ex. /images/logo.jpg) sont servies telles quelles, pas traitées par Vite.
            template: { transformAssetUrls: { base: null, includeAbsolute: false } },
        }),
    ],
});
