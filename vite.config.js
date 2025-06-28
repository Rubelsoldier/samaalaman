import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

// This is our custom "sledgehammer" plugin
const customCorsPlugin = {
    name: 'custom-cors',
    configureServer: (server) => {
        server.middlewares.use((req, res, next) => {
            // Set the CORS header on every request
            res.setHeader('Access-Control-Allow-Origin', '*');
            next();
        });
    },
};

export default defineConfig({
    plugins: [
        // Our custom plugin goes first!
        customCorsPlugin,
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    server: {
        host: '0.0.0.0',
        hmr: {
            host: 'localhost',
        },
    },
});