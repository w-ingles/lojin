import { fileURLToPath, URL } from 'node:url';
import { resolve }            from 'node:path';
import { defineConfig }       from 'vite';
import vue                    from '@vitejs/plugin-vue';
import { viteStaticCopy }     from 'vite-plugin-static-copy';

export default defineConfig({
    plugins: [
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),

        // Copia os temas e layout do /public para /dist
        viteStaticCopy({
            targets: [
                { src: 'public/theme',   dest: '.' },
                { src: 'public/layout',  dest: '.' },
                { src: 'public/favicon.ico', dest: '.' },
            ],
        }),
    ],

    // Vite irá processar o index.mobile.html como entry point
    build: {
        outDir: 'dist',
        emptyOutDir: true,
        rollupOptions: {
            input: resolve(__dirname, 'index.mobile.html'),
        },
    },

    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
        },
    },
});
